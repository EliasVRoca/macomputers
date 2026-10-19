<?php

namespace App\Http\Requests\Usuarios;

use App\Http\Requests\Concerns\SaneaTexto;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de un usuario (RF2 · CU1).
 *
 * Sanea el texto libre, valida unicidad y contraseña, y bloquea que un usuario
 * se desactive a sí mismo desde el formulario.
 */
class GuardarUsuarioRequest extends FormRequest
{
    use SaneaTexto;

    public function authorize(): bool
    {
        return $this->user()?->tienePermiso('usuarios') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $usuario = $usuario instanceof User ? $usuario : null;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20', Rule::unique('personas', 'ci')->ignore($usuario?->persona_id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($usuario?->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ci' => 'C.I.',
            'role_id' => 'rol',
            'sucursal_id' => 'sucursal',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => $this->limpiarTexto($this->input('nombre')),
            'ci' => $this->limpiarTexto($this->input('ci')),
            'telefono' => $this->limpiarTexto($this->input('telefono')),
            'direccion' => $this->limpiarTexto($this->input('direccion')),
            'username' => $this->limpiarTexto($this->input('username')),
            'email' => $this->limpiarTexto($this->input('email')),
            'activo' => $this->boolean('activo'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validador): void {
            $usuario = $this->route('usuario');

            if (! $usuario instanceof User || ! $this->user()?->is($usuario)) {
                return;
            }

            if (! $this->boolean('activo')) {
                $validador->errors()->add('activo', 'No puede desactivar su propia cuenta.');
            }
        });
    }
}
