<?php

namespace App\Http\Requests\Trips;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTripRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'base_currency' => ['required', Rule::enum(Currency::class)],
            // Required only when the creator has no name yet; otherwise their
            // account name is used for their participant.
            'display_name' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->user()->name === null)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * The name to use for the creator's participant — provided value, falling
     * back to their account name. Validation guarantees this resolves non-null.
     */
    public function displayName(): string
    {
        return $this->input('display_name') ?? $this->user()->name;
    }
}
