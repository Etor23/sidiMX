<?php
/**
 * Modelo Tarifas - Gestiona las tarifas para cálculos
 */
class Tarifas{
    private $db;
    private $table = 'tarifas';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    /**
     * Obtener todos los registros de precios
     */
    public function all(){
        return $this->db->get();
    }

    /**
     * Obtener un registro de precios por ID
     */
    public function find($id){
        return $this->db->find((int)$id);
    }

    /**
     * Obtener el registro de precios actual (el último o el único)
     * Asume que solo hay un registro activo
     */
    public function getCurrent(){
        $result = $this->db->get();
        return !empty($result) ? $result[0] : null;
    }


}
