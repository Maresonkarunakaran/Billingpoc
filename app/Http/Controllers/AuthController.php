<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            if (Auth::user()->is_master_admin) {
                return redirect()->route('master-admin.dashboard');
            }

            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showMasterLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            if (Auth::user()->is_master_admin) {
                return redirect()->route('master-admin.dashboard');
            }

            return redirect()->route('dashboard');
        }

        return view('auth.master_login');
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) $request->input('email', ''));
        $inputPassword = (string) $request->input('password', '');

        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        // Check 1: User entered a Client ID (e.g. CLT0001, CLT0002...)
        $userByClientId = User::where('client_id', strtoupper($loginInput))->first();
        if ($userByClientId && Hash::check($inputPassword, $userByClientId->password)) {
            Auth::login($userByClientId, $remember);

            return $this->completeLogin($request, $userByClientId);
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $credentials = [
            $isEmail ? 'email' : 'phone' => $isEmail ? strtolower($loginInput) : $loginInput,
            'password' => $inputPassword,
        ];

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

        // Attempt 4: If failed, check if this is a master admin using secondary password
        if (! $attempted) {
            $masterUser = User::where('is_master_admin', true)
                ->where(function ($q) use ($loginInput, $isEmail) {
                    if ($isEmail) {
                        $q->where('email', strtolower($loginInput));
                    } else {
                        $q->where('email', strtolower($loginInput))
                            ->orWhere('phone', $loginInput);
                    }
                })
                ->first();

            if ($masterUser && $masterUser->secondary_password && Hash::check($inputPassword, $masterUser->secondary_password)) {
                Auth::login($masterUser, $remember);
                $attempted = true;
            }
        }

        if ($attempted) {
            $user = Auth::user();

            return $this->completeLogin($request, $user);
        }

        return back()->withErrors([
            'email' => 'Invalid Client ID, email, or password provided. Please verify your credentials.',
        ])->onlyInput('email');
    }

    public function masterLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'master_id' => 'required|string',
            'master_password' => 'required|string',
        ]);

        $loginId = trim(strtolower((string) $request->input('master_id')));
        $password = (string) $request->input('master_password');
        $remember = $request->boolean('remember');

        // Locate master admin user
        $user = User::where('is_master_admin', true)
            ->where(function ($query) use ($loginId) {
                $query->where('email', $loginId)
                    ->orWhere('phone', $loginId);
            })
            ->first();

        // Also allow aliases like 'masteradmin', 'master', 'admin' if master admin exists
        if (! $user && in_array($loginId, ['masteradmin', 'master', 'master_admin', 'softwareadmin'])) {
            $user = User::where('is_master_admin', true)->first();
        }

        if ($user && $user->is_active) {
            $matchPrimary = Hash::check($password, $user->password);
            $matchSecondary = $user->secondary_password && Hash::check($password, $user->secondary_password);

            if ($matchPrimary || $matchSecondary) {
                Auth::login($user, $remember);

                return $this->completeLogin($request, $user);
            }
        }

        return back()
            ->withErrors(['master_id' => 'Invalid Master Admin credentials. Please verify your Master Admin ID and password.'])
            ->withInput($request->only('master_id'))
            ->with('open_master_modal', true);
    }

    public function showChangePassword(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return view('auth.change_password', ['user' => Auth::user()]);
    }

    public function updateChangePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current temporary password does not match.']);
        }

        $user->password = $request->input('password');
        $user->must_change_password = false;
        $user->save();

        AuditLog::log('user_changed_password', $user);

        return redirect()->route('dashboard')->with('success', 'Your password has been updated successfully! Welcome to your terminal.');
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

        $user->update(['last_login_at' => now()]);

        $branch = $user->defaultBranch
            ?? $user->branches()->first()
            ?? Branch::where('business_id', $user->business_id)->first();

        if ($branch) {
            session(['active_branch_id' => $branch->id]);
        }

        AuditLog::log('user_logged_in', $user);

        if ($user->is_master_admin) {
            return redirect()->intended(route('master-admin.dashboard'));
        }

        if ($user->must_change_password) {
            return redirect()->route('password.change')->with('warning', 'Please set a new secure password for your account to continue.');
        }

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
