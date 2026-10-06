<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CredicardException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $codigo,
        public readonly int $httpStatus,
        public readonly mixed $detalle = null,
    ) {
        parent::__construct($message);
    }

    /** Código "1" del código original: error de comunicación con el proveedor. */
    public static function conexion(string $detalle): self
    {
        return new self('No fue posible comunicarse con el proveedor.', '1', 502,
            config('app.debug') ? $detalle : null);
    }

    /** Código "2" del código original: el proveedor respondió con un mensaje (msg). */
    public static function proveedor(mixed $msg): self
    {
        return new self('El proveedor devolvió un mensaje.', '2', 422, $msg);
    }

    public static function respuestaInvalida(int $status): self
    {
        return new self('El proveedor devolvió una respuesta no válida.', '3', 502, ['status_proveedor' => $status]);
    }

    public static function configuracion(string $servicio): self
    {
        return new self("Falta configurar la URL o la apikey del servicio '$servicio' en el .env.", '4', 500);
    }

    public function render(): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'codigo'  => $this->codigo,
            'mensaje' => $this->getMessage(),
            'detalle' => $this->detalle,
        ], fn ($v) => $v !== null), $this->httpStatus);
    }
}
