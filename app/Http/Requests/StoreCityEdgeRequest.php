<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityEdgeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_node_id' => ['required', 'integer', 'exists:city_nodes,id'],
            'to_node_id' => ['required', 'integer', 'exists:city_nodes,id', 'different:from_node_id'],
            'distance_km' => ['required', 'numeric', 'min:0.01'],
            'bidirectional' => ['boolean'],
        ];
    }
}
