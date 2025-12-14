<?php
/**
 * Controlador Pdfs
 * Gestiona la generación de documentos PDF
 */

class Pdfs extends Controller
{
    private $guiaModelo;

    public function __construct()
    {
        $this->guiaModelo = $this->model('Guia');
    }

    /**
     * Ruteo para generación de PDFs
     * Delega la lógica a PdfService según el tipo de documento
     * URL: /pdfs/generar/{tipo}/{id}
     */
    public function generar($tipo = null, $id = null)
    {
        if (!estaLogueado()) {
            refresh('usuarios/login');
            return;
        }

        $idGuia = (int)$id;
        
        // Registrar en bitácora PDF antes de generar el documento
        if (!empty($tipo) && $idGuia > 0) {
            $this->registrarBitacoraPDF($tipo, $idGuia);
        }

        try {
            $pdfService = $this->getPdfService();
            
            // Determinar qué documento generar y recopilar datos
            switch ($tipo) {
                case 'actaRecepcion':
                    $this->generarActaRecepcionViaPdfService($idGuia, $pdfService);
                    break;
                case 'actaIncidencia':
                    $this->generarActaIncidenciaViaPdfService($idGuia, $pdfService);
                    break;
                case 'preliquidacion':
                    $this->generarPreliquidacionViaPdfService($idGuia, $pdfService);
                    break;
                case 'pedimento':
                    $this->generarPedimentoViaPdfService($idGuia, $pdfService);
                    break;
                case 'comprobantePago':
                    $this->generarComprobantePagoViaPdfService($idGuia, $pdfService);
                    break;
                case 'comprobanteLiberacion':
                    $this->generarComprobanteLiberacionViaPdfService($idGuia, $pdfService);
                    break;
                case 'comprobanteRetiro':
                    $this->generarComprobanteRetiroViaPdfService($idGuia, $pdfService);
                    break;
                case 'pod':
                    $this->generarPODViaPdfService($idGuia, $pdfService);
                    break;
                default:
                    refresh('/guias');
            }
        } catch (Exception $e) {
            error_log("Error al generar PDF: " . $e->getMessage());
            refresh('/guias');
        }
    }

