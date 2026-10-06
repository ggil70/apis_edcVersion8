<?php

namespace App\Http\Controllers;

use App\Services\CredicardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadoCuentaController extends Controller
{
    public function __construct(private readonly CredicardService $credicard)
    {
    }

    /**
     * POST /api/v1/edc/meses
     * Body: { "tarjeta": "4111111111111111" }
     */
    public function meses(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tarjeta' => ['required', 'string', 'regex:/^\d{13,19}$/'],
        ], $this->mensajes());

        return $this->ok($this->credicard->obtenerMeses($datos['tarjeta']));
    }

    /**
     * POST /api/v1/edc/movimientos
     * Body: { "tarjeta": "4111111111111111", "fecha": "2026-09-30" }
     */
    public function movimientos(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tarjeta' => ['required', 'string', 'regex:/^\d{13,19}$/'],
            'fecha'   => ['required', 'date_format:Y-m-d'],
        ], $this->mensajes());

        return $this->ok($this->credicard->obtenerMovimientos($datos['tarjeta'], $datos['fecha']));
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'codigo'  => '0',
            'mensaje' => 'Consulta exitosa.',
            'data'    => $data,
        ]);
    }

    private function mensajes(): array
    {
        return [
            'tarjeta.required'    => 'El número de tarjeta es obligatorio.',
            'tarjeta.regex'       => 'La tarjeta debe contener solo dígitos (entre 13 y 19).',
            'fecha.required'      => 'La fecha es obligatoria.',
            'fecha.date_format'   => 'La fecha debe tener el formato AAAA-MM-DD (ej. 2026-09-30).',
        ];
    }
}
