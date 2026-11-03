<?php

namespace App\Http\Requests;

use App\Actions\Clients\SetClientPortalLoginAction;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateClientPortalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    public function rules(): array
    {
        /** @var Client $client */
        $client = $this->route('client');
        $existingUserId = User::where('client_id', $client->id)->orderBy('id')->value('id');

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($client, $existingUserId) {
                    $taken = User::withTrashed()
                        ->where('tenant_id', $client->tenant_id)
                        ->where('email', $value)
                        ->when($existingUserId, fn ($q) => $q->where('id', '!=', $existingUserId))
                        ->exists();

                    if ($taken && ! SetClientPortalLoginAction::findReusableUser($client, $value)) {
                        $fail('This email already belongs to a team member or another client. Use a different email for this client\'s login.');
                    }
                },
            ],
            'password' => [
                Rule::requiredIf(! $existingUserId || $this->boolean('send_email')),
                'nullable',
                'string',
                Password::min(8),
            ],
            'send_email' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Enter a password so it can be set and emailed to the client.',
        ];
    }
}
