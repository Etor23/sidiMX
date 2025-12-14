<?php
/**
 * Servicio centralizado para generación de PDFs
 * Encapsula toda la lógica de creación de documentos PDF para:
 * - Actas de recepción
 * - Actas de incidencia
 * - Pedimentos
 * - Comprobantes de pago
 * - Reportes (mensual, diario, incidencias)
 * - Comprobantes de liberación y retiro
 * - POD (Proof of Delivery)
 * - Preliquidación
 */
class PdfService
{
    protected $fpdfPath = null;
    protected $appRoot = null;
    protected $logoPath = null;

    public function __construct()
    {
        $this->appRoot = APPROOT;
        $this->fpdfPath = APPROOT . '/../vendor/fpdf/fpdf.php';
        $this->logoPath = APPROOT . '/../public/assets/img/logo.png';
    }

    /**
     * Convertir string UTF-8 a ISO-8859-1 para FPDF
     * @param string $str
     * @return string
     */
    protected function utf8ToIso($str)
    {
        if (empty($str)) return '';
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
    }

    /**
     * Generar reporte mensual en PDF
     * Utilizado por: Guias::reporteMensual()
     */
    public function generarReporteMensual($guias)
    {
        require_once $this->fpdfPath;

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo (consistente con otros PDFs)
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        }

