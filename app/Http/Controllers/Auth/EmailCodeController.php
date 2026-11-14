<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailCodeController extends Controller
{
    public function __construct(private EmailCodeService $codes) {}

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $request->validate(['code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/']], [
            'code.required' => 'Enter the 6-digit code from the email.',
            'code.regex' => 'The code is 6 digits, numbers only.',
        ]);

        $result = $this->codes->verify($user, $request->string('code'));

        if ($result === EmailCodeService::OK) {
            return redirect()->intended(route('dashboard'))->with('success', 'Email confirmed. Welcome to '.config('app.name').'!');
        }

        $left = $this->codes->attemptsLeft($user);

        return back()->withErrors(['code' => match ($result) {
            EmailCodeService::INVALID => "That code isn't right. Check the email and try again ({$left} ".str('try')->plural($left).' left).',
            EmailCodeService::EXPIRED => 'That code has expired. Send yourself a new one below.',
            EmailCodeService::LOCKED => 'Too many wrong tries. Send yourself a new code below.',
            default => 'Please send yourself a new code below.',
        }]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $wait = $this->codes->secondsUntilResend($user);
        if ($wait > 0) {
            return back()->with('warning', "Please wait {$wait} seconds before asking for another code.");
        }

        return $this->codes->send($user)
            ? back()->with('success', 'A new code is on its way to '.$user->email.'.')
            : back()->with('error', "We couldn't send the email just now. Please try again in a minute.");
    }
}
