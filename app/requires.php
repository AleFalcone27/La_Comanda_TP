<?php
require __DIR__ . '/../vendor/autoload.php';
require_once './controles/UsuarioControles.php';
require_once './controles/ProductoControles.php';
require_once './controles/OrdenControles.php';
require_once './controles/VentaControles.php';
require_once './controles/MesaControles.php';
require_once './controles/EncuestaControles.php';
require_once './base_datos/AccesoDatos.php';
require_once './controles/JwtControles.php';
require_once './middlewares/MwAcceso.php';
require_once './middlewares/MwRol.php';
require_once './funciones/descargaCSV.php';
require_once './funciones/logoPDF.php';