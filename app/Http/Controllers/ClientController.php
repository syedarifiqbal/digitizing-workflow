<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'], function ($query, $status) {
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

        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Client created.');
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        return Inertia::render('Clients/Show', [
            'client' => $this->transformClient($client),
            'orders' => [],
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('Clients/Edit', [
            'client' => $this->transformClient($client),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $this->validateData($request);

        $client->update($data);

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function transformClient(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company,
            'notes' => $client->notes,
            'is_active' => $client->is_active,
            'created_at' => $client->created_at?->toDateTimeString(),
            'updated_at' => $client->updated_at?->toDateTimeString(),
        ];
    }
}
