<?php
/**
 * Admin de rutas 
 * En nuestro sistema existirá un dashboard y será defaukt
 */

class Routes{
    protected $controladorActual='Home';
    protected $metodoActual='index';
    protected $parametros= [];

    /**
     * @return string de controlador
     */
    public function __construct(){
        //Toma la url
        $url=$this->getUrl();
        //dd($url); //Para debug
        //Cargar el controlador, determinar el metodo
        if($url && file_exists(APPROOT.'/controllers/'.ucwords($url[0]).'.php')){
            $this->controladorActual=ucwords($url[0]);
            unset($url[0]);
        }
        //Cargamos el archivo
        require_once APPROOT.'/controllers/'.$this->controladorActual.'.php';

        //Instanciamos el controlador
        $this->controladorActual=new $this->controladorActual;

        //Determinar el metodo
        if(isset($url[1]) && method_exists($this->controladorActual,$url[1])){
            $this->metodoActual=$url[1];
            unset($url[1]);
        }
        //Aun quedan parametros
        $this->parametros=($url) ? array_values($url) : [];

        //Llamar al metodo
        call_user_func_array([$this->controladorActual,$this->metodoActual],$this->parametros);

    } //Fin construct

    private function getUrl(){
        if(isset($_GET['url'])){

            //Labores de limpieza de la url
            $url=rtrim($_GET['url'],'/'); //DIRECTORY_SEPARATOR
            $url=filter_var($url,FILTER_SANITIZE_URL);
            $url=explode('/',$url); 
            return $url;
        }
    } //Fin getUrl


}//Fin class