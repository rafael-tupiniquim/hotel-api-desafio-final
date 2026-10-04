<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReserveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', 'exists:hotels,id'],
            // O quarto precisa pertencer ao hotel informado.
            'room_id' => [
                'required',
                'integer',
                Rule::exists('rooms', 'id')->where('hotel_id', $this->input('hotel_id')),
            ],
            'check_in' => ['required', 'date', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date', 'date_format:Y-m-d', 'after:check_in'],
            'total' => ['nullable', 'numeric', 'min:0'],

            'guests' => ['required', 'array', 'min:1'],
            'guests.*.name' => ['required', 'string', 'max:255'],
            'guests.*.last_name' => ['nullable', 'string', 'max:255'],
            'guests.*.phone' => ['nullable', 'string', 'max:30'],

            'dailies' => ['nullable', 'array'],
            'dailies.*.date' => ['required_with:dailies', 'date', 'date_format:Y-m-d'],
            'dailies.*.value' => ['required_with:dailies', 'numeric', 'min:0'],

            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'integer'],
            'payments.*.value' => ['required_with:payments', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'O quarto informado não existe ou não pertence ao hotel informado.',
            'check_out.after' => 'A data de check-out deve ser posterior à data de check-in.',
        ];
    }
}
