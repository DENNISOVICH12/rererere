<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PedidoUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'mesa'   => 'sometimes|nullable|string|max:50',
            'estado' => 'sometimes|in:retenido,modificacion_solicitada,pendiente,en_entrega,preparando,listo,entregado,cancelado',

        ];
    }
}
