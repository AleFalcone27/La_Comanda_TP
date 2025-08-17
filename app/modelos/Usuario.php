<?php
    include_once "base_datos/AccesoDatos.php";
class Usuario{

    public $id;
    public $nombre;
    public $clave;
    public $fecha_registro;
    public $tipo;
    public $estado; // #supedndido #activo

    public static function loguear($id_usuario){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO ingresos (id_usuario, hora_ingreso) VALUES (:id_usuario, :hora_ingreso)");
        $consulta->bindValue(':id_usuario', $id_usuario);
        $consulta->bindValue(':hora_ingreso', date('Y-m-d H:i:s'));
        $consulta->execute();
    }

    public function altaUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuario (nombre, clave, fecha_registro, tipo, estado) VALUES (:nombre, :clave, :fecha_registro, :tipo, :estado)");
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->bindValue(':fecha_registro', $this->fecha_registro);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarUsuario($id_usuario){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM usuario WHERE id = :id_usuario");
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function modificarUsuario($nombre, $estado, $tipo){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE usuario SET estado = :estado, tipo = :tipo WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->rowCount();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, clave, fecha_registro, tipo, estado FROM usuario");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function existeUsuario($nombre){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id FROM usuario WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function validacionUsuario($usuario, $contraseña){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, clave, tipo FROM usuario WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $usuario, PDO::PARAM_STR);
        $consulta->execute();
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($contraseña, $usuario['clave'])) {
            return $usuario;
        } else {
            return false;
        }
    }

    public static function FechasRegistro($usuario){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id FROM usuario WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $usuario, PDO::PARAM_STR);
        $consulta->execute();
        $usuario_id = $consulta->fetch(PDO::FETCH_COLUMN);

        $consulta = $objAccesoDatos->prepararConsulta("SELECT hora_ingreso FROM ingresos WHERE id_usuario = :id_usuario");
        $consulta->bindValue(':id_usuario', $usuario_id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function actividadUsuarios(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
    
        $consulta = $objAccesoDatos->prepararConsulta("SELECT v.id_usuario, u.tipo as rol , COUNT(*) AS operaciones FROM venta v JOIN usuario u ON v.id_usuario = u.id GROUP BY id_usuario, u.tipo");
        $consulta->execute();
        $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
        usort($resultados, function($a, $b) {
            return strcmp($a['rol'], $b['rol']);
        });
    
        return $resultados;
    }
}