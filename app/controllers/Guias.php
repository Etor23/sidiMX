<?php
/**
 * Controlador de Guias
 */

class Guias extends Controller
{

    private $modelo;

    public function __construct()
    {
        $this->modelo = $this->model('Guia');
    }

    public function index()
    {
        // Paginación
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $allGuias = $this->modelo->all();

        // obtener rol del usuario logueado
        $roleId = $_SESSION['idRoles'] ?? null;
        $roleModel = $this->model('Rol');
        $role = $roleModel->find((int)$roleId);
        $roleName = $role['rol'] ?? '';

        // marcar si cada guia tiene comprobante o permisos para mostrar enlaces condicionales
        $compModel = $this->model('Comprobante');
        $permModel = $this->model('Permisos');
        foreach ($allGuias as $idx => $g) {
            $hasComp = $compModel->findByGuia((int)$g['id']);
            $allGuias[$idx]['hasComprobante'] = !empty($hasComp);

            $perms = $permModel->findByGuia((int)$g['id']);
            $allGuias[$idx]['hasPermisos'] = !empty($perms);
        }

        // filtrar según rol
        $isOperador = strcasecmp($roleName, 'Operador') === 0;
        $isRecinto = strcasecmp($roleName, 'Recinto') === 0;
        $isSupervisor = strcasecmp($roleName, 'Supervisor') === 0;

        if ($isRecinto) {
            // Recinto ve guías enRecinto y liberado
            $allGuias = array_values(array_filter($allGuias, function($g){
                return in_array(($g['estado'] ?? ''), ['enRecinto','liberado']);
            }));
        }

        // Aplicar paginación
        $total = count($allGuias);
        $guias = array_slice($allGuias, $offset, $perPage);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $data = [
            'guias' => $guias,
            'roleName' => $roleName,
            'isOperador' => $isOperador,
            'isRecinto' => $isRecinto,
            'isSupervisor' => $isSupervisor,
            'pagination' => [
                'current' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'offset' => $offset
            ]
        ];

        $this->view('guia/index', $data);
    }

    /**
     * Mostrar formulario para crear una nueva guía
     */
    public function create()
    {
        if (!estaLogueado()) {
            refresh('/login');
            return;
        }

        // datos iniciales vacíos
        $data = ['errors' => [], 'old' => []];
        $this->view('guia/create', $data);
    }

    /**
     * Validar si una guía externa ya existe (AJAX)
     */
    public function validarGuiaExterna()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $guiaExterna = trim($input['guia_externa'] ?? '');
        $idGuia = (int)($input['id_guia'] ?? 0); // Para excluir en edición
        
        if (empty($guiaExterna)) {
            echo json_encode(['existe' => false]);
            return;
        }
        
        // Buscar si existe otra guía con ese código (excluyendo la actual si es edición)
        $existe = $this->modelo->existeGuiaExterna($guiaExterna, $idGuia);
        
