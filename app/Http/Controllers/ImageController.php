<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ImageController extends Controller
{
    public function serveProfilePhoto($filename)
    {
        $path = 'profile_photos/' . $filename;

        if (Storage::disk('public')->exists($path)) {
            $fullPath = storage_path('app/public/' . $path);
            return Response::file($fullPath);
        }

        // Fallback to default avatar
        $defaultAvatarPath = public_path('images/default-avatar.png');
        if (file_exists($defaultAvatarPath)) {
            return Response::file($defaultAvatarPath);
        }

        abort(404);
    }

    public function servePostImage($filename)
    {
        $path = 'posts/' . $filename;

        if (Storage::disk('public')->exists($path)) {
            $fullPath = storage_path('app/public/' . $path);
            return Response::file($fullPath);
        }

        // Return 404 for missing post images (no default needed)
        abort(404);
    }
}
