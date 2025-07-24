<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\Proveedores\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class ProviderRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.provider-register');
    }

    public function checkCuit(Request $request)
    {
        $request->validate([
            'cuit' => 'required|numeric'
        ]);

        $cuit = $request->cuit;

        // Verificar si ya existe un usuario con este CUIT
        $existingUser = User::where('username', $cuit)->first();
        if ($existingUser) {
            return redirect()->route('login')
                ->with('status', 'Ya existe un usuario registrado con este CUIT. Por favor, inicie sesión.');
        }

        // Buscar el proveedor en la base de datos interna
        $proveedor = Proveedor::on('proveedores')
            ->where('cuit', $cuit)
            ->first();

        if (!$proveedor) {
            return back()->with('error', 'El CUIT ingresado no está registrado en nuestro sistema.');
        }

        // Generar contraseña temporal
        $temporaryPassword = Str::random(10);

        // Crear nuevo usuario
        $user = User::create([
            'name' => $proveedor->razonsocial,
            'username' => $cuit,
            'email' => $proveedor->correo,
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
        ]);

        // Enviar email con la contraseña temporal
        //Mail::to($proveedor->email)->send(new TemporaryPasswordMail($temporaryPassword));
        if(str_ends_with($proveedor->correo, '@buenosairesenergia.com.ar')) {
            Mail::to([$proveedor->correo])->send(new TemporaryPasswordMail($temporaryPassword));
        }
        //Mail::to('ifernandez@ccasa.com.ar')->send(new TemporaryPasswordMail($temporaryPassword));

        return redirect()->route('login')
            ->with('status', 'Se ha enviado una contraseña temporal a su correo electrónico registrado.');
    }
}
