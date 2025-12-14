<?php

class POD extends Base
{
    public function __construct()
    {
        parent::__construct('pod');
    }

    public function registrarConSP($data)
    {
        try {
            $sql = "CALL registrarPOD(:pIdGuia, :pReceptor, :pCondicion, :pObservaciones)";
            
            $this->raw($sql, [
                ':pIdGuia' => $data['pIdGuia'],
                ':pReceptor' => $data['pReceptor'],
                ':pCondicion' => $data['pCondicion'],
                ':pObservaciones' => $data['pObservaciones']
            ], 'none');
            
            // POD no retorna valores out, solo ejecuta
            return ['idPOD' => true];
        } catch (Exception $e) {
            error_log("Error en registrarConSP POD: " . $e->getMessage());
            return false;
        }
    }

    public function findByGuia($idGuia)
    {
        return $this->raw("SELECT * FROM pod WHERE idGuia = :idGuia", [':idGuia' => $idGuia], 'one');
    }

    /**
     * Validar datos de POD
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        $receptor = trim($data['pReceptor'] ?? '');
        $condicion = trim($data['pCondicion'] ?? '');
        $observaciones = trim($data['pObservaciones'] ?? '');

        // Validar receptor: solo letras, 3-40 caracteres
        if ($receptor === '') {
            $errors['pReceptor'] = 'El receptor es requerido';
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,40}$/', $receptor)) {
            $errors['pReceptor'] = 'El receptor solo puede contener letras (3 a 40 caracteres)';
        }

        // Validar condición
        if ($condicion === '') {
            $errors['pCondicion'] = 'Seleccione una condición válida';
        } else {
            $condicionesValidas = ['Excelente', 'Buena', 'Regular', 'Dañada'];
            if (!in_array($condicion, $condicionesValidas, true)) {
                $errors['pCondicion'] = 'Condición no válida';
            }
        }

        // Validar observaciones: 0-200 caracteres (opcional)
        if (strlen($observaciones) > 200) {
            $errors['pObservaciones'] = 'Las observaciones no pueden exceder 200 caracteres';
        }

        return $errors;
    }
}
