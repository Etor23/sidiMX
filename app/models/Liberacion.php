<?php

class Liberacion extends Base
{
    public function __construct()
    {
        parent::__construct('liberacion');
    }

    public function registrarConSP($data)
    {
        try {
            $pIdGuia = $data['pIdGuia'] ?? null;
            $pIdUsuario = $data['pIdUsuario'] ?? null;
            $pObservaciones = $data['pObservaciones'] ?? null;

            // Llamar al SP registrarLiberacion
            $sql = "CALL registrarLiberacion(:pIdGuia, :pIdUsuario, :pObservaciones, @pIdLiberacion, @pAfectados)";
            
            $this->raw($sql, [
                ':pIdGuia' => $pIdGuia,
                ':pIdUsuario' => $pIdUsuario,
                ':pObservaciones' => $pObservaciones
            ], 'none');

            // Obtener los valores de salida del SP
            $result = $this->raw("SELECT @pIdLiberacion AS idLiberacion, @pAfectados AS afectados", [], 'one');

            return [
                'idLiberacion' => $result['idLiberacion'],
                'afectados' => $result['afectados']
            ];
        } catch (Exception $e) {
            error_log('Liberacion::registrarConSP() error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function findByGuia($idGuia)
    {
        return $this->raw(
            "SELECT * FROM liberacion WHERE idGuia = :idGuia LIMIT 1",
            [':idGuia' => $idGuia],
            'one'
        );
    }

    public function find($id)
    {
        return parent::find($id);
    }

    public function update($data)
    {
        return parent::update($data);
    }

    public function delete()
    {
        return parent::delete();
    }

    /**
     * Validar datos de liberación
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        $observaciones = trim($data['pObservaciones'] ?? '');

        // Observaciones: 3-200 caracteres
        if ($observaciones === '') {
            $errors['pObservaciones'] = 'Las observaciones son requeridas';
        } elseif (strlen($observaciones) < 3) {
            $errors['pObservaciones'] = 'Las observaciones deben tener al menos 3 caracteres';
        } elseif (strlen($observaciones) > 200) {
            $errors['pObservaciones'] = 'Las observaciones no pueden exceder 200 caracteres';
        }

        return $errors;
    }
}
