<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $next = $this->sanitizePostLoginRedirect($request->query('redirect', '/'));
        session(['oauth_redirect' => $next]);

        Log::info('google.oauth.redirect', [
            'ip' => $request->ip(),
            'next_length' => strlen($next),
            'user_agent' => Str::limit((string) $request->userAgent(), 200, '…'),
        ]);

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('google.oauth.callback_failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'session_id_fragment' => session()->getId()
                    ? Str::substr(session()->getId(), 0, 8).'…'
                    : null,
            ]);

            return $this->loginRedirect(['error' => 'Đăng nhập Google thất bại.']);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            Log::warning('google.oauth.callback_no_email');

            return $this->loginRedirect(['error' => 'Không lấy được email từ Google.']);
        }

        $rawGoogleId = $googleUser->getId();
        $googleId = ($rawGoogleId !== null && (string) $rawGoogleId !== '')
            ? (string) $rawGoogleId
            : null;

        // Prefer the row already linked to this Google account (avoids duplicate google_id when
        // email lookup hits a different user than the one holding this google_id).
        $user = $googleId
            ? User::query()->where('google_id', $googleId)->first()
            : null;
        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }
        if ($user && $googleId) {
            $other = User::query()
                ->where('google_id', $googleId)
                ->whereKeyNot($user->getKey())
                ->first();
            if ($other) {
                $user = $other;
            }
        }

        if (! $user) {
            $allowedDomains = $this->allowedDomains();
            if ($allowedDomains === []) {
                Log::notice('google.oauth.provision_blocked_no_domains');

                return $this->loginRedirect([
                    'error' => 'Tài khoản chưa được cấp. Vui lòng liên hệ nhà trường.',
                ]);
            }

            $domain = substr(strrchr($email, '@'), 1) ?: '';
            if (! in_array($domain, $allowedDomains, true)) {
                Log::notice('google.oauth.provision_blocked_domain', [
                    'domain_attempted' => $domain !== '' ? $domain : 'empty',
                ]);

                return $this->loginRedirect([
                    'error' => 'Chỉ chấp nhận email do nhà trường cung cấp.',
                ]);
            }

            $user = User::query()->create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'google_id' => $googleId,
                'avatar_url' => $googleUser->getAvatar(),
            ]);
        } else {
            if (isset($user->is_active) && ! $user->is_active) {
                Log::notice('google.oauth.login_blocked_inactive_user', ['user_id' => $user->getKey()]);

                return $this->loginRedirect(['error' => 'Tài khoản đã bị khóa.']);
            }

            $fill = [];
            if ($googleId) {
                $fill['google_id'] = $googleId;
            }
            $avatar = $googleUser->getAvatar();
            if (is_string($avatar) && $avatar !== '') {
                $fill['avatar_url'] = $avatar;
            }
            if ($fill !== []) {
                $user->forceFill($fill)->save();
            }
        }

        if (! $user->canAccessDispatchWebApp() && ! $user->canAccessDriverWebApp()) {
            Log::notice('google.oauth.login_blocked_no_roles', ['user_id' => $user->getKey()]);

            return $this->loginRedirect([
                'error' => 'Tài khoản không có quyền truy cập. Cần vai trò superadmin, admin, dispatcher hoặc tài xế (driver).',
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;
        $next = $this->sanitizePostLoginRedirect(session()->pull('oauth_redirect', '/'));
        if ($next === '' || $next === '/') {
            $next = '/';
        }

        Log::info('google.oauth.callback_success', ['user_id' => $user->getKey()]);

        return $this->loginRedirect([
            'token' => $token,
            'redirect' => $next,
        ]);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function loginRedirect(array $query): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to('/login?'.http_build_query($query));
    }

    /**
     * Redirect sau đăng nhập: chỉ đường dẫn nội bộ, không cho payload OAuth/Google lồng nhau.
     */
    private function sanitizePostLoginRedirect(mixed $raw): string
    {
        if (! is_string($raw)) {
            return '/';
        }

        $s = trim(str_replace('+', ' ', $raw));
        if ($s === '') {
            return '/';
        }
        if (strlen($s) > 512) {
            Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'initial_length']);

            return '/';
        }

        for ($i = 0; $i < 14; $i++) {
            $decoded = rawurldecode($s);
            if ($decoded === $s) {
                break;
            }
            $s = $decoded;
            if (strlen($s) > 768) {
                Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'decode_overflow']);

                return '/';
            }
        }

        $s = trim($s);
        if ($s === '') {
            return '/';
        }

        if (str_contains($s, '://')) {
            Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'absolute_scheme']);

            return '/';
        }

        if (! str_starts_with($s, '/')) {
            $s = '/'.$s;
        }

        $lower = strtolower($s);
        if (
            str_contains($lower, '/auth/google')
            || str_contains($lower, '%2fauth%2fgoogle')
        ) {
            Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'auth_google_path']);

            return '/';
        }

        if (preg_match('/[?&]code=/', $s) || preg_match('/[?&]state=/', $s)) {
            Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'oauth_query_params']);

            return '/';
        }

        if (strlen($s) > 512) {
            Log::notice('google.oauth.invalid_redirect_scrubbed', ['reason' => 'final_length']);

            return '/';
        }

        return $s;
    }

    /**
     * @return list<string>
     */
    private function allowedDomains(): array
    {
        $raw = config('services.google.allowed_domains');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
