<?php

namespace App\Http\Controllers\Api;

use App\Actions\Integrations\ProcessIntakeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IntakeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function __invoke(IntakeRequest $request, ProcessIntakeAction $action): JsonResponse
    {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $request->attributes->get('apiTenant');

        if (! $tenant || ! $tenant->getSetting('api_enabled', false)) {
            return response()->json([
                'message' => 'API access is disabled for this tenant.',
            ], 403);
        }

        $result = $action->execute($tenant, $request->validated());

        return response()->json($result, 201);
    }
}

