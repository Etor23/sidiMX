<?php
/**
 * Modelo Permisos
 * Provee método para obtener los permisos asociados a una guía
 */
class Permisos{
    private $db;
    private $table = 'permisos';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    public function all(){
        return $this->db->get();
    }

    public function find($id){
        return $this->db->find($id);
    }

    /**
     * Obtener los permisos que pertenecen a una guía
     * usa la tabla permisospaquete (idGuia, idPermisos)
     * Devuelve array de permisos o []
     */
    public function findByGuia($idGuia){
        $sql = "SELECT p.* FROM permisos p
                INNER JOIN permisospaquete pp ON pp.idPermisos = p.id
                WHERE pp.idGuia = :idGuia";

        return $this->db->raw($sql, [':idGuia' => (int)$idGuia]);
    }

}
