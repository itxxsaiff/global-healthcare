<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** Account & security screen. Form posts go to Fortify's endpoints. */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }
}
