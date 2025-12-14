<?php

class Liberaciones extends Controller
{
    private $modelo;
    private $guiaModelo;

    public function __construct()
    {
        $this->modelo = $this->model('Liberacion');
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario de liberación
     * URL: /liberacion/crear/{id}
     */
    public function crear($id = null)
    {
        if (!$this->checkPermissionByRole(['Supervisor'])) {
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

        $this->view('liberacion/formLiberacion', [
            'guia' => $guia,
            'errors' => [],
            'old' => []
        ]);
    }

    /**
     * Guardar liberación y cambiar estado a liberado
     * URL POST: /liberacion/store
     */
    public function store()
    {
        if (!$this->checkPermissionByRole(['Supervisor'])) {
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
            $this->view('liberacion/formLiberacion', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;

            // Guardar datos de liberación usando el SP registrarLiberacion
            $result = $this->modelo->registrarConSP([
                'pIdGuia' => $pIdGuia,
                'pMotivoLiberacion' => $input['pMotivoLiberacion'],
                'pAutorizadoPor' => $input['pAutorizadoPor'],
                'pIdUsuario' => $userId
            ]);

            if ($result['idLiberacion']) {
                // Cambiar estado a liberado
                $this->guiaModelo->updateEstadoConSP($pIdGuia, 'liberado', $userId);
                refresh('/guias');
                return;
            }

            $errors['general'] = 'No se pudo registrar la liberación';
            $this->view('liberacion/formLiberacion', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        } catch (Exception $e) {
            $errors['exception'] = $e->getMessage();
            $this->view('liberacion/formLiberacion', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        }
    }
}

