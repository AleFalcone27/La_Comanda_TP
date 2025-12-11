<?php 

// php -S localhost:8000 -t app 

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require_once './requires.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

// Hooks login

$app->group('/login', function (RouteCollectorProxy $group) {
  $group->post('[/]', \JwtControles::class . ':TokenLogin');
});

// Hooks usuario
$app->group('/usuario', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioControles::class . ':TraerTodos');
    $group->post('[/]', \UsuarioControles::class . ':CargarUno');
    $group->put('/modificar', \UsuarioControles::class . ':ModificarUno');
    $group->get('/ingreso', \UsuarioControles::class . ':TraerFechas');
    $group->get('/operaciones', \UsuarioControles::class . ':obtenerOperaciones');
    $group->get('/operacionesSector', \UsuarioControles::class . ':obtenerOperacionesPorSector');
    $group->delete('/borrar/{id_usuario}', \UsuarioControles::class . ':BorrarUno');
  })->add(new RolMiddleware([ Roles::SOCIO->value ]));


// Hooks producto
$app->group('/producto', function (RouteCollectorProxy $group) {
  $group->get('[/]', \ProductoControles::class . ':TraerTodos');
  $group->post('[/]', \ProductoControles::class . ':CargarUno')->add(new RolMiddleware([Roles::SOCIO->value, Roles::COCINERO->value]));
  $group->get('/masVendido', \ProductoControles::class . ':TraerOrdenados')->add(new RolMiddleware([Roles::SOCIO->value,]));
  $group->put('/modificar', \ProductoControles::class . ':ModificarUno')->add(new RolMiddleware([Roles::SOCIO->value,]));
  $group->delete('/borrar/{id_producto}', \ProductoControles::class . ':BorrarUno')->add(new RolMiddleware([Roles::SOCIO->value,]));
});

// Hooks orden
$app->group('/orden', function (RouteCollectorProxy $group) {
  $group->get('[/]', \OrdenControles::class . ':TraerTodos')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->post('[/]', \OrdenControles::class . ':CargarUno')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->post('/imagen', \OrdenControles::class . ':AgregarImagen')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->get('/demora', \OrdenControles::class . ':DemoraOrden');
  $group->get('/lista', \OrdenControles::class . ':ListaOrdenes')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value,Roles::BARTENDER->value, Roles::COCINERO->value]));
  $group->put('/servir', \OrdenControles::class . ':ServirOrden')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->put('/cobrar', \OrdenControles::class . ':CobrarOrden')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->get('/tarde', \OrdenControles::class . ':OrdenesEntregadasTarde')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('/aTiempo', \OrdenControles::class . ':OrdenesEntregadasATiempo')->add(new RolMiddleware([Roles::SOCIO->value]));
});

// Hooks ventas
$app->group('/ventas', function (RouteCollectorProxy $group) {
  $group->get('[/]', \VentaControles::class . ':TraerTodos')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('/rol', \VentaControles::class . ':TraerVentasRol')->add(new RolMiddleware([Roles::SOCIO->value]));
});

// Hooks Mesa
$app->group('/mesa', function(RouteCollectorProxy $group){
  $group->post('[/]', \MesaControles::class . ':CargarUno')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->put('/modificar', \MesaControles::class . ':ModificarUno')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('[/]', \MesaControles::class . ':TraerTodos')->add(new RolMiddleware([Roles::SOCIO->value, Roles::MOZO->value]));
  $group->get('/masUsada', \MesaControles::class . ':MasUsada')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('/facturacion', \MesaControles::class . ':MenosFacturo')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('/facturacionFechas', \MesaControles::class . ':FacturoFechas')->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->delete('/borrar/{id_mesa}', \MesaControles::class . ':BorrarUno')->add(new RolMiddleware([Roles::SOCIO->value]));
});

// Hooks acciones
$app->group('/preparar', function (RouteCollectorProxy $group) {
  $group->put('[/]', \UsuarioControles::class . ':ComezarAPreparar')->add(new RolMiddleware([Roles::MOZO->value]));
  $group->put('/finalizar', \UsuarioControles::class . ':FinalizarPreparacion')->add(new RolMiddleware([Roles::MOZO->value]));
});

// Hooks descarga archivos 
$app->group('/descargar', function (RouteCollectorProxy $group) {
  $group->get('/csv', function ($request, $response, $args){ return descargarCSV($request, $response, $args);})->add(new RolMiddleware([Roles::SOCIO->value]));
  $group->get('/logoPDF', function ($request, $response, $args){ return LogoPDFDescarga($request, $response, $args);})->add(new RolMiddleware([Roles::SOCIO->value]));
});

// Hooks carga archivos
$app->group('/cargar', function (RouteCollectorProxy $group) {
  $group->post('/csv', function ($request, $response, $args){ return cargarCSV($request, $response, $args);})->add(new RolMiddleware([Roles::SOCIO->value]));
});

// Hooks encuestas
$app->group('/encuesta', function (RouteCollectorProxy $group) {
  $group->post('[/]', \EncuestaControles::class . ':CargarUno');
  $group->get('/mejores', \EncuestaControles::class . ':MejoresEncuestas')->add(new RolMiddleware([Roles::SOCIO->value]));
});

$app->run();
