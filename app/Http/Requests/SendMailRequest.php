<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendMailRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'addressee' => 'required|email',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'message' => 'required|string|min:10',
            'asunto' => 'required|string',
            'token' => 'required|string',
            'secret_key' => 'required|string',
            'file' => 'nullable|file|max:2048',
        ];
    }

    /**
     * Custom Spanish validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder 50 caracteres.',

            'addressee.required' => 'El destinatario es obligatorio.',
            'addressee.email' => 'El destinatario debe ser un correo electrónico válido.',

            'email.email' => 'El correo electrónico debe ser válido.',

            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no debe exceder 50 caracteres.',

            'message.required' => 'El mensaje es obligatorio.',
            'message.string' => 'El mensaje debe ser una cadena de texto.',
            'message.min' => 'El mensaje debe tener al menos 10 caracteres.',

            'asunto.required' => 'El asunto es obligatorio.',
            'asunto.string' => 'El asunto debe ser una cadena de texto.',

            'token.required' => 'No se recibió el token del captcha.',

            'secret_key.required' => 'La clave secreta del captcha es obligatoria.',

            'file.file' => 'El archivo adjunto debe ser un archivo válido.',
            'file.max' => 'El archivo adjunto no debe superar los 2MB.',
        ];
    }

    /**
     * Override to return JSON when validation fails.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error en el formulario',
                'errors'  => $validator->errors(),
            ], 400)
        );
    }
}
