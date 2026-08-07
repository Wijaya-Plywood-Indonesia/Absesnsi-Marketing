<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) {
            return $this->redirectByRole();
        }

        return view('marketer.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! auth()->attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $user = auth()->user();

        if (! $user->canLogin()) {
            auth()->logout();

            if ($user->isPending()) {
                $message = 'Akun Anda belum diverifikasi oleh admin.';
            } else {
                $sampai = $user->validated_at->translatedFormat('d F Y, H:i');
                $message = "Akun Anda dinonaktifkan sampai {$sampai}.";
            }

            return back()->withErrors([
                'email' => $message,
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole();
    }

    public function showRegister()
    {
        if (auth()->check()) {
            return $this->redirectByRole();
        }

        return view('marketer.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'marketing',
            'validated_at' => null, // tetap butuh verifikasi admin
        ]);

        return redirect()->route('login')->with(
            'status',
            'Pendaftaran berhasil. Akun Anda menunggu verifikasi dari admin sebelum bisa login.'
        );
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole()
    {
        if (auth()->user()->role === 'admin') {
            return redirect('/admin');
        }

        return redirect()->intended(route('dashboard'));
    }
}
