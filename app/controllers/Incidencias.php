<?php
/**
 * Controlador Incidencias
 */
class Incidencias extends Controller
{
    public function __construct()
    {
        // nada por ahora
    }

    /**
     * Mostrar formulario para crear incidencia ligado a una guía
     * URL: /incidencias/create?guia={id}
     */
    public function create()
    {
        if (!estaLogueado()){
            refresh('/login');
            return;
        }

        $idGuia = isset($_GET['guia']) ? (int)$_GET['guia'] : null;
        if (empty($idGuia)) {
            refresh('/guias');
            return;
        }

        // cargar datos para el resumen
        $guiaModel = $this->model('Guia');
        $bultosModel = $this->model('Bultos');
        $bultosRealModel = $this->model('BultosReal');
        $tiposModel = $this->model('TiposIncidencia');

        $guia = $guiaModel->find((int)$idGuia);
        if (empty($guia)) {
            refresh('/guias');
            return;
        }

        $bultosReg = null;
        if (!empty($guia['idBultos'])) {
            $bultosReg = $bultosModel->find((int)$guia['idBultos']);
        }

        $bultosReal = $bultosRealModel->findByGuia((int)$idGuia);
        $tipos = $tiposModel->all();

        $this->view('incidencias/create', [
            'guia' => $guia,
            'bultosReg' => $bultosReg,
            'bultosReal' => $bultosReal,
            'tipos' => $tipos,
            'errors' => []
        ]);
    }

    /**
     * Procesar POST del formulario de incidencia
     */
    public function store()
    {
        if (!estaLogueado()){
            refresh('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            refresh('/guias');
            return;
        }

        $input = array_map('trim', $_POST);
        $pIdGuia = isset($input['pIdGuia']) ? (int)$input['pIdGuia'] : null;
        $pIdTiposIncidencia = isset($input['pIdTiposIncidencia']) ? (int)$input['pIdTiposIncidencia'] : null;
        $pDescripcion = $input['pDescripcion'] ?? null;

        $errors = [];
        if (empty($pIdGuia)) $errors['pIdGuia'] = 'Falta id de la guía';
        if (empty($pIdTiposIncidencia)) $errors['pIdTiposIncidencia'] = 'Seleccione el tipo de incidencia';
        if (empty($pDescripcion)) $errors['pDescripcion'] = 'La descripción es obligatoria';

        if (!empty($errors)){
            // recargar create con errores y datos
            $this->view('incidencias/create', [
                'errors' => $errors,
                'guia' => $this->model('Guia')->find((int)$pIdGuia),
                'bultosReg' => $this->model('Bultos')->find((int)($this->model('Guia')->find((int)$pIdGuia)['idBultos'] ?? 0)),
                'bultosReal' => $this->model('BultosReal')->findByGuia((int)$pIdGuia),
                'tipos' => $this->model('TiposIncidencia')->all()
            ]);
            return;
        }

        try {
            $incModel = $this->model('Incidencia');
            $userId = $_SESSION['usuario_id'] ?? null;
            $newId = $incModel->registrarConSP([
                'pIdGuia' => $pIdGuia,
                'pIdTiposIncidencia' => $pIdTiposIncidencia,
                'pDescripcion' => $pDescripcion,
                'pIdUsuariosOperador' => $userId
            ]);

            // mostrar pantalla intermedia que pregunta si quiere agregar otra incidencia
            $this->view('incidencias/after_create', [
                'idGuia' => $pIdGuia,
                'idIncidencia' => $newId
            ]);
            return;
        } catch (Exception $e){
            $errors['exception'] = $e->getMessage();
            $this->view('incidencias/create', [
                'errors' => $errors,
                'guia' => $this->model('Guia')->find((int)$pIdGuia),
                'bultosReg' => $this->model('Bultos')->find((int)($this->model('Guia')->find((int)$pIdGuia)['idBultos'] ?? 0)),
                'bultosReal' => $this->model('BultosReal')->findByGuia((int)$pIdGuia),
                'tipos' => $this->model('TiposIncidencia')->all()
            ]);
            return;
        }
    }

    /**
     * Marcar guia como ordenDePago 
     * URL: /incidencias/marcarOrdenPago/{idGuia}
     */
    public function marcarOrdenPago($id = null)
    {

        $guiaModel = $this->model('Guia');
        $userId = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
        $updated = $guiaModel->updateEstadoConSP((int)$id, 'ordenDePago', $userId);
        if ($updated) {
            echo json_encode(['ok' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'No se pudo actualizar estado']);
        }
    }
}
