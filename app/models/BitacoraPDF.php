<?php

class BitacoraPDF extends Base
{
    public function __construct()
    {
        parent::__construct('bitacorapdf');
    }

    public function registrarConSP($data)
    {
        try {
            $sql = "CALL registrarBitacoraPDF(:pIdUsuario, :pTipoPDF, :pIdGuia)";
            
            $this->raw($sql, [
                ':pIdUsuario' => $data['pIdUsuario'],
                ':pTipoPDF' => $data['pTipoPDF'],
                ':pIdGuia' => $data['pIdGuia']
            ], 'none');
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error en registrarConSP BitacoraPDF: " . $e->getMessage());
            return false;
        }
    }

    public function getAllWithDetails()
    {
        $sql = "SELECT 
                    b.id,
                    b.tipoPDF,
                    b.fechaHora,
                    u.nombre as nombreUsuario,
                    g.id as idGuia,
                    iden.guia as numeroGuia
                FROM bitacorapdf b
                LEFT JOIN usuarios u ON b.idUsuario = u.id
                LEFT JOIN guia g ON b.idGuia = g.id
                LEFT JOIN identificadores iden ON g.idIdentificadores = iden.id
                ORDER BY b.fechaHora DESC";
        
        return $this->raw($sql, [], 'all');
    }
}
