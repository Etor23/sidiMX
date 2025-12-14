<?php
/**
 * Modelo Rol
 * Campos: id, rol
 */
class Rol {
    private $db;
    private $table = 'roles';

    public function __construct() {
        $this->db = new Base($this->table);
    }

    public function all() {
        return $this->db->get();
    }

    public function find($id) {
        return $this->db->find($id);
    }
}
