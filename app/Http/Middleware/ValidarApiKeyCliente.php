<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege la API: el cliente (Postman u otro aplicativo) debe enviar el header
 * X-API-KEY con el valor de API_EDC_CLIENT_KEY definido en el .env.
 */
class ValidarApiKeyCliente
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperada = config('credicard.client_api_key');

        if (! empty($esperada) && ! hash_equals($esperada, (string) $request->header('X-API-KEY'))) {
            return response()->json([
                'success' => false,
                'codigo'  => 'NO_AUTORIZADO',
                'mensaje' => 'X-API-KEY inválida o ausente.',
            ], 401);
        }

        return $next($request);
    }
}
