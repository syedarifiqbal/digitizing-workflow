<?php

namespace App\Http\Controllers\Api;

use App\Actions\Integrations\ProcessIntakeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IntakeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    /**
     * Create a client + order intake
     *
     * Use this endpoint to create a client (if needed) and drop a new order into the workflow.
     * The request will be validated against the tenant's settings and the order will start in the RECEIVED state.
     *
     * @group Intake API
     * @authenticated
     *
     * @header Authorization string required Example: Bearer YOUR_API_KEY
     *
     * @bodyParam client.name string required Client contact name. Example: Jane Client
     * @bodyParam client.email string required Client email address. Example: client@example.com
     * @bodyParam client.company string Client company name.
     * @bodyParam client.phone string Client phone number.
     * @bodyParam order.title string required Order title. Example: Back patch digitizing
     * @bodyParam order.instructions string Additional instructions for the order.
     * @bodyParam order.priority string normal The priority (`normal` or `rush`).
     * @bodyParam order.type string digitizing The order type (`digitizing`, `vector`, `patch`).
     * @bodyParam order.price_amount number Price attached to the order.
     * @bodyParam order.currency string Currency code (defaults to tenant currency). Example: USD
     * @bodyParam order.is_quote boolean Whether this request should be treated as a quote.
     * @bodyParam order.source string Source identifier for reporting. Example: wordpress-form
     *
     * @response 201 scenario="Successful" {"message":"Order created successfully.","order_id":123,"order_number":"ORD-00123","client_id":45,"client_user_created":true}
     * @response 401 {"message":"Missing API key."}
     * @response 403 {"message":"API access is disabled for this tenant."}
     */
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
