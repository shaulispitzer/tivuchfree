<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(): Response
    {

        $users = User::query()
            ->latest('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->toDateTimeString(),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'google_avatar' => $user->google_avatar,
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
        ]);
    }

    public function makeAdmin(User $user): RedirectResponse
    {

        if (! $user->is_admin) {
            $user->update([
                'is_admin' => true,
            ]);
        }

        return back()->success('User marked as admin successfully');
    }

    public function revokeAdmin(User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && $user->id === $currentUser->getAuthIdentifier()) {
            return back()->withErrors(['user' => 'You cannot revoke your own admin status.']);
        }

        if ($user->is_admin) {
            $user->update([
                'is_admin' => false,
            ]);
        }

        return back()->success('Admin status revoked successfully');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->success('User deleted successfully');
    }
}
