<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Menampilkan halaman form login.
     */
    public function create(): View
    {
        return view('admin.auth.login'); 
    }

    /**
     * Menangani permintaan otentikasi yang masuk.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $admin = User::query()
            ->where('email', $request->string('email')->toString())
            ->where('role', 'admin')
            ->first();

        if (! $admin) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->clearRateLimiter();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Menghancurkan sesi otentikasi (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
