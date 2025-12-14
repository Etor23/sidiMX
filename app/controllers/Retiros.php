<?php

class Retiros extends Controller
{
    private $modelo;
    private $guiaModelo;

    public function __construct()
    {
        $this->modelo = $this->model('Retiro');
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario de retiro
     * URL: /retiro/crear/{id}
     */
    public function crear($id = null)
    {
        if (!$this->checkPermissionByRole(['Recinto'])) {
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

        $this->view('retiro/registrar_retiro', [
            'guia' => $guia,
            'retiro' => [],
            'errors' => []
        ]);
    }

    /**
     * Guardar retiro y cambiar estado a retirado
     * URL POST: /retiro/store
     */
    public function store()
    {
        if (!$this->checkPermissionByRole(['Recinto'])) {
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

        // Usar validación del modelo
        $errors = $this->modelo->validate($input);

        if (!empty($errors)) {
            $this->view('retiro/registrar_retiro', [
                'guia' => $guia,
                'retiro' => [],
                'errors' => $errors
            ]);
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;

            // Guardar datos de retiro usando el SP registrarRetiro
            $result = $this->modelo->registrarConSP([
                'pIdGuia' => $pIdGuia,
                'pUnidad' => $input['pUnidad'],
                'pPlacas' => $input['pPlacas'],
                'pOperador' => $input['pOperador'],
                'pIdUsuariosRecinto' => $userId
            ]);

            if ($result['idRetiro']) {
                // Cambiar estado a retirado
                $this->guiaModelo->updateEstadoConSP($pIdGuia, 'retirado', $userId);
                refresh('/guias');
                return;
            }

            $errors['general'] = 'No se pudo registrar el retiro';
            $this->view('retiro/registrar_retiro', [
                'guia' => $guia,
                'retiro' => [],
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            $errors['exception'] = $e->getMessage();
            $this->view('retiro/registrar_retiro', [
                'guia' => $guia,
                'retiro' => [],
                'errors' => $errors
            ]);
        }
    }
}

