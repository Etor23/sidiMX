<?php
/**
 * 
 * Controlador de inicio/dashboard
 */

class Home extends Controller{

    public function index(){
        $this->view('index');
    }
}