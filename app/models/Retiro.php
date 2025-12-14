<?php
/**
 * Modelo Retiro - llama al SP registrarRetiro
 */
class Retiro{
    private $db;
    private $table = 'retiro';

    public function __construct(){
        $this->db = new Base($this->table);
    }

    /**
     * Llama al procedimiento registrarRetiro
     * Espera array con: pIdGuia, pUnidad, pPlacas, pOperador, pIdUsuariosRecinto
     * Retorna array con keys: idRetiro, afectados
     */
    public function registrarConSP(array $params)
    {
        $defaults = [
            'pIdGuia' => null,
            'pUnidad' => null,
            'pPlacas' => null,
            'pOperador' => null,
            'pIdUsuariosRecinto' => null
        ];

        $binds = array_merge($defaults, $params);

        $call = "CALL registrarRetiro(
            :pIdGuia,
            :pUnidad,
            :pPlacas,
            :pOperador,
            :pIdUsuariosRecinto,
            @pIdRetiroNuevo,
            @pAfectados
        )";

        $this->db->raw($call, $binds, 'none');

        $row = $this->db->raw("SELECT @pIdRetiroNuevo AS idRetiro, @pAfectados AS afectados", [], 'one');
        return [
            'idRetiro' => $row['idRetiro'] ?? null,
            'afectados' => isset($row['afectados']) ? (int)$row['afectados'] : null
        ];
    }

    public function findByGuia($idGuia){
        return $this->db->where('idGuia', (int)$idGuia)->first();
    }

    /**
     * Validar datos de retiro
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        $unidad = trim($data['pUnidad'] ?? '');
        $placas = trim($data['pPlacas'] ?? '');
        $operador = trim($data['pOperador'] ?? '');

        // Unidad: letras, números, signos básicos, 3-20 caracteres
        if ($unidad === '') {
            $errors[] = 'La unidad es requerida';
        } elseif (!preg_match('/^[A-Za-z0-9\s\-_.\\/()]{3,20}$/', $unidad)) {
            $errors[] = 'La unidad debe contener letras, números y signos básicos (3 a 20 caracteres)';
        }

        // Placas: letras mayúsculas, números, guiones, 5-10 caracteres
        if ($placas === '') {
            $errors[] = 'Las placas son requeridas';
        } elseif (!preg_match('/^[A-Z0-9\-]{5,10}$/', $placas)) {
            $errors[] = 'Las placas deben contener letras mayúsculas, números y guiones (5 a 10 caracteres)';
        }

        // Operador: solo letras, 3-40 caracteres
        if ($operador === '') {
            $errors[] = 'El conductor es requerido';
        } elseif (!preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,40}$/', $operador)) {
            $errors[] = 'El conductor solo puede contener letras (3 a 40 caracteres)';
        }

        return $errors;
    }
}