        echo json_encode(['existe' => $existe]);
    }

    /**
     * Procesar POST del formulario de creación
     */
    public function store()
    {
        if (!estaLogueado()) {
            refresh('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            refresh('/guias');
            return;
        }

        // Recoger datos del POST
        $input = array_map('trim', $_POST);

        // permisos se envían en pPermisosJson
        $permisosJson = $input['pPermisosJson'] ?? null;
        if ($permisosJson === '') {
            $permisosJson = null;
        }

        // Mapear parámetros necesarios para el SP
        // Mapear parámetros necesarios para el SP
        $spParams = [
            'pIdUsuario' => $_SESSION['usuario_id'] ?? null,
            'pGuia' => $input['pGuia'] ?? null,
            'pMaster' => $input['pMaster'] ?? null,
            'pContenedor' => $input['pContenedor'] ?? null,
            'pOrigen' => $input['pOrigen'] ?? null,
            'pDestino' => $input['pDestino'] ?? null,
            'pAduana' => $input['pAduana'] ?? null,
            'pSeccion' => $input['pSeccion'] ?? null,
            'pModo' => $input['pModo'] ?? null,
            'pIncoterm' => $input['pIncoterm'] ?? null,
            'pConsignatario' => $input['pConsignatario'] ?? null,
            'pContacto' => $input['pContacto'] ?? null,
            'pImportadorRfc' => $input['pImportadorRfc'] ?? null,
            'pCertTratado' => $input['pCertTratado'] ?? null,
            'pCertVigencia' => $input['pCertVigencia'] ?? null,
            'pEtd' => $input['pEtd'] ?? null,
            'pEta' => $input['pEta'] ?? null,
            'pFechaEstIngreso' => $input['pFechaEstIngreso'] ?? null,
            'pCantidad' => is_numeric($input['pCantidad'] ?? null) ? (int)$input['pCantidad'] : null,
            'pPesoNeto' => is_numeric($input['pPesoNeto'] ?? null) ? (float)$input['pPesoNeto'] : null,
            'pVolumen' => is_numeric($input['pVolumen'] ?? null) ? (float)$input['pVolumen'] : null,
            'pAncho' => is_numeric($input['pAncho'] ?? null) ? (float)$input['pAncho'] : null,
            'pAlto' => is_numeric($input['pAlto'] ?? null) ? (float)$input['pAlto'] : null,
            'pLargo' => is_numeric($input['pLargo'] ?? null) ? (float)$input['pLargo'] : null,
            'pPermisosJson' => $permisosJson,
            'pEstado' => $input['pEstado'] ?? null
        ];

        // Validaciones mínimas
        $errors = [];
        if (empty($spParams['pGuia'])) $errors['pGuia'] = 'Guía es requerida';
        if (empty($spParams['pCantidad'])) $errors['pCantidad'] = 'Cantidad es requerida';

        if (!empty($errors)) {
            $this->view('guia/create', ['errors' => $errors, 'old' => $input]);
            return;
        }

        try {
            $id = $this->modelo->crearConSP($spParams);
            if ($id) {
                // registrar bitácora con usuario actual
                $userId = $_SESSION['usuario_id'] ?? null;
                if ($userId === null) {
                    throw new Exception('Usuario no identificado en sesión al crear guía');
                }
                $bitModel = $this->model('Bitacora');
                $bitModel->log('guia', (int)$id, 'crear', null, json_encode($spParams, JSON_UNESCAPED_UNICODE), (int)$userId);

                refresh('/guias');
                return;
            }
            $errors['general'] = 'No se pudo crear la guía (sin id devuelto).';
            $this->view('guia/create', ['errors' => $errors, 'old' => $input]);
        } catch (Exception $e) {
            $errors['exception'] = $e->getMessage();
            $this->view('guia/create', ['errors' => $errors, 'old' => $input]);
        }
    }

    /**
     * Mostrar formulario de edición (usa la misma vista de create pero con datos)
     * Ruta esperada: /guias/editg/{id}
     */
    public function editg($id = null)
    {
        if (!estaLogueado()) {
            refresh('/login');
            return;
        }

        if (empty($id)) {
            refresh('/guias');
            return;
        }

        // cargar guia y registros relacionados
        $guia = $this->modelo->find((int)$id);
        if (empty($guia)) {
            refresh('/guias');
            return;
        }

        $ident = $this->model('Identificadores')->find((int)$guia['idIdentificadores']);
        $log = $this->model('Logistica')->find((int)$guia['idLogistica']);
        $partes = $this->model('Partes')->find((int)$guia['idPartes']);
        $cert = null;
        if (!empty($guia['idCertificadoOrigen'])) {
            $cert = $this->model('Certificado')->find((int)$guia['idCertificadoOrigen']);
        }
        $bultos = $this->model('Bultos')->find((int)$guia['idBultos']);
        $fechas = $this->model('Fechas')->find((int)$guia['idFechas']);
        $permisos = $this->model('Permisos')->findByGuia((int)$id);

        // preparar old data en el mismo formato que el formulario create.php espera
        $old = [];
        // identificadores
        $old['pIdGuia'] = $id;
        $old['pGuia'] = $ident['guia'] ?? '';
        $old['pMaster'] = $ident['master'] ?? '';
        $old['pContenedor'] = $ident['contenedor'] ?? '';
        // logistica
        $old['pOrigen'] = $log['origen'] ?? '';
        $old['pDestino'] = $log['destino'] ?? '';
        $old['pAduana'] = $log['aduana'] ?? '';
        $old['pSeccion'] = $log['seccion'] ?? '';
        $old['pModo'] = $log['modo'] ?? '';
        $old['pIncoterm'] = $log['incoterm'] ?? '';
        // partes
        $old['pConsignatario'] = $partes['consignatario'] ?? '';
        $old['pContacto'] = $partes['contacto'] ?? '';
        $old['pImportadorRfc'] = $partes['importadorRfc'] ?? '';
        // certificado
        $old['pCertTratado'] = $cert['tratado'] ?? '';
        $old['pCertVigencia'] = !empty($cert['vigencia']) ? date('Y-m-d', strtotime($cert['vigencia'])) : '';
        // bultos
        $old['pCantidad'] = $bultos['cantidad'] ?? '';
        $old['pPesoNeto'] = $bultos['pesoNeto'] ?? '';
        $old['pVolumen'] = $bultos['volumen'] ?? '';
        $old['pAncho'] = $bultos['ancho'] ?? '';
        $old['pAlto'] = $bultos['alto'] ?? '';
        $old['pLargo'] = $bultos['largo'] ?? '';
        // fechas
        $old['pEtd'] = !empty($fechas['etd']) ? date('Y-m-d', strtotime($fechas['etd'])) : '';
        $old['pEta'] = !empty($fechas['eta']) ? date('Y-m-d', strtotime($fechas['eta'])) : '';
        $old['pFechaEstIngreso'] = !empty($fechas['fechaEstimadaIngreso']) ? date('Y-m-d', strtotime($fechas['fechaEstimadaIngreso'])) : '';

        // permisos -> serializar a JSON en el mismo formato que el formulario espera
        $permArr = [];
        foreach ($permisos as $p) {
            $permArr[] = [
                'tipo' => $p['tipo'] ?? '',
                'autoridad' => $p['autoridad'] ?? null,
                'vigencia' => !empty($p['vigencia']) ? date('Y-m-d', strtotime($p['vigencia'])) : null
            ];
        }
        $old['pPermisosJson'] = count($permArr) ? json_encode($permArr) : '';

        $data = ['errors' => [], 'old' => $old, 'edit' => true];
        $this->view('guia/create', $data);
    }

    /**
     * Procesar actualización de guía vía SP actualizarGuia
     */
    public function update()
    {
        if (!estaLogueado()) {
            refresh('/login');
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

        $permisosJson = $input['pPermisosJson'] ?? null;
        if ($permisosJson === '') $permisosJson = null;

        // valor anterior para bitácora
        $oldGuia = $this->modelo->find((int)$pIdGuia);

        $spParams = [
            'pIdGuia' => $pIdGuia,
            'pIdUsuario' => $_SESSION['usuario_id'] ?? null,
            'pGuia' => $input['pGuia'] ?? null,
            'pMaster' => $input['pMaster'] ?? null,
            'pContenedor' => $input['pContenedor'] ?? null,
            'pOrigen' => $input['pOrigen'] ?? null,
            'pDestino' => $input['pDestino'] ?? null,
            'pAduana' => $input['pAduana'] ?? null,
            'pSeccion' => $input['pSeccion'] ?? null,
            'pModo' => $input['pModo'] ?? null,
            'pIncoterm' => $input['pIncoterm'] ?? null,
            'pConsignatario' => $input['pConsignatario'] ?? null,
            'pContacto' => $input['pContacto'] ?? null,
            'pImportadorRfc' => $input['pImportadorRfc'] ?? null,
            'pCertTratado' => $input['pCertTratado'] ?? null,
            'pCertVigencia' => $input['pCertVigencia'] ?? null,
            'pEtd' => $input['pEtd'] ?? null,
            'pEta' => $input['pEta'] ?? null,
            'pFechaEstIngreso' => $input['pFechaEstIngreso'] ?? null,
            'pCantidad' => is_numeric($input['pCantidad'] ?? null) ? (int)$input['pCantidad'] : null,
            'pPesoNeto' => is_numeric($input['pPesoNeto'] ?? null) ? (float)$input['pPesoNeto'] : null,
            'pVolumen' => is_numeric($input['pVolumen'] ?? null) ? (float)$input['pVolumen'] : null,
            'pAncho' => is_numeric($input['pAncho'] ?? null) ? (float)$input['pAncho'] : null,
            'pAlto' => is_numeric($input['pAlto'] ?? null) ? (float)$input['pAlto'] : null,
            'pLargo' => is_numeric($input['pLargo'] ?? null) ? (float)$input['pLargo'] : null,
            'pPermisosJson' => $permisosJson,
            'pEstado' => $input['pEstado'] ?? null
        ];

        $errors = [];
        if (empty($spParams['pGuia'])) $errors['pGuia'] = 'Guía es requerida';

        if (!empty($errors)) {
            $this->view('guia/create', ['errors' => $errors, 'old' => $input, 'edit' => true]);
            return;
        }

        try {
            $affected = $this->modelo->actualizarConSP($spParams);

            // registrar bitácora
            $userId = $_SESSION['usuario_id'] ?? null;
            if ($userId === null) {
                throw new Exception('Usuario no identificado en sesión al actualizar guía');
            }
            $bitModel = $this->model('Bitacora');
            $bitModel->log(
                'guia',
                (int)$pIdGuia,
                'actualizar',
                json_encode($oldGuia, JSON_UNESCAPED_UNICODE),
                json_encode($spParams, JSON_UNESCAPED_UNICODE),
                (int)$userId
            );

            // redirigir a lista
            refresh('/guias');
            return;
        } catch (Exception $e) {
            $errors['exception'] = $e->getMessage();
            $this->view('guia/create', ['errors' => $errors, 'old' => $input, 'edit' => true]);
        }
    }

    /**
     * Mostrar identificadores por id de identificadores
     * La ruta será: /guias/identificadores/{idIdentificadores}
     */
    public function identificadores($idIdentificadores = null)
    {
        if (!estaLogueado()) {
            $this->view('identificadores/show', ['identificadores' => null]);
            return;
        }

        if (empty($idIdentificadores)) {
            // redirigir de vuelta a lista de guias si no hay id
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Identificadores');
        $ident = $modelo->find((int)$idIdentificadores);

        // intentar localizar la guia que referencia este identificador
        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByIdentificadores((int)$idIdentificadores);

        $data = [
            'identificadores' => $ident,
            'guiaId' => $guia['id'] ?? null
        ];

        $this->view('identificadores/show', $data);
    }

    public function logistica($idLogistica = null)
    {
        if (!estaLogueado()) {
            $this->view('logistica/show', ['logistica' => null]);
            return;
        }

        if (empty($idLogistica)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Logistica');
        $reg = $modelo->find((int)$idLogistica);
        // Asegurar que 'seccion' esté presente; si falta, usar valor por defecto
        if (is_array($reg) && (!isset($reg['seccion']) || $reg['seccion'] === null || $reg['seccion'] === '')) {
            $reg['seccion'] = 'Importación';
        }

        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByLogistica((int)$idLogistica);

        $this->view('logistica/show', [
            'logistica' => $reg,
            'guiaId' => $guia['id'] ?? null
        ]);
    }

    public function partes($idPartes = null)
    {
        if (!estaLogueado()) {
            $this->view('partes/show', ['partes' => null]);
            return;
        }

        if (empty($idPartes)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Partes');
        $reg = $modelo->find((int)$idPartes);

        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByPartes((int)$idPartes);

        $this->view('partes/show', [
            'partes' => $reg,
            'guiaId' => $guia['id'] ?? null
        ]);
    }

    /**
     * Mostrar tabla de documentos disponibles para una guía
     * URL: /guias/documentos/{id}
     */
    public function documentos($id = null)
    {
        if (!estaLogueado()) {
            $this->view('guia/documentos', ['guiaId' => null, 'guia' => null, 'tieneIncidencias' => false]);
            return;
        }

        if (empty($id)) {
            refresh('/guias');
            return;
        }

        $guia = $this->modelo->find((int)$id);
        
        // Verificar si la guía tiene incidencias asociadas
        $incidenciaModel = $this->model('Incidencia');
        $incidencias = $incidenciaModel->countByGuiaIds([(int)$id]);
        $tieneIncidencias = $incidencias > 0;

        $this->view('guia/documentos', [
            'guiaId' => $id,
            'guia' => $guia,
            'tieneIncidencias' => $tieneIncidencias
        ]);
    }

    public function fechas($idFechas = null)
    {
        if (!estaLogueado()) {
            $this->view('fechas/show', ['fechas' => null]);
            return;
        }

        if (empty($idFechas)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Fechas');
        $reg = $modelo->find((int)$idFechas);

        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByFechas((int)$idFechas);

        $this->view('fechas/show', [
            'fechas' => $reg,
            'guiaId' => $guia['id'] ?? null
        ]);
    }

    public function bultos($idBultos = null)
    {
        if (!estaLogueado()) {
            $this->view('bultos/show', ['bultos' => null]);
            return;
        }

        if (empty($idBultos)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Bultos');
        $reg = $modelo->find((int)$idBultos);

        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByBultos((int)$idBultos);

        $this->view('bultos/show', [
            'bultos' => $reg,
            'guiaId' => $guia['id'] ?? null
        ]);
    }

    public function certificado($idCertificado = null)
    {
        if (!estaLogueado()) {
            $this->view('certificado/show', ['certificado' => null]);
            return;
        }

        if (empty($idCertificado)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Certificado');
        $reg = $modelo->find((int)$idCertificado);

        $modeloGuia = $this->model('Guia');
        $guia = $modeloGuia->findByCertificadoOrigen((int)$idCertificado);

        $this->view('certificado/show', [
            'certificado' => $reg,
            'guiaId' => $guia['id'] ?? null
        ]);
    }

    /**
     * Mostrar comprobante asociado a una guía (buscar por idGuia)
     * Ruta: /guias/comprobante/{idGuia}
     */
    public function comprobante($idGuia = null)
    {
        if (!estaLogueado()) {
            $this->view('comprobante/show', ['comprobante' => null]);
            return;
        }

        if (empty($idGuia)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Comprobante');
        $reg = $modelo->findByGuia((int)$idGuia);

        $this->view('comprobante/show', [
            'comprobante' => $reg,
            'guiaId' => (int)$idGuia
        ]);
    }

    /**
     * Mostrar permisos asociados a una guía
     * Ruta: /guias/permisos/{idGuia}
     */
    public function permisos($idGuia = null)
    {
        if (!estaLogueado()) {
            $this->view('permisos/show', ['permisos' => []]);
            return;
        }

        if (empty($idGuia)) {
            refresh('/guias');
            return;
        }

        $modelo = $this->model('Permisos');
        $permisos = $modelo->findByGuia((int)$idGuia);

        $this->view('permisos/show', [
            'permisos' => $permisos,
            'guiaId' => (int)$idGuia
        ]);
    }

    /**
     * Operaciones rápidas de estado según rol
     */
    public function enviarARecinto($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) {
            refresh('/guias');
            return;
        }
        if (empty($id)) { refresh('/guias'); return; }
        $userId = $_SESSION['usuario_id'] ?? null;
        $this->modelo->updateEstadoConSP((int)$id, 'enRecinto', $userId);
        refresh('/guias');
    }

    public function marcarPagado($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) { refresh('/guias'); return; }
        if (empty($id)) { refresh('/guias'); return; }
        
        // Redirigir al formulario de comprobante en el nuevo controlador
        refresh('/comprobantes/crear/' . $id);
    }

    /**
     * Ir a formulario de pedimento
     */
    public function elaborarPedimento($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) { refresh('/guias'); return; }
        if (empty($id)) { refresh('/guias'); return; }
        
        refresh('/pedimentos/crear/' . $id);
    }

    public function pasarARecinto($id = null)
    {
        // Recinto user action - change to enRecinto
        if (!$this->checkPermissionByRole(['Recinto'])) { refresh('/guias'); return; }
        if (empty($id)) { refresh('/guias'); return; }
        $userId = $_SESSION['usuario_id'] ?? null;
        $this->modelo->updateEstadoConSP((int)$id, 'enRecinto', $userId);
        refresh('/guias');
    }

    /**
     * Ir a formulario de recepción
     */
    public function recepcion($id = null)
    {
        if (!$this->checkPermissionByRole(['Recinto'])) { 
            refresh('/guias'); 
            return; 
        }
        
        if (empty($id)) { 
            refresh('/guias'); 
            return; 
        }
        
        // Redirigir al formulario de recepción en el nuevo controlador
        refresh('/recepcion/crear/' . $id);
    }

    /**
     * Ir a formulario de POD
     */
    public function registrarPOD($id = null)
    {
        if (!$this->checkPermissionByRole(['Operador'])) {
            refresh('/guias');
            return;
        }

        if (empty($id)) {
            refresh('/guias');
            return;
        }

        // Redirigir al formulario de POD en el controlador Pods
        refresh('/pods/registrar/' . $id);
    }

    /**
     * Ir a procesar POD 
     */
    public function storePOD()
    {
        // Esta función solo redirige; el procesamiento se hace en Pods::store()
        refresh('/pods/store');
    }

    /**
     * Eliminar una guía (Supervisor y Operador)
     * URL: /guias/destroy/{id}
     */
    public function destroy($id = null)
    {
        if (!$this->checkPermissionByRole(['Supervisor', 'Operador'])) {
            refresh('/guias');
            return;
        }

        if (empty($id)) {
            refresh('/guias');
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;
            
            if ($userId === null) {
                error_log("Error al eliminar guía {$id}: usuario_id no está en sesión");
                $_SESSION['error'] = 'No se pudo eliminar: sesión inválida';
                refresh('/guias');
                return;
            }
            
            $affected = $this->modelo->eliminarConSP((int)$id, $userId);
            
            error_log("Intentando eliminar guía {$id}, afectados: " . var_export($affected, true));

            if ($affected > 0) {
                $_SESSION['success'] = 'Guía eliminada correctamente';
                refresh('/guias');
                return;
            }
            
            $_SESSION['error'] = 'No se pudo eliminar la guía';
            refresh('/guias');
        } catch (Exception $e) {
            error_log("Error al eliminar guía {$id}: " . $e->getMessage());
            
            // Detectar error de restricción de FK
            if (strpos($e->getMessage(), 'Integrity constraint violation') !== false || 
                strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                $_SESSION['error'] = 'No se puede eliminar la guía porque tiene registros relacionados (comprobantes, pedimentos, liberaciones, retiros, POD, bultos reales, etc.). Elimine primero estos registros o contacte al administrador.';
            } else {
                $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
            }
            
            refresh('/guias');
        }
    }

    /**
     * Generar reporte mensual en PDF
     * Muestra guías cuya fechaEstimadaIngreso esté en el último mes
     * URL: /guias/reporteMensual
     */
    public function reporteMensual()
    {
        if (!estaLogueado()) {
            refresh('usuarios/login');
            return;
        }

        $fechaHoy = date('Y-m-d');
        $guias = $this->modelo->getGuiasUltimoMes($fechaHoy);
        
        // Usar servicio de PDF
        $pdfService = $this->getPdfService();
        $pdfService->generarReporteMensual($guias);
    }

    /**
     * Generar reporte diario en PDF
     * Muestra guías cuya fechaEstimadaIngreso sea el día actual
     * URL: /guias/reporteDiario
     */
    public function reporteDiario()
    {
        if (!estaLogueado()) {
            refresh('usuarios/login');
            return;
        }

        $fechaHoy = date('Y-m-d');
        $guias = $this->modelo->getGuiasPorDia($fechaHoy);
        
        // Usar servicio de PDF
        $pdfService = $this->getPdfService();
        $pdfService->generarReporteDiario($guias);
    }

    /**
     * Generar reporte de incidencias del último mes
     * Muestra todas las incidencias creadas en el último mes
     * URL: /guias/reporteIncidencias
     */
    public function reporteIncidencias()
    {
        if (!estaLogueado()) {
            refresh('usuarios/login');
            return;
        }

        // Obtener incidencias del último mes
        $baseIncidencia = new Base('incidencia');
        
        // Calcular fecha de hace un mes
        $fechaHoy = date('Y-m-d');
        $fechaUnMesAtras = date('Y-m-d', strtotime('-1 month'));
        
        // Query para obtener incidencias del último mes
        $sql = "SELECT i.id,
                       ti.tipoIncidencia as tipo,
                       i.descripcion,
                       g.id as idGuia, 
                       iden.guia as numeroGuia,
                       iden.master,
                       log.aduana,
                       f.fechaEstimadaIngreso as fecha,
                       'Registrada' as estado
                FROM incidencia i
                INNER JOIN guia g ON i.idGuia = g.id
                LEFT JOIN tiposincidencia ti ON i.idTiposIncidencia = ti.id
                LEFT JOIN identificadores iden ON g.idIdentificadores = iden.id
                LEFT JOIN logistica log ON g.idLogistica = log.id
                LEFT JOIN fechas f ON g.idFechas = f.id
                WHERE DATE(f.fechaEstimadaIngreso) BETWEEN :fechaInicio AND :fechaFin
                ORDER BY f.fechaEstimadaIngreso DESC";
        
        $incidencias = $baseIncidencia->raw($sql, [
            ':fechaInicio' => $fechaUnMesAtras,
            ':fechaFin' => $fechaHoy
        ], 'all');
        
        // Usar servicio de PDF
        $pdfService = $this->getPdfService();
        $pdfService->generarReporteIncidencias($incidencias);
    }
}


