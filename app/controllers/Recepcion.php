<?php
/**
 * Controlador Recepcion
 * Gestiona el registro de bultos reales y detección de incidencias
 */

class Recepcion extends Controller
{
    private $guiaModelo;

    public function __construct()
    {
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario de recepción para registrar bultos reales
     * URL: /recepcion/crear/{id}
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

        $bultos = $this->model('Bultos')->find((int)$guia['idBultos']);
        $bultosReal = $this->model('BultosReal')->findByGuia((int)$id);

        $this->view('recepcion/recepcion', [
            'guia' => $guia,
            'bultos' => $bultos,
            'bultosReal' => $bultosReal
        ]);
    }

    /**
     * Procesar POST de recepción y llamar al SP registrarBultosReal
     * URL POST: /recepcion/store
     * 
     */
    public function store()
    {
        // 1. Validar permisos (solo Recinto)
        if (!$this->checkPermissionByRole(['Recinto'])) { 
            refresh('/guias'); 
            return; 
        }

        // 2. Validar que sea POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            refresh('/guias'); 
            return; 
        }

        $input = array_map('trim', $_POST);
        
        // 3. Obtener y validar idGuia
        $pIdGuia = isset($input['pIdGuia']) ? (int)$input['pIdGuia'] : null;
        if (empty($pIdGuia)) { 
            refresh('/guias'); 
            return; 
        }

        // 4. Recuperar datos de guía y bultos registrados
        $guia = $this->guiaModelo->find($pIdGuia);
        if (empty($guia)) { 
            refresh('/guias'); 
            return; 
        }

        $bultosReg = $this->model('Bultos')->find((int)$guia['idBultos']);

        // 5. Recopilar parámetros del formulario
        $params = [
            'pIdGuia' => $pIdGuia,
            'pCantidad' => is_numeric($input['pCantidad'] ?? null) ? (int)$input['pCantidad'] : null,
            'pPesoNeto' => is_numeric($input['pPesoNeto'] ?? null) ? (float)$input['pPesoNeto'] : null,
            'pVolumen' => is_numeric($input['pVolumen'] ?? null) ? (float)$input['pVolumen'] : null,
            'pAncho' => is_numeric($input['pAncho'] ?? null) ? (float)$input['pAncho'] : null,
            'pAlto' => is_numeric($input['pAlto'] ?? null) ? (float)$input['pAlto'] : null,
            'pLargo' => is_numeric($input['pLargo'] ?? null) ? (float)$input['pLargo'] : null,
            'pIdUsuariosRecinto' => $_SESSION['usuario_id'] ?? null
        ];

        try {
            // 6. Llamar al SP registrarBultosReal
            $brModel = $this->model('BultosReal');
            $res = $brModel->registrarConSP($params);

            // 7. Comparar datos registrados vs reales (buscar discrepancias)
            $hasDiscrepancy = false;

            // Helper para comparar floats con tolerancia
            $floatDiff = function($a, $b){
                if ($a === null && $b === null) return false;
                if ($a === null || $b === null) return true;
                return abs((float)$a - (float)$b) > 0.01; // Tolerancia 0.01
            };

            // Comparar cantidad (int)
            if ((int)($bultosReg['cantidad'] ?? 0) !== (int)($params['pCantidad'] ?? 0)) {
                $hasDiscrepancy = true;
            }
            
            // Comparar peso, volumen, ancho, alto, largo con tolerancia
            if (!$hasDiscrepancy) $hasDiscrepancy = $floatDiff($bultosReg['pesoNeto'] ?? null, $params['pPesoNeto']);
            if (!$hasDiscrepancy) $hasDiscrepancy = $floatDiff($bultosReg['volumen'] ?? null, $params['pVolumen']);
            if (!$hasDiscrepancy) $hasDiscrepancy = $floatDiff($bultosReg['ancho'] ?? null, $params['pAncho']);
            if (!$hasDiscrepancy) $hasDiscrepancy = $floatDiff($bultosReg['alto'] ?? null, $params['pAlto']);
            if (!$hasDiscrepancy) $hasDiscrepancy = $floatDiff($bultosReg['largo'] ?? null, $params['pLargo']);

            // 8. Si hay discrepancias, marcar guía con estado "conIncidencia"
            if ($hasDiscrepancy) {
                $userId = $_SESSION['usuario_id'] ?? null;
                $this->guiaModelo->updateEstadoConSP((int)$pIdGuia, 'conIncidencia', $userId);
            }

            // 9. Redirigir a /guias
            refresh('/guias');
            return;
        } catch (Exception $e) {
            // Mostrar error en formulario
            $bultos = $this->model('Bultos')->find((int)$guia['idBultos']);
            $this->view('recepcion/recepcion', [
                'guia' => $guia,
                'bultos' => $bultos,
                'bultosReal' => null,
                'errors' => ['exception' => $e->getMessage()]
            ]);
        }
    }
}
