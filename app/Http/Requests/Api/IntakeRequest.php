<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $this->attributes->get('apiTenant');
        $maxKilobytes = (int) ($tenant?->getSetting('max_upload_mb', 25) ?? 25) * 1024;
        $allowedExtensions = collect(explode(',', (string) $tenant?->getSetting('allowed_input_extensions', 'jpg,jpeg,png,pdf')))
            ->map(fn ($ext) => strtolower(trim($ext)))
            ->filter()
            ->values()
            ->all();

        return [
            'client.name' => ['required', 'string', 'max:255'],
            'client.email' => ['required', 'string', 'email', 'max:255'],
            'client.company' => ['nullable', 'string', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:100'],
            'client.notes' => ['nullable', 'string'],

            'order.title' => ['required', 'string', 'max:255'],
            'order.instructions' => ['nullable', 'string'],
            'order.priority' => ['nullable', 'in:normal,rush'],
            'order.type' => ['nullable', 'in:digitizing,vector,patch'],
            'order.is_quote' => ['nullable', 'boolean'],
            'order.due_at' => ['nullable', 'date'],
            'order.price_amount' => ['nullable', 'numeric', 'min:0'],
            'order.currency' => ['nullable', 'string', 'size:3'],
            'order.source' => ['nullable', 'string', 'max:255'],

            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:'.$maxKilobytes,
                function (string $attribute, mixed $value, \Closure $fail) use ($allowedExtensions) {
                    if (! in_array(strtolower($value->getClientOriginalExtension()), $allowedExtensions, true)) {
                        $fail('Each file must be of type: '.implode(', ', $allowedExtensions).'.');
                    }
                },
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        data_set($validated, 'order.is_quote', (bool) data_get($validated, 'order.is_quote', false));

        return $validated;
    }
}

