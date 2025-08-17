<?php

use function PHPSTORM_META\map;

require_once 'modelos/Encuesta.php';

class EncuestaControles extends Encuesta{
    
    public function CargarUno($request, $response, $args)
    {   
        $parametros = $request->getParsedBody();

        $codigo = $parametros['codigo'];
        $id_mesa = $parametros['id_mesa'];
        $mesa = $parametros['mesa'];
        $restaurante = $parametros['restaurante'];
        $mozo = $parametros['mozo'];
        $cocinero = $parametros['cocinero'];
        $comentario = $parametros['comentario'];

        $orden = Orden::obtenerOrdenPorCodigo($codigo);

        $puntuacionesValidas = range(1, 10);
        if(Encuesta::yaExiste($codigo) != NULL){
            $payload = json_encode(array("ERROR:" => "Esta encuesta ya fue cargada"));
        }
        elseif($orden->estado != 'entregado' && $orden->estado != 'cobrado'){
            $payload = json_encode(array("ERROR:" => "No se puede enviar una encuesta de una orden no entregada"));
        }
        elseif(!in_array($mesa, $puntuacionesValidas) || !in_array($restaurante, $puntuacionesValidas) || !in_array($mozo, $puntuacionesValidas) || !in_array($cocinero, $puntuacionesValidas)){
            $payload = json_encode(array("ERROR:" => "Puntuaciones no validad, deben estar entre 1 y 10"));
        }
        elseif(strlen($comentario) > 66) {
            $payload = json_encode(array("ERROR:" => "Comentario mayor a 66 caracteres"));
        }
        else{

            if(Orden::obtenerMesa($codigo) == $id_mesa){
                $encuesta = new Encuesta();
                $encuesta->codigo = $codigo;
                $encuesta->id_mesa = $id_mesa;
                $encuesta->mesa = $mesa;
                $encuesta->restaurante = $restaurante;
                $encuesta->mozo = $mozo;
                $encuesta->cocinero = $cocinero;
                $encuesta->comentario = $comentario;
                $encuesta->altaEncuesta();
        
                $payload = json_encode(array("EXITO:" => "Encuesta registrada"));
            }
            else{
                $payload = json_encode(array("ERROR:" => "La mesa no coincide con la orden"));
            }
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

public static function MejoresEncuestas($request, $response, $args){

    $parametros = $request->getQueryParams();
    
    $cantidad = $parametros['cantidad'];
    $encuestas = Encuesta::obtenerEncuestasOrdenadas();

    // Contar cuántas encuestas vinieron
    $totalEncuestas = count($encuestas);

    // Si hay menos encuestas que la cantidad pedida, ajustar
    $cantidad = min($cantidad, $totalEncuestas);

    $mejoresEncuestas = array();

    for ($i = 0; $i < $cantidad; $i++) {
        $mejoresEncuestas[] = $encuestas[$i];
    }

    $payload = json_encode(array(
        "mejoresComentarios" => $mejoresEncuestas
    ));

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
}
}