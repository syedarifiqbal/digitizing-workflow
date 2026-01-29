<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'order_ids' => ['array'],
            'order_ids.*' => ['integer'],
            'order_notes' => ['array'],
            'order_notes.*' => ['nullable', 'string', 'max:500'],
            'custom_items' => ['array'],
            'custom_items.*.description' => ['required_with:custom_items.*.unit_price,custom_items.*.quantity', 'string', 'max:255'],
            'custom_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'custom_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_ids' => collect($this->input('order_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $orderCount = collect($this->input('order_ids', []))->filter()->count();
            $customCount = collect($this->input('custom_items', []))
                ->filter(function ($item) {
                    return filled($item['description'] ?? null);
                })
                ->count();

            if ($orderCount === 0 && $customCount === 0) {
                $validator->errors()->add('order_ids', 'Select at least one order or add a custom line item.');
            }
        });
    }
}
