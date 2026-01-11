<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        Lead::firstOrCreate(
            ['email' => $validated['email']],
            ['source' => 'newsletter']
        );

        return back()->with('success', 'Thank you for subscribing!');
    }
}
