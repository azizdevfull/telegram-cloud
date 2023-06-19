<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
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
            $user->avatar = $fileUrl;
            $user->save();
        }
    
        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
