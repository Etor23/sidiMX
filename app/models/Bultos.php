<?php
/**
 * Modelo Bultos
 */
class Bultos{
    private $db;
    private $table = 'bultos';

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
