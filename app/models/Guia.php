<?php
/**
 * modelo Guia
 */

class Guia{
    private $db;
    private $table='guia';

    public function __construct(){
        // modificar la clase Base
        $this->db= new Base($this->table);
    }

    public function all(){
        return $this->db->get();
    }

    /**
     * Buscar guia por idIdentificadores (FK)
     * @param int $idIdentificadores
     * @return array|null
     */
    public function findByIdentificadores($idIdentificadores)
    {
        return $this->db->where('idIdentificadores', $idIdentificadores)->first();
    }

    public function findByLogistica($idLogistica)
    {
        return $this->db->where('idLogistica', $idLogistica)->first();
    }

    public function findByPartes($idPartes)
    {
        return $this->db->where('idPartes', $idPartes)->first();
    }

    public function findByFechas($idFechas)
    {
        return $this->db->where('idFechas', $idFechas)->first();
    }

    public function findByBultos($idBultos)
    {
        return $this->db->where('idBultos', $idBultos)->first();
    }

    public function findByCertificadoOrigen($idCertificado)
    {
        return $this->db->where('idCertificadoOrigen', $idCertificado)->first();
    }

    public function find($id)
    {
        return $this->db->find($id);
    }

    /**
     * Verificar si existe una guía externa (excluyendo una guía específica en edición)
     * @param string $guiaExterna Código de la guía externa
     * @param int $excludeId ID de la guía a excluir (0 si es nueva)
     * @return bool
     */
    public function existeGuiaExterna($guiaExterna, $excludeId = 0)
    {
        // Primero buscar en la tabla identificadores
        $dbIdentificadores = new Base('identificadores');
        
        if ($excludeId > 0) {
            // En edición: buscar guías con ese código excluyendo la actual
            $guiaActual = $this->db->find($excludeId);
            if ($guiaActual && isset($guiaActual['idIdentificadores'])) {
                $result = $dbIdentificadores
                    ->where('guia', $guiaExterna)
                    ->where('id', '!=', $guiaActual['idIdentificadores'])
                    ->first();
            } else {
                $result = $dbIdentificadores->where('guia', $guiaExterna)->first();
            }
        } else {
            // Creación nueva: simplemente buscar si existe
            $result = $dbIdentificadores->where('guia', $guiaExterna)->first();
        }
        
        return !empty($result);
    }

    /**
     * Actualizar guía usando el procedimiento almacenado actualizarGuia
     * Recibe un array con las claves que coinciden con los parámetros del SP
     * Devuelve el número de filas afectadas o null
     */
    public function actualizarConSP(array $params)
    {
        $defaults = [
            'pIdGuia' => null,
            'pGuia' => null,
            'pMaster' => null,
            'pContenedor' => null,
            'pOrigen' => null,
            'pDestino' => null,
            'pAduana' => null,
            'pSeccion' => null,
            'pModo' => null,
            'pIncoterm' => null,
            'pConsignatario' => null,
            'pContacto' => null,
            'pImportadorRfc' => null,
            'pCertTratado' => null,
            'pCertVigencia' => null,
            'pEtd' => null,
            'pEta' => null,
            'pFechaEstIngreso' => null,
            'pCantidad' => null,
            'pPesoNeto' => null,
            'pVolumen' => null,
            'pAncho' => null,
            'pAlto' => null,
            'pLargo' => null,
            'pPermisosJson' => null,
            'pEstado' => null,
            'pIdUsuario' => null
        ];

        $binds = array_merge($defaults, $params);

        $callSql = "CALL actualizarGuia(
            :pIdGuia,
            :pGuia,
            :pMaster,
            :pContenedor,
            :pOrigen,
            :pDestino,
            :pAduana,
            :pSeccion,
            :pModo,
            :pIncoterm,
            :pConsignatario,
            :pContacto,
            :pImportadorRfc,
            :pCertTratado,
            :pCertVigencia,
            :pEtd,
            :pEta,
            :pFechaEstIngreso,
            :pCantidad,
            :pPesoNeto,
            :pVolumen,
            :pAncho,
            :pAlto,
            :pLargo,
            :pPermisosJson,
            :pEstado,
            :pIdUsuario,
            @pAfectados
        )";

        $this->db->raw($callSql, $binds, 'none');

