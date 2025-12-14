<?php

class Comprobantes extends Controller
{
    private $modelo;
    private $guiaModelo;

    public function __construct()
    {
        $this->modelo = $this->model('Comprobante');
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Mostrar formulario de comprobante
     * URL: /comprobante/crear/{id}
     */
    public function crear($id = null)
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

        $totalSugerido = $this->calcularTotalPreliquidacion($guia['id'] ?? null, $guia);

        $this->view('comprobante/formComprobante', [
            'guia' => $guia,
            'errors' => [],
            'old' => ['pTotal' => $totalSugerido]
        ]);
    }

    /**
     * Guardar comprobante y cambiar estado de la guía a pagado
     * URL POST: /comprobante/store
     */
    public function store()
    {
        // 1. Validar permisos (solo Operador)
        if (!$this->checkPermissionByRole(['Operador'])) { 
            refresh('/guias'); 
            return; 
        }

        // 2. Validar que sea POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            refresh('/guias'); 
            return; 
        }

        $input = array_map('trim', $_POST);
        
        // 3. Obtener idGuia válido del formulario
        $pIdGuia = isset($input['pIdGuia']) ? (int)$input['pIdGuia'] : null;

        if (empty($pIdGuia)) { 
            refresh('/guias'); 
            return; 
        }

        // 4. Recuperar datos de la guía
        $guia = $this->guiaModelo->find($pIdGuia);
        if (empty($guia)) { 
            refresh('/guias'); 
            return; 
        }

        // 5. Si falta total, calcula sugerido usando fórmula de preliquidación
        if ($input['pTotal'] === '' || $input['pTotal'] === null) {
            $sugerido = $this->calcularTotalPreliquidacion($pIdGuia, $guia);
            if ($sugerido !== null) {
                $input['pTotal'] = $sugerido;
            }
        }

        // 6. Validar datos del formulario con el modelo
        $errors = $this->modelo->validate($input);

        // 7. Si hay errores, devolver formulario con mensajes de error
        if (!empty($errors)) {
            $this->view('comprobante/formComprobante', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }

        try {
            $userId = $_SESSION['usuario_id'] ?? null;

            // 8. Registrar comprobante via stored procedure
            $result = $this->modelo->registrarConSP([
                'pIdGuia' => $pIdGuia,
                'pNumero' => $input['pNumero'],
                'pEmisor' => $input['pEmisor'],
                'pMoneda' => $input['pMoneda'],
                'pTotal' => (float)$input['pTotal'],
                'pIdUsuario' => $userId
            ]);

            // 9. Si se registró exitosamente, actualizar estado de guía a "pagado"
            if ($result['idComprobante']) {
                $this->guiaModelo->updateEstadoConSP($pIdGuia, 'pagado', $userId);
                refresh('/guias');
                return;
            }

            // Si no se creó el comprobante, mostrar error
            $errors['general'] = 'No se pudo registrar el comprobante';
            $this->view('comprobante/formComprobante', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        } catch (Exception $e) {
            // 10. Capturar y mostrar excepciones en el formulario
            $errors['exception'] = $e->getMessage();
            $this->view('comprobante/formComprobante', [
                'guia' => $guia,
                'errors' => $errors,
                'old' => $input
            ]);
        }
    }

    /**
     * Mostrar comprobante asociado a una guía
     * URL: /comprobante/show/{idGuia}
     */
    public function show($idGuia = null)
    {
        if (!estaLogueado()) {
            $this->view('comprobante/show', ['comprobante' => null]);
            return;
        }

        if (empty($idGuia)) {
            refresh('/guias');
            return;
        }

        $reg = $this->modelo->findByGuia((int)$idGuia);

        $this->view('comprobante/show', [
            'comprobante' => $reg,
        ]);
    }

    /**
     * Calcular total sugerido usando la fórmula de preliquidación
     * 
     * Fórmula:
     * Total = (peso × tarifaKg) + (volumen × tarifaVolumen) 
     *         + (pesoExcedente × tarifaKgExtra) + (volumenExcedente × tarifaVolumenExtra)
     * 
     * Donde:
     * - peso: del bulto original
     * - volumen: del bulto original
     * - pesoExcedente: max(0, pesoReal - peso)
     * - volumenExcedente: max(0, volumenReal - volumen)
     * 
     * Retorna el subtotal redondeado a 2 decimales
     */
    private function calcularTotalPreliquidacion($idGuia, $guia)
    {
        if (empty($idGuia) || empty($guia)) {
            return null;
        }

        $bultosModel = $this->model('Bultos');
        $bultosRealModel = $this->model('BultosReal');
        $tarifasModel = $this->model('Tarifas');

        $bultos = $bultosModel->find($guia['idBultos']) ?? [];
        $bultosReal = $bultosRealModel->findByGuia($idGuia) ?? [];
        $tarifas = $tarifasModel->getCurrent() ?? [];

        if (empty($tarifas)) {
            return null;
        }

        $peso = (float)($bultos['pesoNeto'] ?? 0);
        $volumen = (float)($bultos['volumen'] ?? 0);
        $pesoReal = (float)($bultosReal['pesoNeto'] ?? $peso);
        $volumenReal = (float)($bultosReal['volumen'] ?? $volumen);

        $pesoExcedente = max(0, $pesoReal - $peso);
        $volumenExcedente = max(0, $volumenReal - $volumen);

        $tarifaKg = (float)($tarifas['kg'] ?? 0);
        $tarifaVolumen = (float)($tarifas['volumen'] ?? 0);
        $tarifaKgExtra = (float)($tarifas['kgExtra'] ?? 0);
        $tarifaVolumenExtra = (float)($tarifas['volumenExtra'] ?? 0);

        $montoPeso = $peso * $tarifaKg;
        $montoVolumen = $volumen * $tarifaVolumen;
        $montoPesoExcedente = $pesoExcedente * $tarifaKgExtra;
        $montoVolumenExcedente = $volumenExcedente * $tarifaVolumenExtra;

        $subtotal = $montoPeso + $montoVolumen + $montoPesoExcedente + $montoVolumenExcedente;

        return round($subtotal, 2);
    }
}
