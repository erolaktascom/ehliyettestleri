<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CampaignMailer;
use App\Support\SiteBrand;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Log, Password};
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request()
    {
        return response()->view('pages.auth.forgot-password')
            ->header('Cache-Control', 'private, no-store');
    }

    public function email(Request $request, CampaignMailer $mailer)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        if (!$mailer->settings()) {
            return back()->withErrors(['email' => 'E-posta ile şifre sıfırlama şu anda kullanılamıyor. Lütfen site yönetimiyle iletişime geçin.'])->onlyInput('email');
        }
        $recipient = null;
        try {
            Password::broker()->sendResetLink($data, function (User $user, string $token) use ($mailer, &$recipient) {
                $recipient = $user;
                // Never derive a reset-link origin from the incoming Host header.
                $url = rtrim(config('app.url'), '/').route('password.reset', ['token' => $token], false)
                    .'?'.http_build_query(['email' => $user->getEmailForPasswordReset()], '', '&', PHP_QUERY_RFC3986);
                $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
                $mailer->sendMessage($user->getEmailForPasswordReset(), SiteBrand::name().' - Şifre sıfırlama',
                    "Şifrenizi yenilemek için aşağıdaki bağlantıyı açın:\n\n".$url
                    ."\n\nBu bağlantı {$minutes} dakika geçerlidir ve yalnızca bir kez kullanılabilir.\n"
                    ."Bu işlemi siz istemediyseniz e-postayı dikkate almayın. Şifreniz değişmez.");
            });
        } catch (\Throwable $exception) {
            if ($recipient) Password::broker()->deleteToken($recipient);
            // Transport exceptions can contain credentials, addresses or message bodies.
            Log::warning('Password reset mail transport failed.', ['exception' => get_class($exception)]);
            return back()->withErrors(['email' => 'Sıfırlama e-postası gönderilemedi. Lütfen daha sonra tekrar deneyin.'])->onlyInput('email');
        }
        // Same response for unknown accounts and recently sent links.
        return back()->with('status', 'Bu e-posta adresine kayıtlı bir hesap varsa şifre sıfırlama bağlantısı gönderildi. Gelen kutunuzu ve spam klasörünü kontrol edin.');
    }

    public function edit(Request $request, string $token)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        return response()->view('pages.auth.reset-password', ['token' => $token, 'email' => $data['email']])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:255', 'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        $login = 'login';
        $status = Password::broker()->reset($data, function (User $user, string $password) use (&$login) {
            DB::transaction(function () use ($user, $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
            });
            $login = $user->is_admin ? 'admin.login' : 'login';
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Bağlantı geçersiz veya süresi dolmuş. Lütfen yeni bir sıfırlama bağlantısı isteyin.'])->onlyInput('email');
        }
        return redirect()->route($login)->with('success', 'Şifreniz yenilendi. Yeni şifrenizle giriş yapabilirsiniz.');
    }
}
