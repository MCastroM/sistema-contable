<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el control de acceso real vive en el middleware 'auth'
    }

    public function rules(): array
    {
        // Al editar, el RUT puede repetirse consigo mismo (ignora su propio id)
        $empresaId = $this->route('empresa')?->id;

        return [
            'rut'          => ['required', 'string', 'max:12', 'regex:/^\d{1,8}-[\dkK]$/',
                                Rule::unique('empresas', 'rut')->ignore($empresaId)],
            'razon_social' => ['required', 'string', 'max:200'],
            'giro'         => ['nullable', 'string', 'max:200'],
            'direccion'    => ['nullable', 'string', 'max:200'],
            'comuna'       => ['nullable', 'string', 'max:100'],
            'email'        => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'rut.regex' => 'El RUT debe tener el formato 12345678-9 (sin puntos, con guion).',
            'rut.unique' => 'Ya existe una empresa registrada con ese RUT.',
        ];
    }
}
