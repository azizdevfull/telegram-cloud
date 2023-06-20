<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    public function redirect($shortUrl)
    {
        // Find the user with the given short URL
        $user = User::where('avatar_short_url', $shortUrl)->first();

        if ($user) {
            $fullUrl = $user->avatar_full_url;

            return redirect($fullUrl);
        }

        // Handle the case when the short URL doesn't exist or the user is not found
        abort(404);
    }
}
