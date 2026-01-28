<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private array $roles = ['Admin', 'Manager', 'Designer', 'Client', 'Sales'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'role', 'status']);

        $users = User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with(['roles', 'client'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($query, $role) {
                if ($role !== 'all') {
                    $query->whereHas('roles', fn ($q) => $q->where('name', $role));
                }
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('is_active', $status==='active');
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? 'all',
                'status' => $filters['status'] ?? 'all',
            ],
            'roles' => $this->roles,
            'users' => [
                'data' => $users->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $user->roles->pluck('name')->first(),
                    'client' => $user->client ? [
                        'id' => $user->client->id,
                        'name' => $user->client->name,
                    ] : null,
                    'created_at' => $user->created_at?->toDateTimeString(),
                ]),
                'links' => $users->linkCollection(),
                'meta' => [
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'per_page' => $users->perPage(),
                ],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => $this->roles,
            'clients' => $this->clientOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validateUser($request);

        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['status'] ?? 1,
            'password' => Str::random(32),
        ]);

        $user->syncRoles([$data['role']]);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('users.index')->with('success', 'User invited.');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('view', $user);
        $user->loadMissing('roles');

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->roles->pluck('name')->first(),
                'client_id' => $user->client_id,
            ],
            'roles' => $this->roles,
            'clients' => $this->clientOptions($request),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $user->loadMissing('roles');

        $data = $this->validateUser($request, $user);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['status'] ?? $user->is_active,
            'client_id' => $data['client_id'] ?? null,
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $users = User::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($users as $user) {
            $this->authorize('delete', $user);
            $user->delete();
        }

        return back()->with('success', 'Selected users deleted.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $tenantId = $request->user()->tenant_id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['1', '2'])],
            'role' => ['required', Rule::in($this->roles)],
            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
        ];

        $validated = $request->validate($rules);

        if (($validated['role'] ?? $user?->roles->pluck('name')->first()) === 'Client') {
            $request->validate([
                'client_id' => [
                    'required',
                    Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
                ],
            ]);
        } else {
            $validated['client_id'] = null;
        }

        return $validated;
    }

    private function clientOptions(Request $request)
    {
        return Client::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
