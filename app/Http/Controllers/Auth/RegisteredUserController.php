<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use Telegram\Bot\FileUpload\InputFile;
use App\Providers\RouteServiceProvider;
use Telegram\Bot\Laravel\Facades\Telegram;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'avatar' => ['nullable', 'image'], // Add this line
        ]);
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
    
        if ($request->hasFile('avatar')) {
            $document = $request->file('avatar');
            $response = Telegram::sendDocument([
                'chat_id' => '@tg_laravel', // Replace with the correct channel username
                'document' => InputFile::createFromContents(file_get_contents($document), $document->getClientOriginalName()),
            ]);
            $messageId = $response;
            $file = Telegram::getFile(['file_id' => $messageId->document['file_id']]);
            $filePath = $file['file_path'];
            $fileUrl = 'https://api.telegram.org/file/bot' . Telegram::getAccessToken() . '/' . $filePath;
    
            // Generate a unique identifier for the file URL
            $shortUrl = $this->generateShortUrl();
    
            // Save the full URL and the short URL to the user's avatar field
            $user->avatar = config('app.url') . '/shorts' .  '/' . $shortUrl;
            $user->avatar_full_url = $fileUrl;
            $user->avatar_short_url = $shortUrl;
    
            $user->save();
        }
    
        event(new Registered($user));
    
        Auth::login($user);
    
        return redirect(RouteServiceProvider::HOME);
    }
    
    private function generateShortUrl(): string
    {
        // Generate a unique identifier, such as a random string or a hash
        // You can customize this logic based on your requirements
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $shortUrl = '';
        $length = 6;
    
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = rand(0, strlen($characters) - 1);
            $shortUrl .= $characters[$randomIndex];
        }
    
        // Check if the generated identifier already exists in the database
        // You may need to modify this logic depending on how you store the URLs
        while (User::where('avatar', $shortUrl)->exists()) {
            $shortUrl = $this->generateShortUrl();
        }
    
        return $shortUrl;
    }
    
    
}
