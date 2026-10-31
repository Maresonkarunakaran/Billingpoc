<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) $request->input('email', ''));
        $inputPassword = (string) $request->input('password', '');

        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $credentials = [
            $isEmail ? 'email' : 'phone' => $isEmail ? strtolower($loginInput) : $loginInput,
            'password' => $inputPassword,
        ];

        $remember = $request->boolean('remember');

        // Attempt 1: Exact credentials
        $attempted = Auth::attempt($credentials, $remember);

        // Attempt 2: If failed and not email, also check if entered as email anyway
        if (! $attempted && ! $isEmail) {
            $attempted = Auth::attempt(['email' => strtolower($loginInput), 'password' => $inputPassword], $remember);
        }

        // Attempt 3: If failed, try trimming password in case trailing space was copied
        if (! $attempted && trim($inputPassword) !== $inputPassword) {
            $credentials['password'] = trim($inputPassword);
            $attempted = Auth::attempt($credentials, $remember);
        }

        if ($attempted) {
            $user = Auth::user();

            return $this->completeLogin($request, $user);
        }

        return back()->withErrors([
            'email' => 'Invalid email or password provided. Please verify the email and password.',
        ])->onlyInput('email');
    }

    private function completeLogin(Request $request, User $user): RedirectResponse
    {
        $request->session()->regenerate();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is deactivated. Contact administrator.',
            ]);
        }

        $branch = $user->defaultBranch
            ?? $user->branches()->first()
            ?? Branch::where('business_id', $user->business_id)->first();

        if ($branch) {
            session(['active_branch_id' => $branch->id]);
        }

        AuditLog::log('user_logged_in', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function switchBranch(Request $request)
    {
        $branchId = (int) $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ])['branch_id'];

        $user = Auth::user();

        // Check user access unless super admin
        if ($user->role?->slug !== 'super-admin' && ! $user->branches()->where('branches.id', $branchId)->exists()) {
            abort(403, 'Unauthorized access to this branch.');
        }

        session(['active_branch_id' => $branchId]);

        return back()->with('success', 'Active branch switched successfully.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('user_logged_out', Auth::user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
