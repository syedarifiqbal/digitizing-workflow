<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $filters = $request->only(['search', 'status']);

        $clients = Client::query()
            ->withExists(['users as has_pending_invitation' => fn ($q) => $q->whereNull('email_verified_at')])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('is_active', $status==='active');
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? 'all',
            ],
            'clients' => [
                'data' => $clients->through(fn (Client $client) => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'company' => $client->company,
                    'is_active' => $client->is_active,
                    'has_pending_invitation' => (bool) $client->has_pending_invitation,
                    'created_at' => $client->created_at?->toDateTimeString(),
                ]),
                'links' => $clients->linkCollection(),
                'meta' => [
                    'total' => $clients->total(),
                    'from' => $clients->firstItem(),
                    'to' => $clients->lastItem(),
                    'per_page' => $clients->perPage(),
                ],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Clients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $this->validateData($request);
        $emails = $data['emails'] ?? [];
        unset($data['emails']);

        $client = Client::create($data);
        $this->syncClientEmails($client, $emails);

        return redirect()->route('clients.index')->with('success', 'Client created.');
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $client->load('emails');

        return Inertia::render('Clients/Show', [
            'client' => $this->transformClient($client),
            'orders' => [],
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        $client->load('emails');

        return Inertia::render('Clients/Edit', [
            'client' => $this->transformClient($client),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $this->validateData($request);
        $emails = $data['emails'] ?? [];
        unset($data['emails']);

        $client->update($data);
        $this->syncClientEmails($client, $emails);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }

    public function toggleStatus(Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update([
            'is_active' => $client->isActive() ? 0: 1,
        ]);

        return back()->with('success', 'Client status updated.');
    }

    public function resendInvitation(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $user = User::where('client_id', $client->id)->whereNull('email_verified_at')->first();

        if (! $user) {
            return back()->with('error', 'No pending invitation found for this client.');
        }

        $mailerName = \App\Support\TenantMailer::configureForTenant($request->user()->tenant);
        if ($mailerName) {
            config(['mail.default' => $mailerName]);
        }

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', 'Invitation resent to ' . $user->email . '.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $clients = Client::whereIn('id', $validated['ids'])->get();

        foreach ($clients as $client) {
            $this->authorize('delete', $client);
            $client->delete();
        }

        return back()->with('success', 'Selected clients deleted.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'company'  => ['nullable', 'string', 'max:255'],
            'notes'    => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'permanent_instructions'                    => ['nullable', 'array'],
            'permanent_instructions.special_offer_note' => ['nullable', 'string'],
            'permanent_instructions.price_instructions' => ['nullable', 'string'],
            'permanent_instructions.for_digitizer'      => ['nullable', 'string'],
            'permanent_instructions.appreciation_bonus' => ['nullable', 'numeric'],
            'permanent_instructions.custom'             => ['nullable', 'array'],
            'permanent_instructions.custom.*.key'       => ['required', 'string', 'max:100'],
            'permanent_instructions.custom.*.value'     => ['nullable', 'string'],
            'emails'           => ['nullable', 'array'],
            'emails.*.email'   => ['required', 'email', 'max:255'],
            'emails.*.label'   => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function syncClientEmails(Client $client, array $emails): void
    {
        $client->emails()->delete();

        foreach ($emails as $index => $emailData) {
            $client->emails()->create([
                'tenant_id'  => $client->tenant_id,
                'email'      => $emailData['email'],
                'label'      => $emailData['label'] ?? null,
                'is_primary' => false,
                'sort_order' => $index,
            ]);
        }
    }

    private function transformClient(Client $client): array
    {
        return [
            'id'                     => $client->id,
            'name'                   => $client->name,
            'email'                  => $client->email,
            'phone'                  => $client->phone,
            'company'                => $client->company,
            'notes'                  => $client->notes,
            'is_active'              => $client->is_active,
            'permanent_instructions' => $client->permanent_instructions ?? [],
            'emails'                 => $client->relationLoaded('emails')
                ? $client->emails->map(fn ($e) => [
                    'email' => $e->email,
                    'label' => $e->label,
                ])->values()->all()
                : [],
            'created_at'             => $client->created_at?->toDateTimeString(),
            'updated_at'             => $client->updated_at?->toDateTimeString(),
        ];
    }
}
