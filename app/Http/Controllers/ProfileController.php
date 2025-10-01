<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $u = $request->user();
        return Inertia::render('ProfileView', [
            'user' => [
                'id'                => $u->id,
                'username'          => $u->username,
                'email'             => $u->email,
                'bio'               => $u->bio,
                'profile_photo_url' => $u->profile_photo_url,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $u = $request->user();

        $data = $request->validate([
            'username'      => 'required|string|min:3|max:255|unique:users,username,' . $u->id,
            'email'         => 'required|email|unique:users,email,'    . $u->id,
            'bio'           => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($u->profile_photo) {
                Storage::disk('public')->delete($u->profile_photo);
            }
            $data['profile_photo'] = $request
                ->file('profile_photo')
                ->store('profile_photos', 'public');
        }

        $u->update($data);

        return redirect()->back()->with('success', 'Profile updated!');
    }
}