    /**
     * Registrar en bitácora PDF cada vez que se genera un documento
     */
    private function registrarBitacoraPDF($tipoPDF, $idGuia)
    {
        try {
            $idUsuario = $_SESSION['usuario_id'] ?? null;
            
            if (!$idUsuario) {
                return;
            }

            $bitacoraPDFModel = $this->model('BitacoraPDF');
            $bitacoraPDFModel->registrarConSP([
                'pIdUsuario' => $idUsuario,
                'pTipoPDF' => $tipoPDF,
                'pIdGuia' => $idGuia
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar en bitácora PDF: " . $e->getMessage());
        }
    }

    /**
     * Wrapper para generar Acta de Recepción usando PdfService
     * Recopila datos y delega a PdfService::generarActaRecepcion()
     */
    private function generarActaRecepcionViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        // Recopilar todos los datos necesarios
        $identificadoresModel = $this->model('Identificadores');
        $logisticaModel = $this->model('Logistica');
        $fechasModel = $this->model('Fechas');
        $bultosModel = $this->model('Bultos');
        $certificadoModel = $this->model('Certificado');
        $bultosRealModel = $this->model('BultosReal');
        $incidenciaModel = $this->model('Incidencia');
        $usuarioModel = $this->model('Usuario');
        $tiposIncidenciaModel = $this->model('TiposIncidencia');

        // Obtener datos relacionados
        $identificadores = $identificadoresModel->find($guia['idIdentificadores']) ?? [];
        $logistica = $logisticaModel->find($guia['idLogistica']) ?? [];
        $fechas = $fechasModel->find($guia['idFechas']) ?? [];
        $bultos = $bultosModel->find($guia['idBultos']) ?? [];
        $certificado = $certificadoModel->find($guia['idCertificadoOrigen']) ?? [];
        $bultosReal = $bultosRealModel->findByGuia($idGuia) ?? [];
        $usuarioActual = $usuarioModel->find($_SESSION['usuario_id']) ?? [];
        
        // Obtener incidencias
        $incidencias = [];
        $incidenciasCount = $incidenciaModel->countByGuiaIds([$idGuia]);
        $tieneIncidencias = $incidenciasCount > 0;

        if ($tieneIncidencias) {
            $baseIncidencias = new Base('incidencia');
            $incidencias = $baseIncidencias->raw(
                "SELECT i.descripcion, i.idTiposIncidencia 
                FROM incidencia i 
                WHERE i.idGuia = :idGuia",
                [':idGuia' => $idGuia]
            ) ?? [];

            // Obtener tipos de incidencia
            if (!empty($incidencias)) {
                foreach ($incidencias as &$inc) {
                    $tipoData = $tiposIncidenciaModel->find($inc['idTiposIncidencia']);
                    if ($tipoData && is_array($tipoData)) {
                        $inc['tipo'] = $tipoData['tipo'] ?? 
                                      $tipoData['nombre'] ?? 
                                      $tipoData['tiposIncidencia'] ?? 
                                      $tipoData['descripcion'] ??
                                      $tipoData['tipoIncidencia'] ?? 'N/A';
                        
                        if ($inc['tipo'] === 'N/A') {
                            foreach ($tipoData as $key => $value) {
                                if ($key !== 'id' && !empty($value)) {
                                    $inc['tipo'] = $value;
                                    break;
                                }
                            }
                        }
                    } else {
                        $inc['tipo'] = 'N/A';
                    }
                }
            }
        }

        // Preparar datos para PdfService
        $data = [
            'identificadores' => $identificadores,
            'logistica' => $logistica,
            'fechas' => $fechas,
            'certificado' => $certificado,
            'bultos' => $bultos,
            'bultosReal' => $bultosReal,
            'incidencias' => $incidencias,
            'tieneIncidencias' => $tieneIncidencias,
            'nombreTrabajador' => $usuarioActual['nombre'] ?? 'N/A'
        ];

        // Delegar a PdfService
        $pdfService->generarActaRecepcion($idGuia, $data);
    }

    /**
     * Wrapper para generar Acta de Incidencia usando PdfService
     */
    private function generarActaIncidenciaViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        // Obtener incidencias
        $baseIncidencias = new Base('incidencia');
        $incidencias = $baseIncidencias->raw(
            "SELECT i.descripcion, i.idTiposIncidencia 
            FROM incidencia i 
            WHERE i.idGuia = :idGuia",
            [':idGuia' => $idGuia]
        ) ?? [];

        // Obtener tipos
        if (!empty($incidencias)) {
            $tiposModel = $this->model('TiposIncidencia');
            foreach ($incidencias as &$inc) {
                $tipoData = $tiposModel->find($inc['idTiposIncidencia']);
                if ($tipoData && is_array($tipoData)) {
                    $inc['tipo'] = $tipoData['tipo'] ?? 
                                  $tipoData['nombre'] ?? 
                                  $tipoData['tiposIncidencia'] ?? 
                                  $tipoData['descripcion'] ??
                                  $tipoData['tipoIncidencia'] ?? 'N/A';
                    
                    if ($inc['tipo'] === 'N/A') {
                        foreach ($tipoData as $key => $value) {
                            if ($key !== 'id' && !empty($value)) {
                                $inc['tipo'] = $value;
                                break;
                            }
                        }
                    }
                } else {
                    $inc['tipo'] = 'N/A';
                }
            }
        }

        $usuarioModel = $this->model('Usuario');
        $usuarioActual = $usuarioModel->find($_SESSION['usuario_id']) ?? [];

        $data = [
            'guia' => $guia,
            'incidencias' => $incidencias,
            'nombreOperador' => $usuarioActual['nombre'] ?? 'N/A'
        ];

        $pdfService->generarActaIncidencia($idGuia, $data);
    }

