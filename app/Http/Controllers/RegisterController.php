<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $states = RegistrationService::GST_STATES;

        return view('auth.register', compact('states'));
    }

    public function register(RegisterRequest $request, RegistrationService $service): RedirectResponse
    {
        $user = $service->registerBusiness($request->validated());

        Auth::login($user);
        $request->session()->regenerate();
        session(['active_branch_id' => $user->default_branch_id]);

        return redirect()->route('dashboard')->with('success', 'Welcome to BILLINGPOC! Your business and store have been successfully registered.');
    }
}
