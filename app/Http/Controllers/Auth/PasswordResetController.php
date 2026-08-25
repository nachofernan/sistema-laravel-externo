<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\EventoUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'username' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => 'No encontramos un usuario con ese CUIT.']);
        }

        // Generar token único
        $token = Str::random(64);

        // Guardar el token en la base de datos
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'username' => $user->username, // Guardamos el CUIT también
            'created_at' => Carbon::now()
        ]);

        // Enviar el email con el link de reseteo
        if(app()->environment('production') || str_ends_with($user->email, '@buenosairesenergia.com.ar') || $user->email == 'nachofernan@gmail.com') {
            Mail::to([$user->email])->send(new PasswordResetMail($token, $user));
        }
        //Mail::to($user->email)->send(new PasswordResetMail($token, $user));
        //Mail::to('ifernandez@ccasa.com.ar')->send(new PasswordResetMail($token, $user));

        return back()->with('status', 'Hemos enviado un enlace de recuperación a su correo electrónico.');
    }

    public function showResetForm(Request $request)
    {
        $token = $request->token;
        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'username' => 'required',
            'password' => ['required', 'confirmed', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
            ],
        ]);

        $updatePassword = DB::table('password_reset_tokens')
            ->where('username', $request->username)
            ->where('created_at', '>', Carbon::now()->subHours(12))
            ->first();
            

        if (!$updatePassword || !Hash::check($request->token, $updatePassword->token)) {
            return back()->withErrors(['error' => 'Token inválido o expirado.']);
        }

        $user = User::where('username', $request->username)->first();
        
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        EventoUsuario::registrar('cambio_password', $user, detalle: ['origen' => 'reset_por_token']);

        DB::table('password_reset_tokens')
            ->where(['username' => $request->username])
            ->delete();

        return redirect()->route('login')
            ->with('status', 'Su contraseña ha sido actualizada correctamente.');
    }
}