<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Welcome');
    }

    public function features(): Response
    {
        return Inertia::render('Features');
    }

    public function pricing(): Response
    {
        return Inertia::render('Pricing');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }
}
