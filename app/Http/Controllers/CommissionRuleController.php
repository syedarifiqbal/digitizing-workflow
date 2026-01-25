<?php

namespace App\Http\Controllers;

use App\Enums\CommissionType;
use App\Enums\RoleType;
use App\Models\CommissionRule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommissionRuleController extends Controller
{
    public function salesIndex(Request $request): Response
    {
        return $this->index($request, RoleType::SALES, 'Sales');
    }

    public function designerIndex(Request $request): Response
    {
        return $this->index($request, RoleType::DESIGNER, 'Designer');
    }

    private function index(Request $request, RoleType $roleType, string $roleName): Response
    {
        $this->authorize('viewAny', CommissionRule::class);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $rules = CommissionRule::query()
            ->where('tenant_id', $tenantId)
            ->where('role_type', $roleType)
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (CommissionRule $rule) => [
                'id' => $rule->id,
                'user_id' => $rule->user_id,
                'user' => [
                    'id' => $rule->user->id,
                    'name' => $rule->user->name,
                    'email' => $rule->user->email,
                ],
                'role_type' => $rule->role_type->value,
                'type' => $rule->type->value,
                'fixed_amount' => $rule->fixed_amount,
                'percent_rate' => $rule->percent_rate,
                'currency' => $rule->currency,
                'is_active' => $rule->is_active,
            ]);

        $availableUsers = User::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]);

        $existingUserIds = $rules->pluck('user_id')->toArray();

        return Inertia::render('CommissionRules/Index', [
            'rules' => $rules,
            'availableUsers' => $availableUsers,
            'existingUserIds' => $existingUserIds,
            'roleType' => $roleType->value,
            'roleLabel' => $roleType->label(),
            'typeOptions' => collect(CommissionType::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => ucfirst($case->value),
            ]),
            'currency' => $user->tenant->getSetting('currency', 'USD'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CommissionRule::class);

        $user = $request->user();

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('tenant_id', $user->tenant_id),
            ],
            'role_type' => ['required', Rule::in(['sales', 'designer'])],
            'type' => ['required', Rule::in(array_map(fn ($c) => $c->value, CommissionType::cases()))],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percent_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ]);

        // Check uniqueness: one rule per user per role type
        $exists = CommissionRule::where('tenant_id', $user->tenant_id)
            ->where('user_id', $validated['user_id'])
            ->where('role_type', $validated['role_type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['user_id' => 'This user already has a commission rule for this role.']);
        }

        CommissionRule::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $validated['user_id'],
            'role_type' => $validated['role_type'],
            'type' => $validated['type'],
            'fixed_amount' => $validated['fixed_amount'] ?? null,
            'percent_rate' => $validated['percent_rate'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $roleLabel = RoleType::from($validated['role_type'])->label();
        return back()->with('success', "{$roleLabel} rule created.");
    }

    public function update(Request $request, CommissionRule $commissionRule): RedirectResponse
    {
        $this->authorize('update', $commissionRule);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_map(fn ($c) => $c->value, CommissionType::cases()))],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percent_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ]);

        $commissionRule->update([
            'type' => $validated['type'],
            'fixed_amount' => $validated['fixed_amount'] ?? null,
            'percent_rate' => $validated['percent_rate'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Commission rule updated.');
    }

    public function destroy(Request $request, CommissionRule $commissionRule): RedirectResponse
    {
        $this->authorize('delete', $commissionRule);

        $commissionRule->delete();

        return back()->with('success', 'Commission rule deleted.');
    }
}