        $row = $this->db->raw("SELECT @pAfectados AS afectados", [], 'one');
        return isset($row['afectados']) ? (int)$row['afectados'] : null;
    }


    /**
     * Actualizar estado de guía usando procedimiento almacenado
     * Esto permite que los triggers registren el usuario en bitácora
     * @param int $id ID de la guía
     * @param string $estado Nuevo estado
     * @param int $idUsuario ID del usuario que realiza el cambio
     * @return int Número de filas afectadas
     */
    public function updateEstadoConSP($id, $estado, $idUsuario)
    {
        $callSql = "CALL actualizarEstadoGuia(
            :pIdGuia,
            :pEstado,
            :pIdUsuario,
            @pAfectados
        )";

        $this->db->raw($callSql, [
            ':pIdGuia' => (int)$id,
            ':pEstado' => $estado,
            ':pIdUsuario' => $idUsuario !== null ? (int)$idUsuario : null
        ], 'none');

        $row = $this->db->raw("SELECT @pAfectados AS afectados", [], 'one');
        return isset($row['afectados']) ? (int)$row['afectados'] : 0;
    }

    /**
     * Eliminar guía usando procedimiento almacenado eliminarGuia
     * Recibe id de guía e idUsuario para bitácora
     * Devuelve número de filas afectadas o null
     */
    public function eliminarConSP($idGuia, $idUsuario)
    {
        $callSql = "CALL eliminarGuia(
            :pIdGuia,
            :pIdUsuario,
            @pAfectados
        )";

        $this->db->raw($callSql, [
            ':pIdGuia' => (int)$idGuia,
            ':pIdUsuario' => $idUsuario !== null ? (int)$idUsuario : null
        ], 'none');

        $row = $this->db->raw("SELECT @pAfectados AS afectados", [], 'one');
        return isset($row['afectados']) ? (int)$row['afectados'] : null;
    }

    /**
     * Crear guía usando el procedimiento almacenado crearGuia
     * Recibe un array con las claves que coinciden con los parámetros del SP (sin el @out)
     * Devuelve el id de la guía creada o null
     */
    public function crearConSP(array $params)
    {
        // Preparar parámetros con valores por defecto
        $defaults = [
            'pGuia' => null,
            'pMaster' => null,
            'pContenedor' => null,
            'pOrigen' => null,
            'pDestino' => null,
            'pAduana' => null,
            'pSeccion' => null,
            'pModo' => null,
            'pIncoterm' => null,
            'pConsignatario' => null,
            'pContacto' => null,
            'pImportadorRfc' => null,
            'pCertTratado' => null,
            'pCertVigencia' => null,
            'pEtd' => null,
            'pEta' => null,
            'pFechaEstIngreso' => null,
            'pCantidad' => null,
            'pPesoNeto' => null,
            'pVolumen' => null,
            'pAncho' => null,
            'pAlto' => null,
            'pLargo' => null,
            'pPermisosJson' => null,
            'pEstado' => null,
            'pIdUsuario' => null
        ];

        $binds = array_merge($defaults, $params);

        // Llamar al SP: usar variable de salida @pIdGuia
        $callSql = "CALL crearGuia(
            :pGuia,
            :pMaster,
            :pContenedor,
            :pOrigen,
            :pDestino,
            :pAduana,
            :pSeccion,
            :pModo,
            :pIncoterm,
            :pConsignatario,
            :pContacto,
            :pImportadorRfc,
            :pCertTratado,
            :pCertVigencia,
            :pEtd,
            :pEta,
            :pFechaEstIngreso,
            :pCantidad,
            :pPesoNeto,
            :pVolumen,
            :pAncho,
            :pAlto,
            :pLargo,
            :pPermisosJson,
            :pEstado,
            :pIdUsuario,
            @pIdGuia
        )";

        // Ejecutar CALL (sin fetch)
        $this->db->raw($callSql, $binds, 'none');

        // Obtener variable de salida
        $row = $this->db->raw("SELECT @pIdGuia AS id", [], 'one');
        return $row['id'] ?? null;
    }

    /**
     * Obtener guías del último mes (según fechaEstimadaIngreso) con todos los datos para reporte
     * @param string $fechaHasta Fecha hasta (inclusive), formato Y-m-d
     * @return array
     */
    public function getGuiasUltimoMes($fechaHasta)
    {
        $fechaDesde = date('Y-m-d', strtotime($fechaHasta . ' -1 month'));
        
        $sql = "SELECT 
                g.id as idGuia,
                i.contenedor,
                i.master,
                l.aduana,
                l.modo,
                l.incoterm,
                p.consignatario,
                COALESCE(u.nombre, '-') as operador,
                g.estado,
                b.cantidad,
                b.pesoNeto AS peso,
                DATE_FORMAT(f.fechaEstimadaIngreso, '%Y-%m-%d') as fechaEstimadaIngreso
            FROM guia g
            LEFT JOIN identificadores i ON g.idIdentificadores = i.id
            LEFT JOIN logistica l ON g.idLogistica = l.id
            LEFT JOIN fechas f ON g.idFechas = f.id
            LEFT JOIN partes p ON g.idPartes = p.id
            LEFT JOIN bultos b ON g.idBultos = b.id
            LEFT JOIN (
                SELECT 
                    b1.registroId, 
                    b1.idUsuarios
                FROM bitacora b1
                INNER JOIN (
                    SELECT registroId, MAX(id) as maxId
                    FROM bitacora
                    WHERE tablaAfectada = 'guia'
                    AND accion IN ('INSERT', 'UPDATE')
                    GROUP BY registroId
                ) b2 ON b1.registroId = b2.registroId AND b1.id = b2.maxId
                WHERE b1.tablaAfectada = 'guia'
            ) bit ON bit.registroId = g.id
            LEFT JOIN usuarios u ON u.id = bit.idUsuarios
            WHERE f.fechaEstimadaIngreso BETWEEN :fechaDesde AND :fechaHasta
            ORDER BY CAST(g.id AS UNSIGNED) ASC";
        
        return $this->db->raw($sql, [
            ':fechaDesde' => $fechaDesde,
            ':fechaHasta' => $fechaHasta
        ], 'all');
    }

    /**
     * Obtener guías de un día específico (según fechaEstimadaIngreso)
     * @param string $fecha Fecha en formato Y-m-d
     * @return array
     */
    public function getGuiasPorDia($fecha)
    {
        $sql = "SELECT 
                g.id as idGuia,
                p.consignatario,
                i.contenedor,
                i.master,
                l.aduana,
                l.modo,
                l.incoterm,
                COALESCE(u.nombre, '-') as operador,
                g.estado,
                b.cantidad,
                b.pesoNeto as peso,
                DATE_FORMAT(f.fechaEstimadaIngreso, '%Y-%m-%d') as fechaEstimadaIngreso
            FROM guia g
            LEFT JOIN identificadores i ON g.idIdentificadores = i.id
            LEFT JOIN logistica l ON g.idLogistica = l.id
            LEFT JOIN fechas f ON g.idFechas = f.id
            LEFT JOIN partes p ON g.idPartes = p.id
            LEFT JOIN bultos b ON g.idBultos = b.id
            LEFT JOIN (
                SELECT 
                    b1.registroId, 
                    b1.idUsuarios
                FROM bitacora b1
                INNER JOIN (
                    SELECT registroId, MAX(id) as maxId
                    FROM bitacora
                    WHERE tablaAfectada = 'guia'
                    AND accion IN ('INSERT', 'UPDATE')
                    GROUP BY registroId
                ) b2 ON b1.registroId = b2.registroId AND b1.id = b2.maxId
                WHERE b1.tablaAfectada = 'guia'
            ) bit ON bit.registroId = g.id
            LEFT JOIN usuarios u ON u.id = bit.idUsuarios
            WHERE DATE(f.fechaEstimadaIngreso) = :fecha
            ORDER BY CAST(g.id AS UNSIGNED) ASC";
        
        return $this->db->raw($sql, [
            ':fecha' => $fecha
        ], 'all');
    }

    /**
     * Validar datos de guía para creación/actualización
     * @param array $data Datos a validar
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data)
    {
        $errors = [];

        // Validar campos requeridos
        if (empty($data['pGuia'])) {
            $errors['pGuia'] = 'Número de guía es requerido';
        }

        if (empty($data['pOrigen'])) {
            $errors['pOrigen'] = 'Origen es requerido';
        }

        if (empty($data['pDestino'])) {
            $errors['pDestino'] = 'Destino es requerido';
        }

        // Validar números positivos
        if (isset($data['pCantidad']) && $data['pCantidad'] !== '' && $data['pCantidad'] !== null) {
            if (!is_numeric($data['pCantidad']) || (int)$data['pCantidad'] <= 0) {
                $errors['pCantidad'] = 'Cantidad debe ser un número positivo';
            }
        }

        if (isset($data['pPesoNeto']) && $data['pPesoNeto'] !== '' && $data['pPesoNeto'] !== null) {
            if (!is_numeric($data['pPesoNeto']) || (float)$data['pPesoNeto'] <= 0) {
                $errors['pPesoNeto'] = 'Peso neto debe ser un número positivo';
            }
        }

        if (isset($data['pVolumen']) && $data['pVolumen'] !== '' && $data['pVolumen'] !== null) {
            if (!is_numeric($data['pVolumen']) || (float)$data['pVolumen'] <= 0) {
                $errors['pVolumen'] = 'Volumen debe ser un número positivo';
            }
        }

        // Validar fechas (si se proporcionan)
        if (isset($data['pEtd']) && $data['pEtd'] !== '') {
            if (!strtotime($data['pEtd'])) {
                $errors['pEtd'] = 'Fecha ETD inválida';
            }
        }

        if (isset($data['pEta']) && $data['pEta'] !== '') {
            if (!strtotime($data['pEta'])) {
                $errors['pEta'] = 'Fecha ETA inválida';
            }
        }

        return $errors;
    }

}