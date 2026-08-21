<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class ProgressiveLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.progressive-login');
    }
}
