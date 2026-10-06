<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EstadoCuentaTest extends TestCase
{
    private array $headers = ['X-API-KEY' => 'clave-pruebas', 'Accept' => 'application/json'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'credicard.client_api_key'   => 'clave-pruebas',
            'credicard.meses.url'        => 'https://proveedor.test/edcws/info',
            'credicard.meses.api_key'    => 'key-meses',
            'credicard.movimientos.url'  => 'https://proveedor.test/edcws/edc',
            'credicard.movimientos.api_key' => 'key-movs',
        ]);
    }

    public function test_rechaza_sin_api_key(): void
    {
        $this->postJson('/api/v1/edc/meses', ['tarjeta' => '4111111111111111'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'codigo' => 'NO_AUTORIZADO']);
    }

    public function test_meses_envia_get_con_cuerpo_json_y_apikey(): void
    {
        Http::fake(['proveedor.test/*' => Http::response([['mes' => '09', 'ano' => '2026']])]);

        $this->postJson('/api/v1/edc/meses', ['tarjeta' => '4111111111111111'], $this->headers)
            ->assertOk()
            ->assertJson(['success' => true, 'codigo' => '0', 'data' => [['mes' => '09', 'ano' => '2026']]]);

        Http::assertSent(fn (Request $r) => $r->method() === 'GET'
            && $r->url() === 'https://proveedor.test/edcws/info'
            && $r->header('apikey')[0] === 'key-meses'
            && $r->data() === ['cod1' => '4111111111111111']);
    }

    public function test_movimientos_separa_la_fecha(): void
    {
        Http::fake(['proveedor.test/*' => Http::response(['movimientos' => []])]);

        $this->postJson('/api/v1/edc/movimientos',
            ['tarjeta' => '4111111111111111', 'fecha' => '2026-09-05'], $this->headers)
            ->assertOk()
            ->assertJsonPath('data.movimientos', []);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://proveedor.test/edcws/edc'
            && $r->header('apikey')[0] === 'key-movs'
            && $r->data() === ['cod1' => '4111111111111111', 'ano1' => '2026', 'mes1' => '09', 'dia1' => '05']);
    }

    public function test_msg_del_proveedor_devuelve_codigo_2(): void
    {
        Http::fake(['proveedor.test/*' => Http::response(['msg' => 'Tarjeta no existe'])]);

        $this->postJson('/api/v1/edc/meses', ['tarjeta' => '4111111111111111'], $this->headers)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'codigo' => '2', 'detalle' => 'Tarjeta no existe']);
    }

    public function test_error_de_conexion_devuelve_codigo_1(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->postJson('/api/v1/edc/meses', ['tarjeta' => '4111111111111111'], $this->headers)
            ->assertStatus(502)
            ->assertJson(['success' => false, 'codigo' => '1']);
    }

    public function test_valida_tarjeta_y_fecha(): void
    {
        $this->postJson('/api/v1/edc/movimientos', ['tarjeta' => '41AB', 'fecha' => '30/09/2026'], $this->headers)
            ->assertStatus(422)
            ->assertJson(['codigo' => 'VALIDACION'])
            ->assertJsonValidationErrors(['tarjeta', 'fecha'], 'errores');
    }
}
