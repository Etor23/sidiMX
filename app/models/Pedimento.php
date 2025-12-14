<?php

class Pedimento extends Base
{
    public function __construct()
    {
        parent::__construct('pedimento');
    }

    public function registrarConSP($data)
    {
        try {
            $pIdGuia = $data['idGuia'] ?? null;
            $pRegimen = $data['regimen'] ?? null;
            $pPatente = $data['patente'] ?? null;
            $pNumero = $data['numero'] ?? null;
            $pIdUsuarios = $data['pIdUsuarios'] ?? null;

            // Llamar al SP registrarPedimento
            $sql = "CALL registrarPedimento(:pIdGuia, :pRegimen, :pPatente, :pNumero, :pIdUsuarios, @pIdPedimento, @pAfectados)";
            
            $this->raw($sql, [
                ':pIdGuia' => $pIdGuia,
                ':pRegimen' => $pRegimen,
                ':pPatente' => $pPatente,
                ':pNumero' => $pNumero,
                ':pIdUsuarios' => $pIdUsuarios
            ], 'none');

            // Obtener los valores de salida del SP
            $result = $this->raw("SELECT @pIdPedimento AS idPedimento, @pAfectados AS afectados", [], 'one');

            return [
                'idPedimento' => $result['idPedimento'],
                'afectados' => $result['afectados']
            ];
        } catch (Exception $e) {
            error_log('Pedimento::registrarConSP() error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create($data)
    {
        try {
            // Asegurar que los datos tengan las claves esperadas
            $insertData = [
                'idGuia' => $data['idGuia'] ?? null,
                'regimen' => $data['regimen'] ?? null,
                'patente' => $data['patente'] ?? null,
                'numero' => $data['numero'] ?? null,
                'fecha' => date('Y-m-d H:i:s')
            ];

            // Usar el método create del padre que retorna lastInsertId()
            $id = parent::create($insertData);
            return $id;
        } catch (Exception $e) {
            error_log('Pedimento::create() error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function findByGuia($idGuia)
    {
        return $this->raw(
            "SELECT * FROM pedimento WHERE idGuia = :idGuia LIMIT 1",
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
     * Validar datos de pedimento
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        $regimen = trim($data['pRegimen'] ?? $data['regimen'] ?? '');
        $patente = trim($data['pPatente'] ?? $data['patente'] ?? '');
        $numero = trim($data['pNumero'] ?? $data['numero'] ?? '');

        $validRegimenes = [
            'Importación definitiva',
            'Importación temporal',
            'Devolucion'
        ];

        // Validar régimen
        if ($regimen === '') {
            $errors['pRegimen'] = 'El régimen es requerido';
        } elseif (!in_array($regimen, $validRegimenes, true)) {
            $errors['pRegimen'] = 'Seleccione un régimen válido';
        }

        // Validar patente
        if ($patente === '') {
            $errors['pPatente'] = 'La patente es requerida';
        } elseif (!preg_match('/^\d{4}$/', $patente)) {
            $errors['pPatente'] = 'La patente debe tener 4 dígitos numéricos';
        }

        // Validar número de pedimento
        if ($numero === '') {
            $errors['pNumero'] = 'El número de pedimento es requerido';
        } elseif (!preg_match('/^\d{15}$/', $numero)) {
            $errors['pNumero'] = 'El número de pedimento debe tener exactamente 15 dígitos';
        }

        return $errors;
    }
}
