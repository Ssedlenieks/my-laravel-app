<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\CategoryController;
use Inertia\Inertia;
use App\Http\Controllers\LingvanexController;
use App\Http\Controllers\ImageController;


Route::get('/storage/profile_photos/{filename}', [ImageController::class, 'serveProfilePhoto']);
Route::get('/storage/posts/{filename}', [ImageController::class, 'servePostImage']);


Route::get('/lingvanex/lookup', [LingvanexController::class, 'lookupWord']);
Route::get('/lingvanex/translate', [LingvanexController::class, 'translate']);
Route::get('/lingvanex/languages', [LingvanexController::class, 'getLanguages']);



Route::get('/', fn() => Inertia::render('HomeView'))->name('home');
Route::get('/tech', fn() => Inertia::render('TechView'))->name('runway');
Route::get('/sports', fn() => Inertia::render('SportsView'))->name('style');
Route::get('/kultura', fn() => Inertia::render('KulturaView'))->name('kultura');
Route::get('/ekonomika', fn() => Inertia::render('EkonomikaView'))->name('ekonomika');
Route::get('/politika', fn() => Inertia::render('PolitikaView'))->name('politika');
Route::get('/login', fn() => Inertia::render('LoginView'))->name('login');
Route::get('/studies', fn() => Inertia::render('StudiesView'))->name('studies');
Route::get('/profile', fn() => Inertia::render('ProfileView'))->name('profile');


Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
Route::get('/posts/{postId}/reactions', [ReactionController::class, 'getReactions']);
Route::get('/categories', [CategoryController::class, 'index']);


Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);


    Route::post('/posts/{postId}/comments', [CommentController::class, 'store']);
    Route::post('/posts/{postId}/reactions', [ReactionController::class, 'store']);


    Route::get('/user', fn(Request $request) => $request->user());


    Route::post('/update-profile', function (Request $request) {
        $data = $request->validate([
            'username'      => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'bio'           => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|max:2048',
        ]);


        $user = $request->user();
        $user->username = $data['username'];
        $user->email    = $data['email'];
        $user->bio      = $data['bio'] ?? null;


        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $path = $request->file('profile_photo')
                ->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }


        $user->save();


        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => [
                'username'          => $user->username,
                'email'             => $user->email,
                'bio'               => $user->bio,
                'profile_photo_url' => $user->profile_photo_url,
            ],
        ]);
    });

    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // Admin users management
    Route::get('/users', function () {
        return \App\Models\User::select('id', 'username', 'email', 'is_admin')->get();
    });

    Route::post('/admin/users', function (Request $request) {
        $admin = $request->user();
        if (!$admin->is_admin) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'username' => 'required|string|min:7|unique:users,username|regex:/^[A-Za-z0-9_]+$/',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean'
        ]);

        $user = \App\Models\User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false
        ]);

        return response()->json(['message' => 'User created', 'user' => $user], 201);
    });

    Route::patch('/admin/users/{id}/role', function (int $id, Request $request) {
        $admin = $request->user();
        if (!$admin->is_admin) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'is_admin' => 'required|boolean'
        ]);

        $user = \App\Models\User::findOrFail($id);

        if ($user->id === $admin->id) {
            return response()->json(['error' => 'Cannot change your own role'], 403);
        }

        $user->is_admin = $validated['is_admin'];
        $user->save();

        return response()->json(['message' => 'User role updated', 'user' => $user]);
    });

    Route::delete('/users/{id}', function (int $id, Request $request) {
        $admin = $request->user();
        if (!$admin->is_admin) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $user = \App\Models\User::findOrFail($id);

        if ($user->id === $admin->id) {
            return response()->json(['error' => 'Cannot delete yourself'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    });
});
