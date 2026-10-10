<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('barber')->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:150', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8', 'role' => ['required', Rule::in(['admin', 'barber'])]]);
        User::create($d);

        return back()->with('success', 'User account created.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is(request()->user()), 422, 'You cannot delete your own account.');
        if ($user->barber) {
            $user->barber->update(['user_id' => null]);
        }$user->delete();

        return back()->with('success', 'User account deleted.');
    }
}
