<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Update the user's preferred language.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,id'],
        ]);

        $request->user()->update([
            'preferred_lang' => $validated['locale'],
        ]);

        return back();
    }
}
