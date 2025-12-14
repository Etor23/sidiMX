<?php
/**
 * Modelo Logistica
 */
class Logistica{
    private $db;
    private $table = 'logistica';

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
