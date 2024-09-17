<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatecreatePostRequest extends FormRequest
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
        $rules = [
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|max:10000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

        ];

        if ($this->isMethod('PUT')) {
            $user = auth()->user();
            $rules['title'] = 'sometimes||string|min:3|max:255';
            $rules['content'] = 'sometimes|max:10000';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'O campo título é obrigatório.',
            'content.required' => 'O campo conteúdo é obrigatório.',
            'image.image' => 'O arquivo deve ser uma imagem válida.',
            'image.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg, gif.',
            'image.max' => 'A imagem não pode ser maior que 2MB.',
        ];
    }
}
