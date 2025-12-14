<?php

/**
 *  scripts de ayuda general
 * 
 */

session_start();

/**
 * Funcion dd
 * @return 
 */
function dd()
{
    $args = func_get_args(); //Regresa un arreglo con todos los argumentos
    //Función que llama a otra
    //call_user_func_array(['clase','metodo'], $args); Cuando se tenga que llamar a otro metodo de una clase 
    call_user_func_array('dump', $args);
    die(); //es el exit

}

function d()
{
    $args = func_get_args(); //Regresa un arreglo con todos los argumentos
    //Función que llama a otra
    //call_user_func_array(['clase','metodo'], $args); Cuando se tenga que llamar a otro metodo de una clase 
    call_user_func_array('dump', $args);

}

function dump(...$datos)
{
    echo '<pre>';
    foreach ($datos as $d) {
        print_r($d);
        echo "\n-----------------\n";
    }
    echo '</pre>';
}


function refresh($location)
{
    header("Location: {$location}");
}

/**
 * Funcion para conocer el tipo de autorizacion
 */

function estaLogueado(){
    return (!empty($_SESSION['usuario_nombre']));
}
