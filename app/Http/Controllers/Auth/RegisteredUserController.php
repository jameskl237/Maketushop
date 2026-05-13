<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isSupplier = $request->input('account_type') === 'supplier';

        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($isSupplier) {
            $rules['username']           = 'required|string|max:255|alpha_dash|unique:'.User::class;
            $rules['phone_country_code'] = 'required|string|max:4|regex:/^[0-9]{1,4}$/';
            $rules['phone_number']       = 'required|string|max:20|regex:/^[0-9]{4,20}$/';
            $rules['address']            = 'required|string|max:255';
        }

        $request->validate($rules);

        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $isSupplier ? User::ROLE_SUPPLIER : User::ROLE_USER,
            'password' => Hash::make($request->password),
        ];

        if ($isSupplier) {
            $data['username'] = $request->username;
            $data['phone']    = preg_replace('/\D+/', '', $request->phone_country_code.$request->phone_number);
            $data['address']  = $request->address;
        }

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route($user->dashboardRouteName(), absolute: false));
    }
}
