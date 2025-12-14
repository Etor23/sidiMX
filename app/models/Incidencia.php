<?php
/**
 * Modelo Incidencia - llama al SP registrarIncidencia
 */
class Incidencia{
    private $db;
    private $table = 'incidencia';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    /**
     * Llama al procedimiento registrarIncidencia
     * Espera un array con claves: pIdGuia, pIdTiposIncidencia, pDescripcion, pIdUsuariosOperador
     * Retorna el id de la incidencia creada
     */
    public function registrarConSP(array $params)
    {
        $defaults = [
            'pIdGuia' => null,
            'pIdTiposIncidencia' => null,
            'pDescripcion' => null,
            'pIdUsuariosOperador' => null
        ];

        $binds = array_merge($defaults, $params);

        $call = "CALL registrarIncidencia(
            :pIdGuia,
            :pIdTiposIncidencia,
            :pDescripcion,
            :pIdUsuariosOperador,
            @pIdIncidenciaNueva
        )";

        $this->db->raw($call, $binds, 'none');

        $row = $this->db->raw("SELECT @pIdIncidenciaNueva AS id", [], 'one');
        return $row['id'] ?? null;
    }

    public function find($id){
        return $this->db->find((int)$id);
    }

    /**
     * Contar incidencias para un conjunto de IDs de guías
     * @param array $guiaIds Array de IDs de guías
     * @return int Total de incidencias
     */
    public function countByGuiaIds(array $guiaIds)
    {
        if (empty($guiaIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($guiaIds), '?'));
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE idGuia IN ($placeholders)";
        
        $params = [];
        foreach ($guiaIds as $index => $id) {
            $params[':id' . $index] = (int)$id;
        }
        
        $sqlWithParams = str_replace('?', implode(', ', array_keys($params)), $sql);
        $result = $this->db->raw($sqlWithParams, $params, 'one');
        
        return (int)($result['total'] ?? 0);
    }
}
