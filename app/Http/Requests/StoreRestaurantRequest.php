<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Informe um ponto existente (city_node_id) ou o nome de um novo
            // ponto (node_name), que será criado junto com o restaurante.
            'city_node_id' => ['required_without:node_name', 'nullable', 'integer', 'exists:city_nodes,id'],
            'node_name' => ['required_without:city_node_id', 'nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'cuisine' => ['required', 'string', 'max:100'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'price_range' => ['nullable', 'in:$,$$,$$$'],
        ];
    }
}
