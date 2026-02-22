<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialiteController extends Controller
{
    public function create(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function store(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $authUser = $this->findOrCreateUser($googleUser);

        Auth::login($authUser);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function findOrCreateUser(SocialiteUser $googleUser): User
    {
        $authUser = User::where('email', $googleUser->getEmail())->first();

        if ($authUser) {
            $authUser->update([
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
            ]);

            return $authUser;
        }

        $authUser = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'password' => Hash::make(Str::random(8)),
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
        ]);

        $authUser->markEmailAsVerified();

        event(new Registered($authUser));

        return $authUser;
    }
}
