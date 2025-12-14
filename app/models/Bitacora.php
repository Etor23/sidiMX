<?php
/**
 * Modelo BitacoraModel - obtiene registros de la tabla bitacora
 */
class Bitacora{
    private $db;
    private $table = 'bitacora';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    /**
     * Insertar una entrada en bitácora
     */
    public function log($tabla, $registroId, $accion, $valorAnterior, $valorNuevo, $idUsuario)
    {
        if ($idUsuario === null || !is_numeric($idUsuario)) {
            throw new Exception('BitacoraModel::log requiere un idUsuario válido');
        }

        $sql = "INSERT INTO {$this->table} (idUsuarios, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
                VALUES (:idUsuarios, :tabla, :registroId, :accion, :valorAnterior, :valorNuevo)";

        return $this->db->raw($sql, [
            ':idUsuarios' => (int)$idUsuario,
            ':tabla' => $tabla,
            ':registroId' => $registroId,
            ':accion' => $accion,
            ':valorAnterior' => $valorAnterior,
            ':valorNuevo' => $valorNuevo
        ], 'none');
    }

    /**
     * Obtener todas las entradas (más recientes primero), incluyendo nombre de usuario
     */
    public function all()
    {
        return $this->paginated(null, null);
    }

    /**
     * Obtener paginado: si $limit es null devuelve todo
     */
    public function paginated($limit = null, $offset = null)
    {
        $sql = "SELECT b.*, u.nombre AS usuario_nombre
                FROM bitacora b
                LEFT JOIN usuarios u ON u.id = b.idUsuarios
                ORDER BY b.fechaHora DESC";

        $binds = [];
        if (is_numeric($limit)) {
            $sql .= " LIMIT :_limit";
            $binds[':_limit'] = (int)$limit;
        }
        if (is_numeric($offset)) {
            $sql .= " OFFSET :_offset";
            $binds[':_offset'] = (int)$offset;
        }

        return $this->db->raw($sql, $binds, 'all');
    }

    /**
     * Contar total de entradas en bitacora
     */
    public function count()
    {
        $row = $this->db->raw("SELECT COUNT(*) AS c FROM bitacora", [], 'one');
        return isset($row['c']) ? (int)$row['c'] : 0;
    }

    public function find($id)
    {
        return $this->db->find((int)$id);
    }
}