        // Título y fecha
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 12, $this->utf8ToIso('Reporte Mensual de Guías'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, $this->utf8ToIso('Fecha') . ': ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        // Definir anchos de columnas y centrar la tabla en A4 vertical
        $colIdW = 18; $colContW = 28; $colEstW = 22; $colModoW = 24; $colConsW = 40; $colCantW = 16; $colPesoW = 18; // total ~166mm para buen margen
        $totalTableWidth = $colIdW + $colContW + $colEstW + $colModoW + $colConsW + $colCantW + $colPesoW; // 166
        $pageWidth = $pdf->GetPageWidth() - 20; // márgenes laterales de 10mm
        $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;

        // Encabezado
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        $headerY = max(35, $pdf->GetY());
        $pdf->SetXY($tableOffsetX, $headerY);
        $pdf->Cell($colIdW, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell($colContW, 7, 'Contenedor', 1, 0, 'C', true);
        $pdf->Cell($colEstW, 7, 'Estado', 1, 0, 'C', true);
        $pdf->Cell($colModoW, 7, 'Modo', 1, 0, 'C', true);
        $pdf->Cell($colConsW, 7, 'Consignatario', 1, 0, 'C', true);
        $pdf->Cell($colCantW, 7, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell($colPesoW, 7, 'Peso (kg)', 1, 1, 'C', true);

        // Datos
        $pdf->SetFont('Arial', '', 8);
        $currentY = $pdf->GetY();
        $rowH = 6;
        foreach ($guias as $guia) {
            $pdf->SetXY($tableOffsetX, $currentY);
            $pdf->Cell($colIdW, 6, $this->utf8ToIso(substr($guia['idGuia'] ?? '', 0, 8)), 1, 0);
            $pdf->Cell($colContW, 6, $this->utf8ToIso(substr($guia['contenedor'] ?? '', 0, 12)), 1, 0);
            $pdf->Cell($colEstW, 6, $this->utf8ToIso(substr($guia['estado'] ?? '', 0, 12)), 1, 0);
            $pdf->Cell($colModoW, 6, $this->utf8ToIso(substr($guia['modo'] ?? '', 0, 8)), 1, 0);
            $pdf->Cell($colConsW, 6, $this->utf8ToIso(substr($guia['consignatario'] ?? '-', 0, 20)), 1, 0);
            $pdf->Cell($colCantW, 6, $this->utf8ToIso($guia['cantidad'] ?? ''), 1, 0, 'C');
            $pdf->Cell($colPesoW, 6, number_format($guiasPeso = ($guia['peso'] ?? 0), 2), 1, 0, 'R');
            $currentY += $rowH;
        }

        $pdf->Output('I', 'Reporte_Mensual_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Generar reporte diario en PDF
     * Utilizado por: Guias::reporteDiario()
     */
    public function generarReporteDiario($guias)
    {
        require_once $this->fpdfPath;

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo (mismo tamaño que otros PDFs)
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        }

        // Título y fecha
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 12, $this->utf8ToIso('Reporte Diario de Guías'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, $this->utf8ToIso('Fecha') . ': ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Encabezados (centrados con márgenes)
        // Rebalanceo de anchos sin Observaciones; total 166mm para buen margen
        $colIdW = 18; $colContW = 28; $colEstW = 22; $colModoW = 24; $colConsW = 40; $colCantW = 16; $colPesoW = 18;
        $totalTableWidth = $colIdW + $colContW + $colEstW + $colModoW + $colConsW + $colCantW + $colPesoW; // 166
        $pageWidth = $pdf->GetPageWidth() - 20; // márgenes laterales de 10mm
        $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        // Colocar el encabezado en una Y definida bajo el logo/título
        $headerY = max(40, $pdf->GetY());
        $pdf->SetXY($tableOffsetX, $headerY);
        $pdf->Cell($colIdW, 7, 'ID', 1, 0, 'C', true);
        $pdf->Cell($colContW, 7, 'Contenedor', 1, 0, 'C', true);
        $pdf->Cell($colEstW, 7, 'Estado', 1, 0, 'C', true);
        $pdf->Cell($colModoW, 7, 'Modo', 1, 0, 'C', true);
        $pdf->Cell($colConsW, 7, 'Consignatario', 1, 0, 'C', true);
        $pdf->Cell($colCantW, 7, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell($colPesoW, 7, 'Peso (kg)', 1, 1, 'C', true);

        // Datos
        $pdf->SetFont('Arial', '', 8);
        $currentY = $pdf->GetY();
        $rowH = 6;
        foreach ($guias as $guia) {
            // Posicionar cada fila manualmente para evitar solapes
            $pdf->SetXY($tableOffsetX, $currentY);
            $pdf->Cell($colIdW, $rowH, $this->utf8ToIso(substr($guia['idGuia'] ?? '', 0, 8)), 1, 0);
            $pdf->Cell($colContW, $rowH, $this->utf8ToIso(substr($guia['contenedor'] ?? '', 0, 12)), 1, 0);
            // ampliar estado para evitar cortes (ej. 'entregado')
            $pdf->Cell($colEstW, $rowH, $this->utf8ToIso(substr($guia['estado'] ?? '', 0, 12)), 1, 0);
            $pdf->Cell($colModoW, $rowH, $this->utf8ToIso(substr($guia['modo'] ?? '', 0, 8)), 1, 0);
            $pdf->Cell($colConsW, $rowH, $this->utf8ToIso(substr($guia['consignatario'] ?? '', 0, 20)), 1, 0);
            $pdf->Cell($colCantW, $rowH, $this->utf8ToIso(($guia['cantidad'] ?? '')), 1, 0, 'C');
            $pdf->Cell($colPesoW, $rowH, number_format($guia['peso'] ?? 0, 2), 1, 0, 'R');
            // Avanzar a la siguiente fila
            $currentY += $rowH;
        }

        $pdf->Output('I', 'Reporte_Diario_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Generar reporte de incidencias en PDF
     * Utilizado por: Guias::reporteIncidencias()
     */
    public function generarReporteIncidencias($incidencias)
    {
        require_once $this->fpdfPath;

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo (mismo estilo que otros reportes)
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        }

        // Título y fecha
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 12, $this->utf8ToIso('Reporte de Incidencias'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, $this->utf8ToIso('Generado') . ': ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);

        // Resumen
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Total de incidencias: ' . count($incidencias), 0, 1);
        $pdf->Ln(3);

        // Tabla de incidencias (centrada)
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        $colIdW = 30; $colTipoW = 40; $colDescW = 70; $colFechaW = 30; $colEstadoW = 30;
        $totalTableWidth = $colIdW + $colTipoW + $colDescW + $colFechaW + $colEstadoW; // 200mm
        $pageWidth = $pdf->GetPageWidth() - 20; // márgenes laterales de 10mm
        $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;
        $headerY = max(40, $pdf->GetY());
        $pdf->SetXY($tableOffsetX, $headerY);
        $pdf->Cell($colIdW, 7, 'ID Guia', 1, 0, 'C', true);
        $pdf->Cell($colTipoW, 7, 'Tipo', 1, 0, 'C', true);
        $pdf->Cell($colDescW, 7, 'Descripcion', 1, 0, 'C', true);
        $pdf->Cell($colFechaW, 7, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell($colEstadoW, 7, 'Estado', 1, 1, 'C', true);

        // Datos colocados con SetXY y avance de filas

        // Datos colocados con SetXY y avance de filas
        $pdf->SetFont('Arial', '', 8);
        $currentY = $pdf->GetY();
        $rowH = 6;
        foreach ($incidencias as $inc) {
            $pdf->SetXY($tableOffsetX, $currentY);
            $pdf->Cell($colIdW, $rowH, $this->utf8ToIso(substr($inc['idGuia'] ?? '', 0, 12)), 1, 0);
            $pdf->Cell($colTipoW, $rowH, $this->utf8ToIso(substr($inc['tipo'] ?? '', 0, 25)), 1, 0);
            $pdf->Cell($colDescW, $rowH, $this->utf8ToIso(substr($inc['descripcion'] ?? '', 0, 40)), 1, 0);
            $pdf->Cell($colFechaW, $rowH, $this->utf8ToIso($inc['fecha'] ?? ''), 1, 0);
            $pdf->Cell($colEstadoW, $rowH, $this->utf8ToIso(substr($inc['estado'] ?? '', 0, 12)), 1, 0);
            $currentY += $rowH;
        }

        $pdf->Output('I', 'Reporte_Incidencias_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Generar Acta de Recepción de Guía
     * Utilizado por: Guias::pdf() con acción 'actaRecepcion'
     */
    public function generarActaRecepcion($idGuia, $data)
    {
        require_once $this->fpdfPath;

        // Extraer datos de array
        $identificadores = $data['identificadores'] ?? [];
        $logistica = $data['logistica'] ?? [];
        $fechas = $data['fechas'] ?? [];
        $certificado = $data['certificado'] ?? [];
        $bultos = $data['bultos'] ?? [];
        $bultosReal = $data['bultosReal'] ?? [];
        $incidencias = $data['incidencias'] ?? [];
        $tieneIncidencias = $data['tieneIncidencias'] ?? false;
        $nombreTrabajador = $data['nombreTrabajador'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        } else {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 10, 'Logo SIDI-MX', 1, 0, 'C');
        }

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('Acta de recepción de guía'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión: ') . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Secciones
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, '1. Datos generales del paquete', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, $this->utf8ToIso('Guía') . ': [' . ($idGuia ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Contenedor') . ': [' . ($identificadores['contenedor'] ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Máster') . ': [' . ($identificadores['master'] ?? '') . ']', 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, 'Aduana: [' . ($logistica['aduana'] ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, 'Modo: [' . ($logistica['modo'] ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, 'Incoterm: [' . ($logistica['incoterm'] ?? '') . ']', 0, 1);

        $pdf->Ln(4);

        // Información documental
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Información documental'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, 'Fecha ETD: [' . $this->utf8ToIso($fechas['etd'] ?? '') . ']', 0, 0);
        $pdf->Cell(45, 5, 'Fecha ETA: [' . $this->utf8ToIso($fechas['eta'] ?? '') . ']', 0, 0);
        $pdf->Cell(50, 5, $this->utf8ToIso('Fecha ingreso') . ': [' . $this->utf8ToIso($fechas['fechaEstimadaIngreso'] ?? '') . ']', 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(0, 5, $this->utf8ToIso('Certificado') . ': [' . $this->utf8ToIso($certificado['tratado'] ?? '') . ' - ' . $this->utf8ToIso('Vigencia') . ': ' . $this->utf8ToIso($certificado['vigencia'] ?? '') . ']', 0, 1);

        $pdf->Ln(4);

        // Verificación física
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('3. Verificación física'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(95, 5, $this->utf8ToIso('Cantidad declarada') . ': [' . $this->utf8ToIso($bultos['cantidad'] ?? '') . ']', 0, 0);
        $cantidadVerificada = $bultosReal['cantidad'] ?? '___';
        $pdf->Cell(95, 5, $this->utf8ToIso('Cantidad Verificada') . ': [' . $this->utf8ToIso($cantidadVerificada) . ']', 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(95, 5, $this->utf8ToIso('Peso neto declarado') . ': [' . $this->utf8ToIso($bultos['pesoNeto'] ?? '') . '] kg', 0, 0);
        $pesoVerificado = $bultosReal['pesoNeto'] ?? '___';
        $pdf->Cell(95, 5, $this->utf8ToIso('Peso neto verificado') . ': [' . $this->utf8ToIso($pesoVerificado) . '] kg', 0, 1);

        if ($tieneIncidencias && !empty($incidencias)) {
            $pdf->Ln(4);
            $pdf->SetX($bodyMarginLeft);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 6, '4. Incidencias', 0, 1);
            $pdf->Ln(2);

            $colDescWidth = 120;
            $colTipoWidth = 50;
            $totalTableWidth = $colDescWidth + $colTipoWidth;
            $pageWidth = $pdf->GetPageWidth() - 20;
            $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;

            $pdf->SetX($tableOffsetX);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell($colDescWidth, 6, $this->utf8ToIso('Descripción'), 1, 0, 'C');
            $pdf->Cell($colTipoWidth, 6, 'Tipo', 1, 1, 'C');

            $pdf->SetFont('Arial', '', 8);
            foreach ($incidencias as $inc) {
                $pdf->SetX($tableOffsetX);
                $pdf->Cell($colDescWidth, 6, $this->utf8ToIso(substr($inc['descripcion'] ?? '', 0, 60)), 1, 0, 'L');
                $pdf->Cell($colTipoWidth, 6, $this->utf8ToIso($inc['tipo'] ?? 'N/A'), 1, 1, 'C');
            }
        }

        // Firma
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Recibió') . ':', 0, 1, 'C');
        $pdf->Ln(15);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, $this->utf8ToIso('Nombre y firma de'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'trabajador', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreTrabajador), 0, 1, 'C');

        $pdf->Output('I', $this->utf8ToIso('Acta_Recepcion_Guia_') . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Acta de Incidencia
     * Utilizado por: Guias::pdf() con acción 'actaIncidencia'
     */
    public function generarActaIncidencia($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $guia = $data['guia'] ?? [];
        $incidencias = $data['incidencias'] ?? [];
        $nombreOperador = $data['nombreOperador'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        } else {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 10, 'Logo SIDI-MX', 1, 0, 'C');
        }

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, 'Acta de Incidencia', 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Guía
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Guía') . ': [' . $idGuia . ']', 0, 1);

        $pdf->Ln(8);

        // Tabla de incidencias
        $colTipoWidth = 50;
        $colDescWidth = 120;
        $totalTableWidth = $colTipoWidth + $colDescWidth;
        $pageWidth = $pdf->GetPageWidth() - 20;
        $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;

        $pdf->SetX($tableOffsetX);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell($colTipoWidth, 8, 'Tipo', 1, 0, 'C', true);
        $pdf->Cell($colDescWidth, 8, $this->utf8ToIso('Descripción'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        
        if (!empty($incidencias)) {
            foreach ($incidencias as $inc) {
                $pdf->SetX($tableOffsetX);
                $pdf->Cell($colTipoWidth, 20, $this->utf8ToIso($inc['tipo'] ?? 'N/A'), 1, 0, 'C');
                $pdf->Cell($colDescWidth, 20, $this->utf8ToIso(substr($inc['descripcion'] ?? '', 0, 80)), 1, 1, 'L');
            }
        } else {
            for ($i = 0; $i < 2; $i++) {
                $pdf->SetX($tableOffsetX);
                $pdf->Cell($colTipoWidth, 20, '', 1, 0, 'C');
                $pdf->Cell($colDescWidth, 20, '', 1, 1, 'L');
            }
        }

        // Firma
        $pdf->Ln(50);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Nombre y firma de'), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->utf8ToIso('operador'), 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreOperador), 0, 1, 'C');

        $pdf->Output('I', 'Acta_Incidencia_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Pedimento
     * Utilizado por: Guias::pdf() con acción 'pedimento'
     */
    public function generarPedimento($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $pedimento = $data['pedimento'] ?? [];
        $logistica = $data['logistica'] ?? [];
        $comprobante = $data['comprobante'] ?? [];
        $nombreOperador = $data['nombreOperador'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        } else {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 10, 'Logo SIDI-MX', 1, 0, 'C');
        }

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('Pedimento'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // 1. Datos del pedimento
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('1. Datos del pedimento'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Guía') . ': [' . ($idGuia ?? '') . ']', 0, 0);
        $pdf->Cell(50, 5, $this->utf8ToIso('Régimen') . ': [' . $this->utf8ToIso($pedimento['regimen'] ?? '') . ']', 0, 0);
        $pdf->Cell(50, 5, $this->utf8ToIso('Aduana') . ': [' . $this->utf8ToIso($logistica['aduana'] ?? '') . ']', 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Patente') . ': [' . $this->utf8ToIso($pedimento['patente'] ?? '') . ']', 0, 0);
        $pdf->Cell(50, 5, $this->utf8ToIso('Número') . ': [' . $this->utf8ToIso($pedimento['numero'] ?? '') . ']', 0, 1);

        $pdf->Ln(4);

        // 2. Estado de pago
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Estado de pago'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pagadoCheck = (!empty($pedimento['idComprobante'])) ? '[X]' : '[ ]';
        $pdf->Cell(0, 5, $this->utf8ToIso('Pagado') . ': ' . $pagadoCheck, 0, 1);

        if (!empty($comprobante)) {
            $pdf->SetX($bodyMarginLeft);
            $pdf->Cell(0, 5, $this->utf8ToIso('Fecha de pago') . ': [' . $this->utf8ToIso($comprobante['fecha'] ?? 'N/A') . ']', 0, 1);

            $pdf->SetX($bodyMarginLeft);
            $pdf->SetFont('Arial', 'B', 9);
            $total = isset($comprobante['total']) ? '$' . number_format($comprobante['total'], 2) : '$0.00';
            $moneda = $this->utf8ToIso($comprobante['moneda'] ?? '');
            $pdf->Cell(0, 5, $this->utf8ToIso('Total') . ': ' . $total . ' ' . $moneda, 0, 1);
        }

        // Firma
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Nombre y firma de'), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->utf8ToIso('operador'), 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreOperador), 0, 1, 'C');

        $pdf->Output('I', 'Pedimento_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Comprobante de Pago
     * Utilizado por: Guias::pdf() con acción 'comprobantePago'
     */
    public function generarComprobantePago($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $comprobante = $data['comprobante'] ?? [];

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        $pdf->Ln(5);

        // Cuadro contenedor
        $cuadroX = 15;
        $cuadroY = 15;
        $cuadroWidth = 180;
        $cuadroHeight = 100;
        $pdf->Rect($cuadroX, $cuadroY, $cuadroWidth, $cuadroHeight);

        // Margen interno
        $innerMargin = 5;
        $startX = $cuadroX + $innerMargin;
        $currentY = $cuadroY + $innerMargin;

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, $startX, $currentY, 25);
        } else {
            $pdf->SetXY($startX, $currentY);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(25, 15, 'Logo', 1, 0, 'C');
        }

        // Título
        $pdf->SetXY($cuadroX, $currentY + 5);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell($cuadroWidth, 7, $this->utf8ToIso('Comprobante de pago'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha') . ': ' . $this->utf8ToIso($comprobante['fecha'] ?? date('Y-m-d'));
        $pdf->SetXY($cuadroX + $cuadroWidth - 50, $currentY + 5);
        $pdf->Cell(45, 5, $fechaText, 0, 1, 'R');

        $currentY += 25;

        // ID, Número de cuenta, Emisor
        $pdf->SetXY($startX, $currentY);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(15, 6, 'ID: ', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(45, 6, $this->utf8ToIso($idGuia), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(35, 6, $this->utf8ToIso('Número de cuenta') . ': ', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, $this->utf8ToIso($comprobante['numero'] ?? ''), 0, 1, 'L');

        $currentY += 8;
        $pdf->SetXY($startX, $currentY);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(15, 6, $this->utf8ToIso('Emisor') . ': ', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, $this->utf8ToIso($comprobante['emisor'] ?? ''), 0, 1, 'L');

        $currentY += 20;

        // Total
        $pdf->SetXY($startX, $currentY);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(12, 6, $this->utf8ToIso('Total') . ': ', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $total = isset($comprobante['total']) ? '$' . number_format($comprobante['total'], 2) : '$0.00';
        $moneda = $this->utf8ToIso($comprobante['moneda'] ?? '');
        $pdf->Cell(50, 6, $total . ' ' . $moneda, 0, 1, 'L');

        // Sello
        $selloX = $cuadroX + $cuadroWidth - 65;
        $selloY = $currentY - 10;
        $pdf->SetXY($selloX, $selloY);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 35, 'Sello', 1, 1, 'C');

        $pdf->Output('I', 'Comprobante_Pago_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Comprobante de Liberación
     * Utilizado por: Guias::pdf() con acción 'comprobanteLiberacion'
     */
    public function generarComprobanteLiberacion($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $identificadores = $data['identificadores'] ?? [];
        $logistica = $data['logistica'] ?? [];
        $partes = $data['partes'] ?? [];
        $pedimento = $data['pedimento'] ?? [];
        $comprobante = $data['comprobante'] ?? [];
        $incidencias = $data['incidencias'] ?? [];
        $tieneIncidencias = $data['tieneIncidencias'] ?? false;
        $liberacion = $data['liberacion'] ?? [];
        $nombreSupervisor = $data['nombreSupervisor'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        }

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('Comprobante de Liberación'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Datos de la guía
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('1. Datos de la guía / embarque'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, $this->utf8ToIso('Guía') . ': [' . ($idGuia ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Máster') . ': [' . $this->utf8ToIso($identificadores['master'] ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Contenedor') . ': [' . $this->utf8ToIso($identificadores['contenedor'] ?? '') . ']', 0, 1);

        // Datos aduanales
        $pdf->Ln(4);
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Datos aduanales clave'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, $this->utf8ToIso('Aduana') . ': [' . $this->utf8ToIso($logistica['aduana'] ?? '') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Régimen') . ': [' . $this->utf8ToIso($pedimento['regimen'] ?? '') . ']', 0, 1);

        $total = isset($comprobante['total']) ? '$' . number_format($comprobante['total'], 2) : '$0.00';
        $moneda = $this->utf8ToIso($comprobante['moneda'] ?? '');
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(0, 5, $this->utf8ToIso('Total contribuciones') . ': [' . $total . ' ' . $moneda . ']', 0, 1);

        // Observaciones
        $pdf->Ln(4);
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso('Observaciones') . ':', 0, 1);
        $pdf->SetX($bodyMarginLeft);
        $observaciones = $this->utf8ToIso($liberacion['observaciones'] ?? 'Ninguna');
        $pdf->MultiCell(0, 5, $observaciones);

        // Firma
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Supervisor que autorizó'), 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreSupervisor), 0, 1, 'C');

        $pdf->Output('I', 'Comprobante_Liberacion_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Comprobante de Retiro
     * Utilizado por: Guias::pdf() con acción 'comprobanteRetiro'
     */
    public function generarComprobanteRetiro($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $identificadores = $data['identificadores'] ?? [];
        $retiro = $data['retiro'] ?? [];
        $nombreOperadorRetiro = $data['nombreOperadorRetiro'] ?? 'N/A';

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 30);
        }

        $pdf->Ln(15);

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('Comprobante de Retiro'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Información de la guía
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('1. Información de la guía'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, $this->utf8ToIso('Guía') . ': [' . $this->utf8ToIso($identificadores['guia'] ?? 'N/A') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Máster') . ': [' . $this->utf8ToIso($identificadores['master'] ?? 'N/A') . ']', 0, 1);

        // Datos del retiro
        $pdf->Ln(4);
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Datos del retiro'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Unidad') . ':', 0, 0);
        $pdf->Cell(0, 5, $this->utf8ToIso($retiro['unidad'] ?? 'N/A'), 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Placas') . ':', 0, 0);
        $pdf->Cell(0, 5, $this->utf8ToIso($retiro['placas'] ?? 'N/A'), 0, 1);

        $fechaProgramada = !empty($retiro['fechaProgramada']) ? date('d/m/Y', strtotime($retiro['fechaProgramada'])) : 'N/A';
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Fecha programada') . ':', 0, 0);
        $pdf->Cell(0, 5, $fechaProgramada, 0, 1);

        // Firma
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Trabajador'), 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreOperadorRetiro), 0, 1, 'C');

        $pdf->Output('I', 'Comprobante_Retiro_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar POD (Proof of Delivery)
     * Utilizado por: Guias::pdf() con acción 'pod'
     */
    public function generarPOD($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $identificadores = $data['identificadores'] ?? [];
        $logistica = $data['logistica'] ?? [];
        $pod = $data['pod'] ?? [];

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 30);
        }

        $pdf->Ln(15);

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('POD - Proof of Delivery'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Información de la guía
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('1. Información de la guía'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(60, 5, $this->utf8ToIso('Guía') . ': [' . $this->utf8ToIso($identificadores['guia'] ?? 'N/A') . ']', 0, 0);
        $pdf->Cell(60, 5, $this->utf8ToIso('Máster') . ': [' . $this->utf8ToIso($identificadores['master'] ?? 'N/A') . ']', 0, 1);

        // Datos de entrega
        $pdf->Ln(4);
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Datos de entrega'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Receptor') . ':', 0, 0);
        $pdf->Cell(0, 5, $this->utf8ToIso($pod['receptor'] ?? 'N/A'), 0, 1);

        $horaEntrega = !empty($pod['horaEntrega']) ? date('d/m/Y H:i', strtotime($pod['horaEntrega'])) : 'N/A';
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Hora de entrega') . ':', 0, 0);
        $pdf->Cell(0, 5, $horaEntrega, 0, 1);

        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(40, 5, $this->utf8ToIso('Condición') . ':', 0, 0);
        $pdf->Cell(0, 5, $this->utf8ToIso($pod['condicion'] ?? 'N/A'), 0, 1);

        $pdf->Output('I', 'POD_Guia_' . $idGuia . '.pdf');
        exit;
    }

    /**
     * Generar Preliquidación
     * Utilizado por: Guias::pdf() con acción 'preliquidacion'
     */
    public function generarPreliquidacion($idGuia, $data)
    {
        require_once $this->fpdfPath;

        $logistica = $data['logistica'] ?? [];
        $bultos = $data['bultos'] ?? [];
        $bultosReal = $data['bultosReal'] ?? [];
        $tarifas = $data['tarifas'] ?? [];
        $nombreEmpleado = $data['nombreEmpleado'] ?? 'N/A';

        // Calculos
        $peso = (float)($bultos['pesoNeto'] ?? 0);
        $volumen = (float)($bultos['volumen'] ?? 0);
        $pesoReal = (float)($bultosReal['pesoNeto'] ?? $peso);
        $volumenReal = (float)($bultosReal['volumen'] ?? $volumen);
        $cantidadReal = (int)($bultosReal['cantidad'] ?? 0);

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

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // Logo
        if (file_exists($this->logoPath)) {
            $pdf->Image($this->logoPath, 10, 10, 25);
        }

        // Título
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 7, $this->utf8ToIso('Preliquidación'), 0, 1, 'C');

        // Fecha
        $pdf->SetFont('Arial', '', 9);
        $fechaText = $this->utf8ToIso('Fecha de emisión') . ': ' . date('d/m/Y');
        $pdf->SetX($pdf->GetPageWidth() - 10 - $pdf->GetStringWidth($fechaText));
        $pdf->Cell(0, 5, $fechaText, 0, 1, 'R');

        $pdf->Ln(8);
        $bodyMarginLeft = 22;

        // Datos generales
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('1. Datos generales del paquete'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($bodyMarginLeft);
        $pdf->Cell(50, 5, $this->utf8ToIso('Guía') . ': [' . $this->utf8ToIso($idGuia ?? '') . ']', 0, 0);
        $pdf->Cell(70, 5, $this->utf8ToIso('Aduana') . ': [' . $this->utf8ToIso($logistica['aduana'] ?? '') . ']', 0, 1);

        $pdf->Ln(6);

        // Tabla de cargos
        $pdf->SetX($bodyMarginLeft);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('2. Cargos'), 0, 1);
        $pdf->Ln(2);

        $colCargoWidth = 50;
        $colCantidadWidth = 35;
        $colValorWidth = 35;
        $colMontoWidth = 35;
        $totalTableWidth = $colCargoWidth + $colCantidadWidth + $colValorWidth + $colMontoWidth;
        $pageWidth = $pdf->GetPageWidth() - 20;
        $tableOffsetX = 10 + ($pageWidth - $totalTableWidth) / 2;

        $pdf->SetX($tableOffsetX);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell($colCargoWidth, 7, $this->utf8ToIso('Cargo'), 1, 0, 'C', true);
        $pdf->Cell($colCantidadWidth, 7, $this->utf8ToIso('Cantidad'), 1, 0, 'C', true);
        $pdf->Cell($colValorWidth, 7, $this->utf8ToIso('Valor'), 1, 0, 'C', true);
        $pdf->Cell($colMontoWidth, 7, $this->utf8ToIso('Monto (MXN)'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 8);

        $pdf->SetX($tableOffsetX);
        $pdf->Cell($colCargoWidth, 6, $this->utf8ToIso('Peso'), 1, 0, 'L');
        $pdf->Cell($colCantidadWidth, 6, number_format($peso, 2) . ' kg', 1, 0, 'C');
        $pdf->Cell($colValorWidth, 6, '$' . number_format($tarifaKg, 2), 1, 0, 'R');
        $pdf->Cell($colMontoWidth, 6, '$' . number_format($montoPeso, 2), 1, 1, 'R');

        $pdf->SetX($tableOffsetX);
        $pdf->Cell($colCargoWidth, 6, $this->utf8ToIso('Volumen'), 1, 0, 'L');
        $pdf->Cell($colCantidadWidth, 6, number_format($volumen, 2) . ' cm3', 1, 0, 'C');
        $pdf->Cell($colValorWidth, 6, '$' . number_format($tarifaVolumen, 2), 1, 0, 'R');
        $pdf->Cell($colMontoWidth, 6, '$' . number_format($montoVolumen, 2), 1, 1, 'R');

        // Filas de excedentes (solo si hay excedentes)
        if ($pesoExcedente > 0) {
            $pdf->SetX($tableOffsetX);
            $pdf->Cell($colCargoWidth, 6, $this->utf8ToIso('Peso excedente'), 1, 0, 'L');
            $pdf->Cell($colCantidadWidth, 6, number_format($pesoExcedente, 2) . ' kg', 1, 0, 'C');
            $pdf->Cell($colValorWidth, 6, '$' . number_format($tarifaKgExtra, 2), 1, 0, 'R');
            $pdf->Cell($colMontoWidth, 6, '$' . number_format($montoPesoExcedente, 2), 1, 1, 'R');
        }

        if ($volumenExcedente > 0) {
            $pdf->SetX($tableOffsetX);
            $pdf->Cell($colCargoWidth, 6, $this->utf8ToIso('Volumen excedente'), 1, 0, 'L');
            $pdf->Cell($colCantidadWidth, 6, number_format($volumenExcedente, 2) . ' cm3', 1, 0, 'C');
            $pdf->Cell($colValorWidth, 6, '$' . number_format($tarifaVolumenExtra, 2), 1, 0, 'R');
            $pdf->Cell($colMontoWidth, 6, '$' . number_format($montoVolumenExcedente, 2), 1, 1, 'R');
        }

        // Total
        $pdf->Ln(2);
        $pdf->SetX($tableOffsetX + $colCargoWidth + $colCantidadWidth + $colValorWidth);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($colMontoWidth, 7, $this->utf8ToIso('Total') . ': $' . number_format($subtotal, 2), 0, 1, 'R');

        // Firma
        $pdf->Ln(15);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $this->utf8ToIso('Nombre y firma de'), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->utf8ToIso('empleado'), 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->utf8ToIso($nombreEmpleado), 0, 1, 'C');

        $pdf->Output('I', 'Preliquidacion_Guia_' . $idGuia . '.pdf');
        exit;
    }
}
