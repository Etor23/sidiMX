<?php
/**
 * Modelo Comprobante
 */
class Comprobante{
    private $db;
    private $table = 'comprobante';

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
     * Buscar comprobante por idGuia (FK)
     */
    public function findByGuia($idGuia){
        return $this->db->where('idGuia', $idGuia)->first();
    }

    /**
     * Registrar comprobante usando SP registrarComprobante
     * @param array $params [pIdGuia, pNumero, pEmisor, pMoneda, pTotal, pIdUsuario]
     * @return array ['idComprobante' => int, 'afectados' => int]
     */
    public function registrarConSP(array $params)
    {
        $defaults = [
            'pIdGuia' => null,
            'pNumero' => null,
            'pEmisor' => null,
            'pMoneda' => null,
            'pTotal' => null,
            'pIdUsuario' => null
        ];

        $binds = array_merge($defaults, $params);

        $call = "CALL registrarComprobante(
            :pIdGuia,
            :pNumero,
            :pEmisor,
            :pMoneda,
            :pTotal,
            :pIdUsuario,
            @pIdComprobante,
            @pAfectados
        )";

        $this->db->raw($call, $binds, 'none');

        $row = $this->db->raw("SELECT @pIdComprobante AS idComprobante, @pAfectados AS afectados", [], 'one');
        return [
            'idComprobante' => $row['idComprobante'] ?? null,
            'afectados' => isset($row['afectados']) ? (int)$row['afectados'] : null
        ];
    }

    /**
     * Validar datos de comprobante
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        $numero = trim($data['pNumero'] ?? '');
        $emisor = trim($data['pEmisor'] ?? '');
        $moneda = trim($data['pMoneda'] ?? '');
        $total = $data['pTotal'] ?? null;

        // Validar número de cuenta: 10-20 dígitos
        if ($numero === '') {
            $errors['pNumero'] = 'Número de cuenta es requerido';
        } elseif (!preg_match('/^\d{10,20}$/', $numero)) {
            $errors['pNumero'] = 'El número de cuenta debe tener entre 10 y 20 dígitos';
        }

        // Validar emisor: letras, espacios y &, /, -, ., () de 3-100 caracteres
        if ($emisor === '') {
            $errors['pEmisor'] = 'Emisor es requerido';
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s&\/\-.()]{3,100}$/', $emisor)) {
            $errors['pEmisor'] = 'El emisor solo puede contener letras, espacios y & / - . () (3 a 100 caracteres)';
        }

        if ($moneda === '') {
            $errors['pMoneda'] = 'Moneda es requerida';
        }

        // Validar total es número positivo
        if (isset($total)) {
            if ($total === '' || $total === null) {
                $errors['pTotal'] = 'Total es requerido';
            } elseif (!is_numeric($total) || (float)$total < 0) {
                $errors['pTotal'] = 'Total debe ser un número positivo';
            } elseif ((float)$total > 999999999.99) {
                $errors['pTotal'] = 'Total no puede exceder 999,999,999.99';
            }
        }

        return $errors;
    }
}
