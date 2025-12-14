<?php

/**  config.inc.php, es buena practica ponerle inc a los archivos que se usaran como include, para archivos que no se usan directamente
*
*
* @autor Etor
* @fecha 23/11/2025
*/  

define('URLROOT','http://sidiMX'); //Cual es mi dominio

define('APPROOT',__DIR__.'/../'); //Cual es la ruta de mi proyecto
define('VENDORS', __DIR__.'/../../vendor/');

// Configurar zona horaria de México
date_default_timezone_set('America/Mexico_City');

require_once APPROOT.'helpers/helpers.inc.php'; //cargar los helpers

//definir constantes de BD

    define('DBUSER','root');
    define('DBPWD','');
    define('DBDRIVER','mysql');
    define('DBHOST','localhost');
    define('DBNAME','sidi');
    


    //Carga de clases core
    spl_autoload_register(function($nombre){
        // Primero intentar cargar desde core
        if (file_exists(APPROOT.'/core/'.$nombre.'.php')) {
            require_once APPROOT.'/core/'.$nombre.'.php';
        }
        // Si no existe en core, intentar cargar desde controllers
        elseif (file_exists(APPROOT.'/controllers/'.$nombre.'.php')) {
            require_once APPROOT.'/controllers/'.$nombre.'.php';
        }
        // Si no existe en controllers, intentar cargar desde models
        elseif (file_exists(APPROOT.'/models/'.$nombre.'.php')) {
            require_once APPROOT.'/models/'.$nombre.'.php';
        }
    });