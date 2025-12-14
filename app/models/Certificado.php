<?php
/**
 * Modelo Certificado (mapea a certificadoorigen)
 */
class Certificado{
    private $db;
    private $table = 'certificadoorigen';

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
