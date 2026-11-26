<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\GoogleLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};

class GoogleAuthController extends Controller
{
    private function unavailable(Request $request, string $message)
    {
        $request->session()->forget(['google_flow', 'google_link', 'state', 'code_verifier']);
        return redirect()->route('login')->with('error', $message)->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function redirect(Request $request, GoogleLogin $google)
    {
        $settings = GoogleLogin::settings();
        if (!$settings) return $this->unavailable($request, 'Google ile giriş şu anda kullanılamıyor. E-posta ve şifrenizle giriş yapabilirsiniz.');
        $request->session()->forget('google_link');
        $request->session()->put('google_flow', ['expires' => now()->addMinutes(10)->timestamp, 'fingerprint' => GoogleLogin::fingerprint($settings)]);
        return $google->provider($request, $settings)->redirect()->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, GoogleLogin $google)
    {
        $settings = GoogleLogin::settings();
        $flow = $request->session()->pull('google_flow');
        if (!$settings || !is_array($flow) || ($flow['expires'] ?? 0) < now()->timestamp
            || !hash_equals(GoogleLogin::fingerprint($settings), $flow['fingerprint'] ?? '')
            || !$request->filled('code') || !is_string($request->query('code')) || !is_string($request->query('state')) || $request->has('error')) {
            return $this->unavailable($request, 'Google girişi tamamlanamadı veya süresi doldu. Lütfen yeniden deneyin.');
        }
        try {
            $profile = $google->provider($request, $settings)->user();
            $subject = $profile->getId();
            $email = $profile->getEmail();
            if (!is_string($subject) || $subject === '' || strlen($subject) > 255
                || !is_string($email) || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || !in_array($profile->user['email_verified'] ?? false, [true, 'true'], true)) {
                return $this->unavailable($request, 'Google hesabınızın doğrulanmış e-posta bilgisi alınamadı.');
            }
            $email = mb_strtolower($email);
            $result = DB::transaction(function () use ($subject, $email, $profile) {
                $user = User::where('google_id', $subject)->lockForUpdate()->first();
                if ($user) return ['user' => $user];
                $existing = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
                if ($existing) return ['existing' => $existing];
                $user = new User(['name' => mb_substr(trim($profile->getName() ?: 'Google Kullanıcısı'), 0, 255),
                    'email' => $email, 'password' => null, 'is_admin' => false]);
                $user->google_id = $subject;
                $user->email_verified_at = now();
                $user->save();
                return ['user' => $user];
            });
            $user = $result['user'] ?? $result['existing'];
            if ($user->is_admin) return $this->unavailable($request, 'Yönetim hesapları için mevcut e-posta ve şifre girişini kullanın.');
            if (isset($result['existing'])) {
                if ($user->google_id || !$user->password) return $this->unavailable($request, 'Bu hesap başka bir giriş yöntemiyle eşleştirilmiş. Mevcut giriş yönteminizi kullanın.');
                $request->session()->put('google_link', ['user_id' => $user->id, 'subject' => $subject, 'email' => $email,
                    'expires' => now()->addMinutes(10)->timestamp, 'fingerprint' => GoogleLogin::fingerprint($settings)]);
                return redirect()->route('auth.google.link')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
            }
            return $this->login($request, $user);
        } catch (\Throwable $e) {
            // OAuth exception messages can contain credentials/tokens: never log them or their traces.
            Log::warning('Google sign-in failed.', ['exception_type' => get_class($e)]);
            return $this->unavailable($request, 'Google ile bağlantı tamamlanamadı. Lütfen tekrar deneyin veya e-posta ile giriş yapın.');
        } finally {
            $request->session()->forget(['state', 'code_verifier']);
        }
    }

    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('google_link');
        $settings = GoogleLogin::settings();
        return $settings && is_array($pending) && ($pending['expires'] ?? 0) >= now()->timestamp
            && hash_equals(GoogleLogin::fingerprint($settings), $pending['fingerprint'] ?? '') ? $pending : null;
    }

    public function showLink(Request $request)
    {
        $pending = $this->pending($request);
        if (!$pending) return $this->unavailable($request, 'Hesap eşleştirme süresi doldu. Google girişini yeniden başlatın.');
        return response()->view('pages.auth.google-link', ['email' => $pending['email']])->header('Cache-Control', 'no-store');
    }

    public function link(Request $request)
    {
        $pending = $this->pending($request);
        if (!$pending) return $this->unavailable($request, 'Hesap eşleştirme süresi doldu. Google girişini yeniden başlatın.');
        $request->validate(['password' => 'required|string|max:1024']);
        try {
            $user = DB::transaction(function () use ($request, $pending) {
                $user = User::lockForUpdate()->find($pending['user_id']);
                if (!$user || $user->is_admin || $user->google_id || mb_strtolower($user->email) !== $pending['email']
                    || !$user->password || !Hash::check($request->input('password'), $user->password)) return null;
                $user->google_id = $pending['subject'];
                $user->email_verified_at ??= now();
                $user->save();
                return $user;
            });
        } catch (\Illuminate\Database\QueryException) {
            return $this->unavailable($request, 'Hesap eşleştirilemedi. Giriş işlemini yeniden başlatın.');
        }
        if (!$user) return back()->withErrors(['password' => 'Hesap doğrulanamadı. Mevcut hesap şifrenizi kontrol edin.']);
        return $this->login($request, $user);
    }

    private function login(Request $request, User $user)
    {
        $request->session()->forget(['google_link', 'google_flow', 'state', 'code_verifier']);
        Auth::login($user);
        $request->session()->regenerate();
        if (!$user->password) session()->flash('success', 'Google ile giriş yaptınız. Hesap Ayarları bölümünden ayrıca bir şifre oluşturabilirsiniz.');
        return redirect()->route('dashboard')->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
