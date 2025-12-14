<?php

class Pedimentos extends Controller
{
    private $modelo;
    private $guiaModelo;

    public function __construct()
    {
        $this->modelo = $this->model('Pedimento');
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario para elaborar pedimento
     * URL: /pedimento/crear/{id}
     */
    public function crear($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) { 
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

        $this->view('pedimento/formPedimento', [
            'guia' => $guia,
            'errors' => [],
            'old' => []
        ]);
    }

    /**
     * Guardar pedimento y cambiar estado a esperandoPago
     * URL POST: /pedimento/store
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

        // Usar validación del modelo
        $errors = $this->modelo->validate($input);

        if (!empty($errors)) {
            $this->view('pedimento/formPedimento', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;
            
            // Guardar datos del pedimento usando el SP registrarPedimento
            $result = $this->modelo->registrarConSP([
                'idGuia' => $pIdGuia,
                'regimen' => $input['pRegimen'] ?? $input['regimen'] ?? null,
                'patente' => $input['pPatente'] ?? $input['patente'] ?? null,
                'numero' => $input['pNumero'] ?? $input['numero'] ?? null,
                'pIdUsuarios' => $userId
            ]);

            if ($result['idPedimento']) {
                // Cambiar estado a esperandoPago
                $this->guiaModelo->updateEstadoConSP($pIdGuia, 'esperandoPago', $userId);
                refresh('/guias');
                return;
            }

            $errors['general'] = 'No se pudo registrar el pedimento';
            $this->view('pedimento/formPedimento', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        } catch (Exception $e) {
            $errors['exception'] = $e->getMessage();
            $this->view('pedimento/formPedimento', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        }
    }
}
