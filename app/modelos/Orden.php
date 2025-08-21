<?php
    include_once "base_datos/AccesoDatos.php";
    include_once "funciones/guardar_imagen.php";
    date_default_timezone_set('America/Argentina/Buenos_Aires');
class Orden{

    public $id;
    public $codigo;
    public $id_mesa;
    public $estado;
    public $hora_pedido; 
    public $demora_orden;
    public $cliente_nombre;
    public $fecha;

    public function altaOrden()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos
        ->prepararConsulta("INSERT INTO orden (codigo, id_mesa, estado, hora_pedido, cliente_nombre, fecha)
         VALUES (:codigo, :id_mesa, :estado, :hora_pedido, :cliente_nombre, :fecha)");

        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':hora_pedido', $this->hora_pedido);
        $consulta->bindValue(':cliente_nombre', $this->cliente_nombre);
        $consulta->bindValue(':fecha', $this->fecha);
        $consulta->execute();


        return $objAccesoDatos->obtenerUltimoId();
    }

   public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo, id_mesa, estado, hora_pedido, cliente_nombre, demora_orden FROM orden");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Orden');
    } 

    public static function obtenerOrdenPorCodigo($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM orden WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject();
    }

    public static function generarCodigo(){
        
        $codigo = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 5)), 0, 5);

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id FROM orden WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo);
        $consulta->execute();
        
        while($consulta->fetch()){
            $codigo = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 5)), 0, 5);

            $consulta = $objAccesoDatos->prepararConsulta("SELECT id FROM orden WHERE codigo = :codigo");
            $consulta->bindValue(':codigo', $codigo);
            $consulta->execute();
        }

        return $codigo;
    }

    public static function existeOrden($codigo){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo FROM orden WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function actualizarDemorasOrdenes() {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo FROM orden WHERE estado <> 'cobrado'");
        $consulta->execute();
        $ordenes = $consulta->fetchAll(PDO::FETCH_COLUMN);
    
        foreach ($ordenes as $codigo) {
            $nuevosDatos = self::demorayEstado($codigo);
    
            $consultaUpdate = $objAccesoDatos->prepararConsulta("UPDATE orden SET demora_orden = :demora_orden, estado = :estado WHERE codigo = :codigo");
            $consultaUpdate->bindValue(':demora_orden', $nuevosDatos[0], PDO::PARAM_INT);
            $consultaUpdate->bindValue(':estado', $nuevosDatos[1], PDO::PARAM_STR);
            $consultaUpdate->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            $consultaUpdate->execute();
        }
    
        return "Estados de órdenes actualizados correctamente.";
    }

    public static function demorayEstado($codigo) {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT estado FROM venta WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();
        $estados = $consulta->fetchAll(PDO::FETCH_COLUMN);

        if($estados == NULL){
            return [0, 'no existe la orden'];
        }

        $consulta = $objAccesoDatos->prepararConsulta("SELECT COALESCE(MAX(demora), 0) AS max_demora FROM venta WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();
        $maxDemora = $consulta->fetch(PDO::FETCH_ASSOC)['max_demora'];

        if (in_array('espera', $estados)) {
            return [$maxDemora, 'espera'];
        }
        elseif (in_array('en preparacion', $estados)) {    
            return [$maxDemora, 'en preparacion'];
        }
        elseif (count(array_unique($estados)) === 1 && $estados[0] === 'listo para servir') {
            return [$maxDemora, 'listo para servir'];
        }
        elseif (count(array_unique($estados)) === 1 && $estados[0] === 'entregado') {
            return [$maxDemora, 'entregado'];
        }
    }

    public static function obtenerMesa($codigo){

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id_mesa FROM orden WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();
        
        $idMesa = $consulta->fetch(PDO::FETCH_COLUMN);
    
        return $idMesa !== false ? $idMesa : 'mesa no existe';
    }

    public static function servir($codigo){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE orden SET horario_entrega = :hora WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':hora', date('H:i:s'));
        $consulta->execute();

        Orden::actualizarDemorasOrdenes();
    }

    public static function calcularCuenta($codigo) {
        if (Orden::existeOrden($codigo)) {
            $ventas = Venta::ObtenerPorCodigo($codigo);
            $cuenta = 0;
    
            foreach ($ventas as $venta) {
                $producto = Producto::obtenerProductoNombre($venta->producto);
                
                if (!$producto){
                    return "Error: Producto '{$venta->producto}' no encontrado.";
                }
                $cuenta += $venta->cantidad * $producto->precio;
            }
    
            return $cuenta;
        } 
        else{
            return 'La orden no existe';
        }
    }

    public static function cobrar($codigo, $precio){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE orden SET cuenta = :cuenta, estado = :estado WHERE codigo = :codigo AND estado = 'entregado'");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':cuenta', $precio, PDO::PARAM_INT);
        $consulta->bindValue(':estado', 'cobrado', PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function ordenesTarde(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo FROM orden WHERE horario_entrega IS NOT NULL AND horario_entrega > TIMESTAMPADD(MINUTE, demora_orden, hora_pedido)");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function ordenesATiempo(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo as horario FROM orden WHERE horario_entrega IS NOT NULL AND horario_entrega <= TIMESTAMPADD(MINUTE, demora_orden, hora_pedido)");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}    