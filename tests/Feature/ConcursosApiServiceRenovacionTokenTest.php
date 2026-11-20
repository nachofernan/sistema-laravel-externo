<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Services\ConcursosApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Renovación del JWT ante un 401 de la API interna.
 *
 * Sin RefreshDatabase a propósito: no toca la base (phpunit.xml no aísla la base de tests).
 * La renovación se hace con un doble de AuthController, que en producción consulta users.
 */
class ConcursosApiServiceRenovacionTokenTest extends TestCase
{
    private const URL = 'http://api-interna.test';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('PLATAFORMA_API_URL='.self::URL);
        $_ENV['PLATAFORMA_API_URL'] = self::URL;
        $_SERVER['PLATAFORMA_API_URL'] = self::URL;
    }

    private function servicio(): ConcursosApiService
    {
        session(['jwt_token' => 'token-vencido']);

        return new ConcursosApiService(session('jwt_token'), '20123456789');
    }

    private function respuestaVencido()
    {
        return Http::response(['error' => 'Token inválido: Expired token'], 401);
    }

    #[Test]
    public function con_token_valido_cambia_la_intencion_sin_renovar(): void
    {
        $this->mock(AuthController::class)->shouldNotReceive('getNewToken');
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->assertTrue($this->servicio()->cambiarIntencion(145, 3));
        Http::assertSentCount(1);
    }

    #[Test]
    public function con_token_vencido_renueva_y_reintenta_la_intencion_una_vez(): void
    {
        $this->mock(AuthController::class)->shouldReceive('getNewToken')->once()->andReturn('token-nuevo');
        Http::fakeSequence()
            ->pushResponse($this->respuestaVencido())
            ->push(['success' => true]);

        $this->assertTrue($this->servicio()->cambiarIntencion(145, 3));

        Http::assertSentCount(2);
        $pedidos = Http::recorded()->map(fn ($par) => $par[0]);
        $this->assertSame('Bearer token-vencido', $pedidos[0]->header('Authorization')[0]);
        $this->assertSame('Bearer token-nuevo', $pedidos[1]->header('Authorization')[0]);
        $this->assertSame('PATCH', $pedidos[1]->method());
        $this->assertSame(['intencion' => 3], $pedidos[1]->data());
        $this->assertSame('token-nuevo', session('jwt_token'));
    }

    #[Test]
    public function si_la_renovacion_falla_devuelve_el_error_sin_reintentar(): void
    {
        $this->mock(AuthController::class)->shouldReceive('getNewToken')->once()
            ->andThrow(new \Exception('Error temporal del sistema. Intente nuevamente.'));
        Http::fake(['*' => $this->respuestaVencido()]);

        $this->assertFalse($this->servicio()->cambiarIntencion(145, 3));

        Http::assertSentCount(1);
        $this->assertSame('token-vencido', session('jwt_token'));
    }

    #[Test]
    public function si_el_reintento_tambien_da_401_no_entra_en_bucle(): void
    {
        $this->mock(AuthController::class)->shouldReceive('getNewToken')->once()->andReturn('token-nuevo');
        Http::fake(['*' => $this->respuestaVencido()]);

        $this->assertFalse($this->servicio()->cambiarIntencion(145, 3));
        Http::assertSentCount(2);
    }

    #[Test]
    public function si_otra_instancia_ya_renovo_el_token_lo_reutiliza_sin_pedir_otro(): void
    {
        $this->mock(AuthController::class)->shouldNotReceive('getNewToken');
        Http::fakeSequence()
            ->pushResponse($this->respuestaVencido())
            ->push(['data' => [['id' => 145]]]);

        $servicio = $this->servicio();
        session(['jwt_token' => 'token-renovado-por-otro']);

        $this->assertSame([['id' => 145]], $servicio->getConcursos());
        Http::assertSent(fn (Request $pedido) => $pedido->header('Authorization')[0] === 'Bearer token-renovado-por-otro');
    }

    #[Test]
    public function la_subida_de_documento_reintenta_con_el_archivo_completo(): void
    {
        $this->mock(AuthController::class)->shouldReceive('getNewToken')->once()->andReturn('token-nuevo');
        Http::fakeSequence()
            ->pushResponse($this->respuestaVencido())
            ->push(['data' => ['id' => 1]]);

        $archivo = \Illuminate\Http\UploadedFile::fake()->createWithContent('oferta.pdf', 'contenido-pdf');

        $this->assertNotNull($this->servicio()->subirDocumentoConcurso(145, $archivo, 50));

        $reintento = Http::recorded()->map(fn ($par) => $par[0])[1];
        $this->assertStringContainsString('contenido-pdf', $reintento->body());
        $this->assertStringContainsString('oferta.pdf', $reintento->body());
    }
}
