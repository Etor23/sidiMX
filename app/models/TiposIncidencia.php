<?php
/**
 * Modelo TiposIncidencia
 */
class TiposIncidencia{
    private $db;
    private $table = 'tiposincidencia';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    public function all(){
        return $this->db->get();
    }

    public function find($id){
        return $this->db->find((int)$id);
    }
}
