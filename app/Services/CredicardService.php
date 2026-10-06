<?php

namespace App\Services;

use App\Exceptions\CredicardException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente del proveedor Credicard (estado de cuenta de tarjeta de crédito).
 *
 * Reemplaza las funciones obtener_meses_credicard() y obtener_movimientos_credicard()
 * que ejecutaban "curl -k -X GET -H apikey ... -d @archivo.json" con shell_exec.
 * Aquí se usa el cliente HTTP de Laravel: misma petición (GET con cuerpo JSON y
 * header apikey), sin archivos temporales ni llamadas a la consola.
 */
class CredicardService
{
    /**
     * Obtiene los meses disponibles del estado de cuenta de una tarjeta.
     * (antes: obtener_meses_credicard($tarjeta))
     */
    public function obtenerMeses(string $tarjeta): mixed
    {
        return $this->llamar('meses', [
            'cod1' => $tarjeta,
        ]);
    }

    /**
     * Obtiene los movimientos del estado de cuenta de una tarjeta para una fecha (Y-m-d).
     * (antes: obtener_movimientos_credicard($tarjeta, $fecha))
     */
    public function obtenerMovimientos(string $tarjeta, string $fecha): mixed
    {
        [$anio, $mes, $dia] = explode('-', $fecha);

        return $this->llamar('movimientos', [
            'cod1' => $tarjeta,
            'ano1' => $anio,
            'mes1' => $mes,
            'dia1' => $dia,
        ]);
    }

    /**
     * @throws CredicardException
     */
    private function llamar(string $servicio, array $payload): mixed
    {
        $url    = config("credicard.$servicio.url");
        $apiKey = config("credicard.$servicio.api_key");

        if (empty($url) || empty($apiKey)) {
            throw CredicardException::configuracion($servicio);
        }

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->acceptJson()
                ->timeout(config('credicard.timeout'))
                ->withOptions(['verify' => config('credicard.verificar_ssl')])
                // El proveedor espera GET con el JSON en el cuerpo (igual que curl -X GET -d).
                ->withBody(json_encode($payload), 'application/json')
                ->send('GET', $url);
        } catch (ConnectionException $e) {
            // Antes: return "1"; // Error en ejecución de cURL
            Log::error("Credicard [$servicio] sin conexión", [
                'tarjeta' => $this->enmascarar($payload['cod1']),
                'error'   => $e->getMessage(),
            ]);

            throw CredicardException::conexion($e->getMessage());
        }

        $resultado = $response->json();

        if ($resultado === null) {
            Log::error("Credicard [$servicio] respuesta no JSON", [
                'tarjeta' => $this->enmascarar($payload['cod1']),
                'status'  => $response->status(),
                'body'    => mb_substr($response->body(), 0, 500),
            ]);

            throw CredicardException::respuestaInvalida($response->status());
        }

        // Antes: if (isset($resultado['msg']) && !empty($resultado['msg'])) return "2";
        if (is_array($resultado) && ! empty($resultado['msg'])) {
            throw CredicardException::proveedor($resultado['msg']);
        }

        return $resultado;
    }

    private function enmascarar(string $tarjeta): string
    {
        return str_repeat('*', max(strlen($tarjeta) - 4, 0)).substr($tarjeta, -4);
    }
}
