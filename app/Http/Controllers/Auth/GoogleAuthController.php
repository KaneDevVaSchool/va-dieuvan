<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $next = $request->query('redirect', '/');
        session(['oauth_redirect' => is_string($next) ? $next : '/']);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return $this->loginRedirect(['error' => 'Đăng nhập Google thất bại.']);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return $this->loginRedirect(['error' => 'Không lấy được email từ Google.']);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $allowedDomains = $this->allowedDomains();
            if ($allowedDomains === []) {
                return $this->loginRedirect([
                    'error' => 'Tài khoản chưa được cấp. Vui lòng liên hệ nhà trường.',
                ]);
            }

            $domain = substr(strrchr($email, '@'), 1) ?: '';
            if (! in_array($domain, $allowedDomains, true)) {
                return $this->loginRedirect([
                    'error' => 'Chỉ chấp nhận email do nhà trường cung cấp.',
                ]);
            }

            $user = User::query()->create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'google_id' => $googleUser->getId(),
            ]);
        } else {
            if (isset($user->is_active) && ! $user->is_active) {
                return $this->loginRedirect(['error' => 'Tài khoản đã bị khóa.']);
            }

            if ($googleUser->getId()) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        }

        $token = $user->createToken('web')->plainTextToken;
        $next = session()->pull('oauth_redirect', '/');
        if (! is_string($next) || $next === '') {
            $next = '/';
        }

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