    /**
     * Wrapper para generar Pedimento usando PdfService
     */
    private function generarPedimentoViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        $pedimentoModel = $this->model('Pedimento');
        $logisticaModel = $this->model('Logistica');
        $comprobanteModel = $this->model('Comprobante');
        $usuarioModel = $this->model('Usuario');

        $pedimento = $pedimentoModel->findByGuia($idGuia) ?? [];
        $logistica = $logisticaModel->find($guia['idLogistica']) ?? [];
        $comprobante = !empty($pedimento['idComprobante']) ? 
                       $comprobanteModel->find($pedimento['idComprobante']) : [];
        $usuarioActual = $usuarioModel->find($_SESSION['usuario_id']) ?? [];

        $data = [
            'pedimento' => $pedimento,
            'logistica' => $logistica,
            'comprobante' => $comprobante,
            'nombreOperador' => $usuarioActual['nombre'] ?? 'N/A'
        ];

        $pdfService->generarPedimento($idGuia, $data);
    }

    /**
     * Wrapper para generar Comprobante de Pago usando PdfService
     */
    private function generarComprobantePagoViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        $comprobanteModel = $this->model('Comprobante');
        $comprobante = $comprobanteModel->findByGuia($idGuia) ?? [];
        
        if (empty($comprobante)) {
            refresh('/guias');
            return;
        }

        $data = [
            'comprobante' => $comprobante
        ];

        $pdfService->generarComprobantePago($idGuia, $data);
    }

    /**
     * Wrapper para generar Preliquidación usando PdfService
     */
    private function generarPreliquidacionViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        $identificadoresModel = $this->model('Identificadores');
        $logisticaModel = $this->model('Logistica');
        $bultosModel = $this->model('Bultos');
        $bultosRealModel = $this->model('BultosReal');
        $tarifasModel = $this->model('Tarifas');
        $usuarioModel = $this->model('Usuario');

        $identificadores = $identificadoresModel->find($guia['idIdentificadores']) ?? [];
        $logistica = $logisticaModel->find($guia['idLogistica']) ?? [];
        $bultos = $bultosModel->find($guia['idBultos']) ?? [];
        $bultosReal = $bultosRealModel->findByGuia($idGuia) ?? [];
        $tarifas = $tarifasModel->getCurrent() ?? [];
        $usuarioActual = $usuarioModel->find($_SESSION['usuario_id']) ?? [];

        if (empty($tarifas)) {
            refresh('/guias');
            return;
        }

        $data = [
            'logistica' => $logistica,
            'bultos' => $bultos,
            'bultosReal' => $bultosReal,
            'tarifas' => $tarifas,
            'nombreEmpleado' => $usuarioActual['nombre'] ?? 'N/A'
        ];

        $pdfService->generarPreliquidacion($idGuia, $data);
    }

    /**
     * Wrapper para generar Comprobante de Liberación usando PdfService
     */
    private function generarComprobanteLiberacionViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        $identificadoresModel = $this->model('Identificadores');
        $logisticaModel = $this->model('Logistica');
        $partesModel = $this->model('Partes');
        $pedimentoModel = $this->model('Pedimento');
        $comprobanteModel = $this->model('Comprobante');
        $liberacionModel = $this->model('Liberacion');
        $incidenciaModel = $this->model('Incidencia');
        $usuarioModel = $this->model('Usuario');
        $tiposIncidenciaModel = $this->model('TiposIncidencia');

        $identificadores = $identificadoresModel->find($guia['idIdentificadores']) ?? [];
        $logistica = $logisticaModel->find($guia['idLogistica']) ?? [];
        $partes = $partesModel->find($guia['idPartes']) ?? [];
        $pedimento = $pedimentoModel->findByGuia($idGuia) ?? [];
        $comprobante = $comprobanteModel->findByGuia($idGuia) ?? [];
        $liberacion = $liberacionModel->findByGuia($idGuia) ?? [];
        $supervisor = $usuarioModel->find($liberacion['idUsuario'] ?? null) ?? [];

        // Obtener incidencias
        $incidencias = [];
        $incidenciasCount = $incidenciaModel->countByGuiaIds([$idGuia]);
        $tieneIncidencias = $incidenciasCount > 0;

        if ($tieneIncidencias) {
            $baseIncidencias = new Base('incidencia');
            $incidencias = $baseIncidencias->raw(
                "SELECT i.descripcion, i.idTiposIncidencia 
                FROM incidencia i 
                WHERE i.idGuia = :idGuia",
                [':idGuia' => $idGuia]
            ) ?? [];

            if (!empty($incidencias)) {
                foreach ($incidencias as &$inc) {
                    $tipoData = $tiposIncidenciaModel->find($inc['idTiposIncidencia']);
                    if ($tipoData && is_array($tipoData)) {
                        $inc['tipo'] = $tipoData['tipo'] ?? 
                                      $tipoData['nombre'] ?? 
                                      $tipoData['tiposIncidencia'] ?? 
                                      $tipoData['descripcion'] ??
                                      $tipoData['tipoIncidencia'] ?? 'N/A';
                        
                        if ($inc['tipo'] === 'N/A') {
                            foreach ($tipoData as $key => $value) {
                                if ($key !== 'id' && !empty($value)) {
                                    $inc['tipo'] = $value;
                                    break;
                                }
                            }
                        }
                    } else {
                        $inc['tipo'] = 'N/A';
                    }
                }
            }
        }

        $data = [
            'identificadores' => $identificadores,
            'logistica' => $logistica,
            'partes' => $partes,
            'pedimento' => $pedimento,
            'comprobante' => $comprobante,
            'incidencias' => $incidencias,
            'tieneIncidencias' => $tieneIncidencias,
            'liberacion' => $liberacion,
            'nombreSupervisor' => $supervisor['nombre'] ?? 'N/A'
        ];

        $pdfService->generarComprobanteLiberacion($idGuia, $data);
    }

    /**
     * Wrapper para generar Comprobante de Retiro usando PdfService
     */
    private function generarComprobanteRetiroViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        if (!in_array($guia['estado'], ['retirado', 'entregado'])) {
            refresh('/guias');
            return;
        }

        $identificadoresModel = $this->model('Identificadores');
        $retiroModel = $this->model('Retiro');
        $usuarioModel = $this->model('Usuario');

        $identificadores = $identificadoresModel->find($guia['idIdentificadores']) ?? [];
        $retiro = $retiroModel->findByGuia($idGuia) ?? [];
        $idUsuarioRetiro = $_SESSION['usuario_id'] ?? null;
        $operadorRetiro = $usuarioModel->find($idUsuarioRetiro) ?? [];

        if (empty($retiro)) {
            refresh('/guias');
            return;
        }

        $data = [
            'identificadores' => $identificadores,
            'retiro' => $retiro,
            'nombreOperadorRetiro' => $operadorRetiro['nombre'] ?? 'N/A'
        ];

        $pdfService->generarComprobanteRetiro($idGuia, $data);
    }

    /**
     * Wrapper para generar POD usando PdfService
     */
    private function generarPODViaPdfService($idGuia, $pdfService)
    {
        $guia = $this->guiaModelo->find($idGuia);
        if (!$guia) {
            refresh('/guias');
            return;
        }

        if ($guia['estado'] !== 'entregado') {
            refresh('/guias');
            return;
        }

        $identificadoresModel = $this->model('Identificadores');
        $logisticaModel = $this->model('Logistica');
        $podModel = $this->model('POD');

        $identificadores = $identificadoresModel->find($guia['idIdentificadores']) ?? [];
        $logistica = $logisticaModel->find($guia['idLogistica']) ?? [];
        $pod = $podModel->findByGuia($idGuia) ?? [];

        if (empty($pod)) {
            refresh('/guias');
            return;
        }

        $data = [
            'identificadores' => $identificadores,
            'logistica' => $logistica,
            'pod' => $pod
        ];

        $pdfService->generarPOD($idGuia, $data);
    }
}
