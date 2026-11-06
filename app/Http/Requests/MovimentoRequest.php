<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentoRequest extends FormRequest
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
            'descrizione' => 'required|string|max:255',
            'importo' => 'required|numeric|min:0',
            'entratauscita' => 'required|boolean',
            // La categoria deve appartenere all'entità selezionata
            'categoria' => [
                'required', 'integer',
                Rule::exists('categorie', 'cat_id')->where('entity_id', session('selected_entity_id')),
            ],
            'data' => 'nullable|date',
            'estero_senza_iva' => 'nullable|boolean',
        ];
    }
}
