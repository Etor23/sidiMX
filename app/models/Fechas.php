<?php
/**
 * Modelo Fechas
 */
class Fechas{
    private $db;
    private $table = 'fechas';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    public function all(){
        return $this->db->get();
    }

    public function find($id){
        return $this->db->find($id);
    }
}
