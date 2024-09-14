<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreateUserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

        ];

        if ($this->isMethod('PUT')) {
            $user = auth()->user();
                $rules['name'] = 'sometimes|string|max:255';
                $rules['email'] = 'sometimes|string|email|max:255|unique:users,email,' . auth()->user()->id;
                $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está sendo utilizado.',
            'password.required' => 'O campo senha é obrigatório para criação de usuário.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'image.image' => 'O arquivo deve ser uma imagem válida.',
            'image.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg, gif.',
            'image.max' => 'A imagem não pode ser maior que 2MB.',
        ];
    }
}
