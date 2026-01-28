<?php

namespace App\Http\Controllers;

use App\Actions\Integrations\GenerateApiKeyAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function store(Request $request, GenerateApiKeyAction $generator): RedirectResponse
    {
        abort_if(! $request->user()?->isAdmin(), 403);

        $tenant = $request->user()->tenant;

        $apiKey = $generator->execute($tenant);

        return back()->with([
            'success' => 'New API key generated. Copy it now—you will not see it again.',
            'api_key_plain' => $apiKey,
        ]);
    }
}

