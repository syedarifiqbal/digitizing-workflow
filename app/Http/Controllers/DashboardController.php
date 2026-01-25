<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect clients to their portal dashboard
        if ($user->hasRole('Client')) {
            return Inertia::location(route('client.dashboard'));
        }

        return Inertia::render('Dashboard');
    }
}
