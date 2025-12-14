<?php
/**
 * Modelo para BultosReal - llama al SP registrarBultosReal
 */
class BultosReal{
    private $db;
    private $table = 'bultosreal';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    /**
     * Llama al procedimiento registrarBultosReal
     * Espera un array con claves: pIdGuia, pCantidad, pPesoNeto, pVolumen, pAncho, pAlto, pLargo, pIdUsuariosRecinto
     * Retorna array con keys 'idBultosReal' y 'afectados' o lanza excepción
     */
    public function registrarConSP(array $params)
    {
        $defaults = [
            'pIdGuia' => null,
            'pCantidad' => null,
            'pPesoNeto' => null,
            'pVolumen' => null,
            'pAncho' => null,
            'pAlto' => null,
            'pLargo' => null,
            'pIdUsuariosRecinto' => null
        ];

        $binds = array_merge($defaults, $params);

        $call = "CALL registrarBultosReal(
            :pIdGuia,
            :pCantidad,
            :pPesoNeto,
            :pVolumen,
            :pAncho,
            :pAlto,
            :pLargo,
            :pIdUsuariosRecinto,
            @pIdBultosReal,
            @pAfectados
        )";

        $this->db->raw($call, $binds, 'none');

        $row = $this->db->raw("SELECT @pIdBultosReal AS idBultosReal, @pAfectados AS afectados", [], 'one');
        return [
            'idBultosReal' => $row['idBultosReal'] ?? null,
            'afectados' => isset($row['afectados']) ? (int)$row['afectados'] : null
        ];
    }

    public function findByGuia($idGuia)
    {
        return $this->db->where('idGuia', (int)$idGuia)->first();
    }
}
