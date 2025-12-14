<?php

class Pods extends Controller
{
    private $modelo;
    private $guiaModelo;

    public function __construct()
    {
        $this->modelo = $this->model('POD');
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario para registrar POD
     * URL: /pod/registrar/{id}
     */
    public function registrar($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) {
            refresh('/guias');
            return;
        }

        if (empty($id)) {
            refresh('/guias');
            return;
        }

        $guia = $this->guiaModelo->find((int)$id);
        if (empty($guia)) {
            refresh('/guias');
            return;
        }

        // Verificar que el estado sea "retirado"
        if ($guia['estado'] !== 'retirado') {
            refresh('/guias');
            return;
        }

        $data = [
            'guia' => $guia,
            'errors' => [],
            'pReceptor' => '',
            'pCondicion' => '',
            'pObservaciones' => ''
        ];

        $this->view('pod/formPOD', $data);
    }

    /**
     * Procesar registro de POD
     * URL: /pod/store
     */
    public function store()
    {
        if (!$this->checkPermissionByRole(['Operador'])) {
            refresh('/guias');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            refresh('/guias');
            return;
        }

        $input = array_map('trim', $_POST);
        $pIdGuia = isset($input['pIdGuia']) ? (int)$input['pIdGuia'] : null;

        if (empty($pIdGuia)) {
            refresh('/guias');
            return;
        }

        $guia = $this->guiaModelo->find($pIdGuia);
        if (empty($guia)) {
            refresh('/guias');
            return;
        }

        // Verificar que el estado sea "retirado"
        if ($guia['estado'] !== 'retirado') {
            refresh('/guias');
            return;
        }

        $errors = [];

        // Validar receptor (requerido)
        $pReceptor = $input['pReceptor'] ?? '';
        if (empty($pReceptor)) {
            $errors['pReceptor'] = 'El receptor es obligatorio';
        } elseif (strlen($pReceptor) > 60) {
            $errors['pReceptor'] = 'El receptor no puede exceder 60 caracteres';
        }

        // Validar condición (requerido)
        $pCondicion = $input['pCondicion'] ?? '';
        $condicionesValidas = ['Excelente', 'Buena', 'Regular', 'Dañada'];
        if (empty($pCondicion)) {
            $errors['pCondicion'] = 'La condición es obligatoria';
        } elseif (!in_array($pCondicion, $condicionesValidas)) {
            $errors['pCondicion'] = 'Condición no válida';
        } elseif (strlen($pCondicion) > 30) {
            $errors['pCondicion'] = 'La condición no puede exceder 30 caracteres';
        }

        // Validar observaciones (opcional)
        $pObservaciones = $input['pObservaciones'] ?? '';
        if (strlen($pObservaciones) > 200) {
            $errors['pObservaciones'] = 'Las observaciones no pueden exceder 200 caracteres';
        }

        if (!empty($errors)) {
            $data = [
                'guia' => $guia,
                'errors' => $errors,
                'pReceptor' => $pReceptor,
                'pCondicion' => $pCondicion,
                'pObservaciones' => $pObservaciones
            ];
            $this->view('pod/formPOD', $data);
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;

            // Registrar POD usando el SP
            $result = $this->modelo->registrarConSP([
                'pIdGuia' => $pIdGuia,
                'pReceptor' => $pReceptor,
                'pCondicion' => $pCondicion,
                'pObservaciones' => $pObservaciones
            ]);

            if ($result && isset($result['idPOD'])) {
                // Cambiar estado a "entregado" (estado final, no modificable)
                $this->guiaModelo->updateEstadoConSP($pIdGuia, 'entregado', $userId);
                refresh('/guias');
                return;
            }

            $errors['general'] = 'No se pudo registrar el POD';
            $this->view('pod/formPOD', [
                'guia' => $guia,
                'errors' => $errors,
                'pReceptor' => $pReceptor,
                'pCondicion' => $pCondicion,
                'pObservaciones' => $pObservaciones
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar POD: " . $e->getMessage());
            $this->view('pod/formPOD', [
                'guia' => $guia,
                'errors' => ['general' => 'Error al registrar el POD'],
                'pReceptor' => $pReceptor,
                'pCondicion' => $pCondicion,
                'pObservaciones' => $pObservaciones
            ]);
        }
    }
}

