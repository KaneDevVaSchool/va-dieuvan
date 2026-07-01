<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SuperAdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $next = $this->sanitizePostLoginRedirect($request->query('redirect', '/'));
        session(['oauth_redirect' => $next]);

        $response = $this->googleDriver()->redirect();

        // Ghi session (oauth state của Socialite + oauth_redirect) trước khi trình duyệt rời site — giảm InvalidState khi driver=file hoặc proxy chậm.
        $request->session()->save();

        Log::info('google.oauth.redirect', array_merge([
            'ip' => $request->ip(),
            'next_length' => strlen($next),
            'user_agent' => Str::limit((string) $request->userAgent(), 200, '…'),
            'oauth_mode' => config('services.google.stateless', false) ? 'stateless' : 'session_state',
            'trusted_proxies' => config('trusted_proxies.proxies') === '*' ? '*' : (config('trusted_proxies.proxies') !== null ? 'set' : 'null'),
        ], $this->oauthSessionLogFragment($request)));

        return $response;
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = $this->googleDriver()->user();
        } catch (InvalidStateException $e) {
            Log::warning('google.oauth.invalid_state', array_merge([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'empty',
                'oauth_mode' => config('services.google.stateless', false) ? 'stateless' : 'session_state',
            ], $this->oauthDiagnosticContext($request)));

            return $this->loginRedirect([
                'error' => 'Phiên đăng nhập Google bị gián đoạn (cookie phiên không khớp). Hãy tắt chặn cookie, đóng các tab khác rồi thử lại. Nếu vẫn lỗi, kiểm tra .env: APP_URL (https), SESSION_DOMAIN để trống hoặc đúng host, và TRUSTED_PROXIES=* sau reverse proxy.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('google.oauth.callback_failed', array_merge([
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'oauth_mode' => config('services.google.stateless', false) ? 'stateless' : 'session_state',
            ], $this->oauthDiagnosticContext($request)));

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

        SuperAdminAccess::ensureRole($user);

        // Không đưa token thẳng lên URL (rò qua access log / history / referer).
        // Phát một mã ngẫu nhiên dùng-một-lần (TTL ngắn); SPA POST /api/auth/exchange để đổi lấy token.
        $code = Str::random(64);
        Cache::put(
            AuthController::OAUTH_EXCHANGE_CACHE_PREFIX.hash('sha256', $code),
            $user->getKey(),
            now()->addSeconds(120),
        );

        $next = $this->sanitizePostLoginRedirect(session()->pull('oauth_redirect', '/'));

        if (! $user->canAccessDispatchWebApp() && ! $user->canAccessDriverWebApp()) {
            Log::notice('google.oauth.login_no_roles_portal', ['user_id' => $user->getKey()]);
            $next = '/portal';
        } elseif ($next === '' || $next === '/') {
            $next = $this->defaultWebAppHome($user);
        }

        Log::info('google.oauth.callback_success', ['user_id' => $user->getKey()]);

        return $this->loginRedirect([
            'code' => $code,
            'redirect' => $next,
        ]);
    }

    /**
     * @return \Laravel\Socialite\Contracts\Provider
     */
    private function googleDriver()
    {
        $driver = Socialite::driver('google')->with(['prompt' => 'select_account']);

        if (config('services.google.stateless', false)) {
            return $driver->stateless();
        }

        return $driver;
    }

    /**
     * @return array{session_id_fragment: ?string}
     */
    private function oauthSessionLogFragment(Request $request): array
    {
        $id = $request->session()->getId();

        return [
            'session_id_fragment' => $id !== '' ? Str::substr($id, 0, 8).'…' : null,
        ];
    }

    /**
     * Ngữ cảnh chẩn đoán OAuth (không log code/state/Google token).
     *
     * @return array<string, mixed>
     */
    private function oauthDiagnosticContext(Request $request): array
    {
        $cookieName = config('session.cookie');

        return array_merge($this->oauthSessionLogFragment($request), [
            'session_driver' => config('session.driver'),
            'session_same_site' => config('session.same_site'),
            'session_secure' => config('session.secure'),
            'session_domain' => config('session.domain'),
            'session_cookie_name' => $cookieName,
            'has_session_cookie' => $cookieName !== null && $request->cookies->has((string) $cookieName),
            'request_https' => $request->secure(),
            'callback_has_code_param' => $request->filled('code'),
            'callback_has_state_param' => $request->filled('state'),
            'x_forwarded_proto' => $request->headers->get('X-Forwarded-Proto'),
            'host' => $request->getHost(),
            'app_url' => config('app.url'),
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

    private function defaultWebAppHome(User $user): string
    {
        if (! $user->canAccessDispatchWebApp() && $user->canAccessDriverWebApp()) {
            return '/driver';
        }

        if ($this->isDeptHeadOnly($user)) {
            return '/dept';
        }

        return '/mng';
    }

    private function isDeptHeadOnly(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if (! $user->hasRole('department_head')) {
            return false;
        }

        return ! $user->hasAnyRole(['admin', 'dispatcher', 'superadmin']);
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
