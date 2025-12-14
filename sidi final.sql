-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-12-2025 a las 08:32:52
-- Versión del servidor: 10.4.24-MariaDB
-- Versión de PHP: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sidi`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizarEstadoGuia` (IN `pIdGuia` INT, IN `pEstado` VARCHAR(50), IN `pIdUsuario` INT, OUT `pAfectados` INT)   BEGIN
    -- Establecer variable de sesión para que los triggers la usen
    SET @idUsuarios = pIdUsuario;
    
    -- Actualizar el estado de la guía
    UPDATE guia 
    SET estado = pEstado 
    WHERE id = pIdGuia;
    
    -- Obtener filas afectadas
    SET pAfectados = ROW_COUNT();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `actualizarGuia` (IN `pIdGuia` INT, IN `pGuia` VARCHAR(40), IN `pMaster` VARCHAR(40), IN `pContenedor` VARCHAR(40), IN `pOrigen` VARCHAR(100), IN `pDestino` VARCHAR(100), IN `pAduana` VARCHAR(20), IN `pSeccion` VARCHAR(20), IN `pModo` VARCHAR(20), IN `pIncoterm` VARCHAR(20), IN `pConsignatario` VARCHAR(30), IN `pContacto` VARCHAR(100), IN `pImportadorRfc` VARCHAR(13), IN `pCertTratado` VARCHAR(50), IN `pCertVigencia` DATE, IN `pEtd` DATE, IN `pEta` DATE, IN `pFechaEstIngreso` DATE, IN `pCantidad` INT, IN `pPesoNeto` DOUBLE, IN `pVolumen` DOUBLE, IN `pAncho` DOUBLE, IN `pAlto` DOUBLE, IN `pLargo` DOUBLE, IN `pPermisosJson` JSON, IN `pEstado` VARCHAR(20), IN `pIdUsuario` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vIdFechas INT;
  DECLARE vIdIdentificadores INT;
  DECLARE vIdLogistica INT;
  DECLARE vIdPartes INT;
  DECLARE vIdCertificado INT;
  DECLARE vIdBultos INT;

  -- para permisos JSON
  DECLARE vLen INT DEFAULT 0;
  DECLARE i INT DEFAULT 0;
  DECLARE vTipo VARCHAR(50);
  DECLARE vAutoridad VARCHAR(30);
  DECLARE vVigStr VARCHAR(20);
  DECLARE vVigencia DATE;
  DECLARE vIdPerm INT;

  DECLARE vExisteGuia INT DEFAULT 0;
  DECLARE vEstadoAnterior VARCHAR(20);

  -- Si algo truena, rollback
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
  -- Setear variable de sesión para trigger (bitácora)
IF pIdUsuario IS NOT NULL THEN
  SET @idUsuarios = pIdUsuario;
ELSE
  SET @idUsuarios = NULL;
END IF;

  -- Validar que exista la guía y capturar estado anterior para bitácora
  SELECT COUNT(*), estado INTO vExisteGuia, vEstadoAnterior
  FROM Guia
  WHERE id = pIdGuia;

  IF vExisteGuia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no existe';
  END IF;

  -- Validación JSON permisos (si vienen)
  IF pPermisosJson IS NOT NULL AND JSON_VALID(pPermisosJson) = 0 THEN
    SIGNAL SQLSTATE '22032'
      SET MESSAGE_TEXT = 'pPermisosJson no es JSON válido';
  END IF;

  -- Obtener IDs relacionados actuales
  SELECT idFechas, idIdentificadores, idLogistica, idPartes, idCertificadoOrigen, idBultos
    INTO vIdFechas, vIdIdentificadores, vIdLogistica, vIdPartes, vIdCertificado, vIdBultos
  FROM Guia
  WHERE id = pIdGuia
  LIMIT 1;

  -- 1) Fechas
  UPDATE Fechas
  SET etd = pEtd,
      eta = pEta,
      fechaEstimadaIngreso = COALESCE(pFechaEstIngreso, pEta)
  WHERE id = vIdFechas;

  -- 2) Identificadores
  UPDATE Identificadores
  SET guia = pGuia,
      master = pMaster,
      contenedor = pContenedor
  WHERE id = vIdIdentificadores;

  -- 3) Logística
  UPDATE Logistica
  SET origen = pOrigen,
      destino = pDestino,
      aduana = pAduana,
      seccion = pSeccion,
      modo = pModo,
      incoterm = pIncoterm
  WHERE id = vIdLogistica;

  -- 4) Partes
  UPDATE Partes
  SET consignatario = pConsignatario,
      contacto = pContacto,
      importadorRfc = pImportadorRfc
  WHERE id = vIdPartes;

  -- 5) Certificado (opcional)
  IF pCertTratado IS NULL OR TRIM(pCertTratado) = '' THEN
    -- Si ahora no hay certificado, desvincula y borra el anterior (0-1 real)
    IF vIdCertificado IS NOT NULL THEN
      UPDATE Guia
      SET idCertificadoOrigen = NULL
      WHERE id = pIdGuia;

      DELETE FROM CertificadoOrigen
      WHERE id = vIdCertificado;

      SET vIdCertificado = NULL;
    END IF;
  ELSE
    -- Si antes no tenía, crea; si tenía, actualiza
    IF vIdCertificado IS NULL THEN
      INSERT INTO CertificadoOrigen(tratado, vigencia)
      VALUES (pCertTratado, pCertVigencia);
      SET vIdCertificado = LAST_INSERT_ID();

      UPDATE Guia
      SET idCertificadoOrigen = vIdCertificado
      WHERE id = pIdGuia;
    ELSE
      UPDATE CertificadoOrigen
      SET tratado = pCertTratado,
          vigencia = pCertVigencia
      WHERE id = vIdCertificado;
    END IF;
  END IF;

  -- 6) Bultos
  UPDATE Bultos
  SET cantidad = pCantidad,
      pesoNeto = pPesoNeto,
      volumen = pVolumen,
      ancho = pAncho,
      alto = pAlto,
      largo = pLargo
  WHERE id = vIdBultos;

  -- 7) Guia (estado solo si viene)
  UPDATE Guia
  SET estado = COALESCE(pEstado, estado)
  WHERE id = pIdGuia;

  SET pAfectados = ROW_COUNT();

  -- 8) Permisos (reemplazar lista)
  DELETE FROM PermisosPaquete
  WHERE idGuia = pIdGuia;

  IF pPermisosJson IS NOT NULL AND JSON_LENGTH(pPermisosJson) > 0 THEN
    SET vLen = JSON_LENGTH(pPermisosJson);
    SET i = 0;

    WHILE i < vLen DO
      SET vTipo = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].tipo'))));
      SET vAutoridad = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].autoridad'))));
      SET vVigStr = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].vigencia'))));

      IF vVigStr IS NULL OR vVigStr = '' THEN
        SET vVigencia = NULL;
      ELSE
        SET vVigencia = STR_TO_DATE(vVigStr, '%Y-%m-%d');
      END IF;

      IF vTipo IS NOT NULL AND vTipo <> '' THEN
        SET vIdPerm = NULL;

        SELECT id INTO vIdPerm
        FROM Permisos
        WHERE tipo = vTipo
          AND (autoridad <=> vAutoridad)
          AND (vigencia <=> vVigencia)
        LIMIT 1;

        IF vIdPerm IS NULL THEN
          INSERT INTO Permisos(tipo, autoridad, vigencia)
          VALUES (vTipo, vAutoridad, vVigencia);
          SET vIdPerm = LAST_INSERT_ID();
        END IF;

        INSERT INTO PermisosPaquete(idGuia, idPermisos)
        VALUES (pIdGuia, vIdPerm);
      END IF;

      SET i = i + 1;
    END WHILE;
  END IF;

  -- 9) Registrar en bitácora (si se pasó usuario)
  IF pIdUsuario IS NOT NULL THEN
    INSERT INTO Bitacora(idUsuarios, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
    VALUES (
      pIdUsuario,
      'guia',
      pIdGuia,
      'actualizar',
      JSON_OBJECT('estado', vEstadoAnterior),
      JSON_OBJECT(
        'guia', pGuia,
        'estado', COALESCE(pEstado, vEstadoAnterior)
      )
    );
  END IF;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `crearGuia` (IN `pGuia` VARCHAR(40), IN `pMaster` VARCHAR(40), IN `pContenedor` VARCHAR(40), IN `pOrigen` VARCHAR(100), IN `pDestino` VARCHAR(100), IN `pAduana` VARCHAR(20), IN `pSeccion` VARCHAR(20), IN `pModo` VARCHAR(20), IN `pIncoterm` VARCHAR(20), IN `pConsignatario` VARCHAR(30), IN `pContacto` VARCHAR(100), IN `pImportadorRfc` VARCHAR(13), IN `pCertTratado` VARCHAR(50), IN `pCertVigencia` DATE, IN `pEtd` DATE, IN `pEta` DATE, IN `pFechaEstIngreso` DATE, IN `pCantidad` INT, IN `pPesoNeto` DOUBLE, IN `pVolumen` DOUBLE, IN `pAncho` DOUBLE, IN `pAlto` DOUBLE, IN `pLargo` DOUBLE, IN `pPermisosJson` JSON, IN `pEstado` VARCHAR(20), IN `pIdUsuario` INT, OUT `pIdGuia` INT)   BEGIN
  DECLARE vIdFechas INT;
  DECLARE vIdIdentificadores INT;
  DECLARE vIdLogistica INT;
  DECLARE vIdPartes INT;
  DECLARE vIdCertificado INT;
  DECLARE vIdBultos INT;

  -- para permisos JSON
  DECLARE vLen INT DEFAULT 0;
  DECLARE i INT DEFAULT 0;
  DECLARE vTipo VARCHAR(50);
  DECLARE vAutoridad VARCHAR(30);
  DECLARE vVigStr VARCHAR(20);
  DECLARE vVigencia DATE;
  DECLARE vIdPerm INT;

  -- Si algo truena, rollback
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;
	-- Setear variable de sesión para trigger (bitácora)
IF pIdUsuario IS NOT NULL THEN
  SET @idUsuarios = pIdUsuario;
ELSE
  SET @idUsuarios = NULL;
END IF;
  -- Validación JSON permisos (si vienen)
  IF pPermisosJson IS NOT NULL AND JSON_VALID(pPermisosJson) = 0 THEN
    SIGNAL SQLSTATE '22032'
      SET MESSAGE_TEXT = 'pPermisosJson no es JSON válido';
  END IF;

  -- 1) Fechas
  INSERT INTO Fechas(etd, eta, fechaEstimadaIngreso)
  VALUES (pEtd, pEta, COALESCE(pFechaEstIngreso, pEta));
  SET vIdFechas = LAST_INSERT_ID();

  -- 2) Identificadores
  INSERT INTO Identificadores(guia, master, contenedor)
  VALUES (pGuia, pMaster, pContenedor);
  SET vIdIdentificadores = LAST_INSERT_ID();

  -- 3) Logística
  INSERT INTO Logistica(origen, destino, aduana, seccion, modo, incoterm)
  VALUES (pOrigen, pDestino, pAduana, pSeccion, pModo, pIncoterm);
  SET vIdLogistica = LAST_INSERT_ID();

  -- 4) Partes
  INSERT INTO Partes(consignatario, contacto, importadorRfc)
  VALUES (pConsignatario, pContacto, pImportadorRfc);
  SET vIdPartes = LAST_INSERT_ID();

  -- 5) Certificado (opcional)
  IF pCertTratado IS NULL OR TRIM(pCertTratado) = '' THEN
    SET vIdCertificado = NULL;
  ELSE
    INSERT INTO CertificadoOrigen(tratado, vigencia)
    VALUES (pCertTratado, pCertVigencia);
    SET vIdCertificado = LAST_INSERT_ID();
  END IF;

  -- 6) Bultos
  INSERT INTO Bultos(cantidad, pesoNeto, volumen, ancho, alto, largo)
  VALUES (pCantidad, pPesoNeto, pVolumen, pAncho, pAlto, pLargo);
  SET vIdBultos = LAST_INSERT_ID();

  -- 7) Guia
  INSERT INTO Guia(
    idFechas,
    idIdentificadores,
    idLogistica,
    idPartes,
    idCertificadoOrigen,
    idBultos,
    estado
  )
  VALUES (
    vIdFechas,
    vIdIdentificadores,
    vIdLogistica,
    vIdPartes,
    vIdCertificado,
    vIdBultos,
    COALESCE(pEstado, 'preAlerta')
  );
  SET pIdGuia = LAST_INSERT_ID();

  -- 8) Permisos desde JSON
  IF pPermisosJson IS NOT NULL AND JSON_LENGTH(pPermisosJson) > 0 THEN
    SET vLen = JSON_LENGTH(pPermisosJson);
    SET i = 0;

    WHILE i < vLen DO
      SET vTipo = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].tipo'))));
      SET vAutoridad = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].autoridad'))));
      SET vVigStr = TRIM(JSON_UNQUOTE(JSON_EXTRACT(pPermisosJson, CONCAT('$[', i, '].vigencia'))));

      -- vigencia opcional
      IF vVigStr IS NULL OR vVigStr = '' THEN
        SET vVigencia = NULL;
      ELSE
        SET vVigencia = STR_TO_DATE(vVigStr, '%Y-%m-%d');
      END IF;

      IF vTipo IS NOT NULL AND vTipo <> '' THEN
        -- Reusar permiso si existe mismo tipo+autoridad+vigencia
        SET vIdPerm = NULL;
        SELECT id INTO vIdPerm
        FROM Permisos
        WHERE tipo = vTipo
          AND (autoridad <=> vAutoridad)
          AND (vigencia <=> vVigencia)
        LIMIT 1;

        IF vIdPerm IS NULL THEN
          INSERT INTO Permisos(tipo, autoridad, vigencia)
          VALUES (vTipo, vAutoridad, vVigencia);
          SET vIdPerm = LAST_INSERT_ID();
        END IF;

        INSERT INTO PermisosPaquete(idGuia, idPermisos)
        VALUES (pIdGuia, vIdPerm);
      END IF;

      SET i = i + 1;
    END WHILE;
  END IF;

  -- 9) Registrar en bitácora (si se pasó usuario)
  IF pIdUsuario IS NOT NULL THEN
    INSERT INTO Bitacora(idUsuarios, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
    VALUES (
      pIdUsuario,
      'guia',
      pIdGuia,
      'crear',
      NULL,
      JSON_OBJECT(
        'guia', pGuia,
        'master', pMaster,
        'contenedor', pContenedor,
        'estado', COALESCE(pEstado, 'preAlerta')
      )
    );
  END IF;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `crearUsuario` (IN `pNombre` VARCHAR(40), IN `pIdRoles` INT, IN `pContrasenaHash` VARCHAR(255), IN `pIdUsuariosCreador` INT, OUT `pIdUsuarioNuevo` INT)   BEGIN
  DECLARE vExiste INT DEFAULT 0;
  DECLARE vRolOk  INT DEFAULT 0;

  -- Si algo falla, rollback
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validaciones básicas
  IF pNombre IS NULL OR TRIM(pNombre) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El nombre es obligatorio';
  END IF;

  IF pIdRoles IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El rol es obligatorio';
  END IF;

  IF pContrasenaHash IS NULL OR TRIM(pContrasenaHash) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'contrasenaHash es obligatoria (debe venir hasheada desde PHP)';
  END IF;

  -- Verificar que exista el rol
  SELECT COUNT(*) INTO vRolOk
  FROM roles
  WHERE id = pIdRoles;

  IF vRolOk = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El rol indicado no existe';
  END IF;

  -- Evitar duplicar nombres de usuario
  SELECT COUNT(*) INTO vExiste
  FROM usuarios
  WHERE nombre = pNombre;

  IF vExiste > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Ya existe un usuario con ese nombre';
  END IF;

  -- Setear usuario creador para Bitacora (si viene)
  IF pIdUsuariosCreador IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosCreador;
  END IF;

  -- Insertar usuario
  INSERT INTO usuarios (nombre, idRoles, contrasenaHash)
  VALUES (pNombre, pIdRoles, pContrasenaHash);

  SET pIdUsuarioNuevo = LAST_INSERT_ID();

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `editarUsuario` (IN `pIdUsuario` INT, IN `pNombre` VARCHAR(40), IN `pIdRoles` INT, IN `pContrasenaHash` VARCHAR(255), IN `pIdUsuariosEditor` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vExiste INT DEFAULT 0;
  DECLARE vRolOk  INT DEFAULT 0;
  DECLARE vUserOk INT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validaciones
  IF pIdUsuario IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El id del usuario a editar es obligatorio';
  END IF;

  SELECT COUNT(*) INTO vUserOk
  FROM usuarios
  WHERE id = pIdUsuario;

  IF vUserOk = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario a editar no existe';
  END IF;

  IF pNombre IS NULL OR TRIM(pNombre) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El nombre es obligatorio';
  END IF;

  IF pIdRoles IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El rol es obligatorio';
  END IF;

  SELECT COUNT(*) INTO vRolOk
  FROM roles
  WHERE id = pIdRoles;

  IF vRolOk = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El rol indicado no existe';
  END IF;

  -- No permitir duplicar el nombre (excepto el mismo usuario)
  SELECT COUNT(*) INTO vExiste
  FROM usuarios
  WHERE nombre = pNombre
    AND id <> pIdUsuario;

  IF vExiste > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Ya existe otro usuario con ese nombre';
  END IF;

  -- Setear editor para Bitacora
  IF pIdUsuariosEditor IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosEditor;
  END IF;

  -- Update: si no viene hash, no toca contraseña
  IF pContrasenaHash IS NULL OR TRIM(pContrasenaHash) = '' THEN
    UPDATE usuarios
    SET nombre = pNombre,
        idRoles = pIdRoles
    WHERE id = pIdUsuario;
  ELSE
    UPDATE usuarios
    SET nombre = pNombre,
        idRoles = pIdRoles,
        contrasenaHash = pContrasenaHash
    WHERE id = pIdUsuario;
  END IF;

  SET pAfectados = ROW_COUNT();

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `eliminarGuia` (IN `pIdGuia` INT, IN `pIdUsuario` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vExisteGuia INT DEFAULT 0;
  DECLARE vIdFechas INT;
  DECLARE vIdIdentificadores INT;
  DECLARE vIdLogistica INT;
  DECLARE vIdPartes INT;
  DECLARE vIdCertificado INT;
  DECLARE vIdBultos INT;

  -- Si algo truena, rollback
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Setear variable de sesión para trigger (bitácora)
  IF pIdUsuario IS NOT NULL THEN
    SET @idUsuarios = pIdUsuario;
  ELSE
    SET @idUsuarios = NULL;
  END IF;

  -- Validar que exista la guía
  SELECT COUNT(*) INTO vExisteGuia
  FROM Guia
  WHERE id = pIdGuia;

  IF vExisteGuia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no existe';
  END IF;

  -- Obtener IDs de las tablas relacionadas para eliminarlas después
  SELECT idFechas, idIdentificadores, idLogistica, idPartes, idCertificadoOrigen, idBultos
    INTO vIdFechas, vIdIdentificadores, vIdLogistica, vIdPartes, vIdCertificado, vIdBultos
  FROM Guia
  WHERE id = pIdGuia
  LIMIT 1;

  -- 1) Eliminar permisos asociados
  DELETE FROM PermisosPaquete
  WHERE idGuia = pIdGuia;

  -- 2) Eliminar guía (trigger registra en bitácora)
  DELETE FROM Guia
  WHERE id = pIdGuia;

  SET pAfectados = ROW_COUNT();

  -- 3) Eliminar registros relacionados (datos huérfanos)
  IF vIdFechas IS NOT NULL THEN
    DELETE FROM Fechas WHERE id = vIdFechas;
  END IF;

  IF vIdIdentificadores IS NOT NULL THEN
    DELETE FROM Identificadores WHERE id = vIdIdentificadores;
  END IF;

  IF vIdLogistica IS NOT NULL THEN
    DELETE FROM Logistica WHERE id = vIdLogistica;
  END IF;

  IF vIdPartes IS NOT NULL THEN
    DELETE FROM Partes WHERE id = vIdPartes;
  END IF;

  IF vIdCertificado IS NOT NULL THEN
    DELETE FROM CertificadoOrigen WHERE id = vIdCertificado;
  END IF;

  IF vIdBultos IS NOT NULL THEN
    DELETE FROM Bultos WHERE id = vIdBultos;
  END IF;

  -- 4) Eliminar registros en bitácora relacionados (opcional, si quieres limpiar historial)
  -- DELETE FROM Bitacora WHERE tablaAfectada = 'Guia' AND registroId = pIdGuia;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `eliminarUsuario` (IN `pIdUsuario` INT, IN `pIdUsuariosEliminador` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vUserOk INT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  IF pIdUsuario IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El id del usuario a eliminar es obligatorio';
  END IF;

  SELECT COUNT(*) INTO vUserOk
  FROM usuarios
  WHERE id = pIdUsuario;

  IF vUserOk = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario a eliminar no existe';
  END IF;

  -- Setear eliminador para Bitacora
  IF pIdUsuariosEliminador IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosEliminador;
  END IF;

  DELETE FROM usuarios
  WHERE id = pIdUsuario;

  SET pAfectados = ROW_COUNT();

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarBitacoraPDF` (IN `pIdUsuario` INT, IN `pTipoPDF` VARCHAR(50), IN `pIdGuia` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO bitacoraPDF (
        idUsuario,
        tipoPDF,
        idGuia,
        fechaHora
    ) VALUES (
        pIdUsuario,
        pTipoPDF,
        pIdGuia,
        NOW()
    );

    COMMIT;
    
    SELECT LAST_INSERT_ID() AS idBitacora, ROW_COUNT() AS afectados;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarBultosReal` (IN `pIdGuia` INT, IN `pCantidad` INT, IN `pPesoNeto` DOUBLE, IN `pVolumen` DOUBLE, IN `pAncho` DOUBLE, IN `pAlto` DOUBLE, IN `pLargo` DOUBLE, IN `pIdUsuariosRecinto` INT, OUT `pIdBultosReal` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vExisteGuia INT DEFAULT 0;
  DECLARE vIdBR       INT DEFAULT NULL;
  DECLARE vEstadoActual VARCHAR(20);

  -- rollback si falla algo
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validar guía existente y traer estado
  SELECT COUNT(*), estado
    INTO vExisteGuia, vEstadoActual
  FROM guia
  WHERE id = pIdGuia
  LIMIT 1;

  IF vExisteGuia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no existe';
  END IF;

  -- setear usuario de recinto para triggers de bitácora (si viene)
  IF pIdUsuariosRecinto IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosRecinto;
  END IF;

  -- Ver si ya existe bultosreal de esa guía (1-1 lógico)
  SELECT id INTO vIdBR
  FROM bultosreal
  WHERE idGuia = pIdGuia
  LIMIT 1;

  IF vIdBR IS NULL THEN
    -- INSERT
    INSERT INTO bultosreal
      (idGuia, cantidad, pesoNeto, volumen, ancho, alto, largo)
    VALUES
      (pIdGuia, pCantidad, pPesoNeto, pVolumen, pAncho, pAlto, pLargo);

    SET pIdBultosReal = LAST_INSERT_ID();
    SET pAfectados = 1;
  ELSE
    -- UPDATE
    UPDATE bultosreal
    SET cantidad = pCantidad,
        pesoNeto = pPesoNeto,
        volumen  = pVolumen,
        ancho    = pAncho,
        alto     = pAlto,
        largo    = pLargo
    WHERE id = vIdBR;

    SET pIdBultosReal = vIdBR;
    SET pAfectados = ROW_COUNT();
  END IF;

  -- Cambiar estado a ordenDePago solo si no está en estados posteriores
  IF vEstadoActual IN ('preAlerta','enRecinto','conIncidencia') THEN
    UPDATE guia
    SET estado = 'ordenDePago'
    WHERE id = pIdGuia;
  END IF;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarComprobante` (IN `pIdGuia` INT, IN `pNumero` VARCHAR(100), IN `pEmisor` VARCHAR(255), IN `pMoneda` VARCHAR(10), IN `pTotal` DECIMAL(10,2), IN `pIdUsuario` INT, OUT `pIdComprobante` INT, OUT `pAfectados` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET pIdComprobante = NULL;
        SET pAfectados = 0;
    END;
    
    START TRANSACTION;
    
    -- Establecer variable de sesión para los triggers
    SET @idUsuarios = pIdUsuario;
    
    -- Insertar comprobante con fecha actual
    INSERT INTO comprobante (idGuia, numero, emisor, fecha, moneda, total)
    VALUES (pIdGuia, pNumero, pEmisor, CURDATE(), pMoneda, pTotal);
    
    SET pIdComprobante = LAST_INSERT_ID();
    SET pAfectados = ROW_COUNT();
    
    -- Actualizar idComprobante en la tabla pedimento para esta guía
    UPDATE pedimento 
    SET idComprobante = pIdComprobante 
    WHERE idGuia = pIdGuia;
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarIncidencia` (IN `pIdGuia` INT, IN `pIdTiposIncidencia` INT, IN `pDescripcion` VARCHAR(200), IN `pIdUsuariosOperador` INT, OUT `pIdIncidenciaNueva` INT)   BEGIN
  DECLARE vExisteGuia INT DEFAULT 0;
  DECLARE vExisteTipo INT DEFAULT 0;
  DECLARE vEstadoActual VARCHAR(20);

  -- rollback si falla algo
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validar guía
  SELECT COUNT(*), estado
    INTO vExisteGuia, vEstadoActual
  FROM guia
  WHERE id = pIdGuia
  LIMIT 1;

  IF vExisteGuia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no existe';
  END IF;

  -- Validar tipo de incidencia
  SELECT COUNT(*) INTO vExisteTipo
  FROM tiposincidencia
  WHERE id = pIdTiposIncidencia;

  IF vExisteTipo = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El tipo de incidencia no existe';
  END IF;

  IF pDescripcion IS NULL OR TRIM(pDescripcion) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La descripción es obligatoria';
  END IF;

  -- setear usuario para triggers de bitácora (si viene)
  IF pIdUsuariosOperador IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosOperador;
  END IF;

  -- Insertar incidencia
  INSERT INTO incidencia (idGuia, idTiposIncidencia, descripcion)
  VALUES (pIdGuia, pIdTiposIncidencia, pDescripcion);

  SET pIdIncidenciaNueva = LAST_INSERT_ID();

  -- Cambiar estado a conIncidencia si no está en estados finales
  IF vEstadoActual NOT IN ('pagado','liberado','retirado') THEN
    UPDATE guia
    SET estado = 'conIncidencia'
    WHERE id = pIdGuia;
  END IF;

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarLiberacion` (IN `pIdGuia` INT, IN `pIdUsuario` INT, IN `pObservaciones` VARCHAR(200), OUT `pIdLiberacion` INT, OUT `pAfectados` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET pIdLiberacion = NULL;
        SET pAfectados = 0;
    END;
    
    START TRANSACTION;
    
    -- Establecer variable de sesión para los triggers
    SET @idUsuarios = pIdUsuario;
    
    -- Insertar registro de liberación con fecha/hora actual
    INSERT INTO liberacion (idGuia, fechaHora, idUsuario, observaciones)
    VALUES (pIdGuia, NOW(), pIdUsuario, pObservaciones);
    
    SET pIdLiberacion = LAST_INSERT_ID();
    SET pAfectados = ROW_COUNT();
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarPedimento` (IN `pIdGuia` INT, IN `pRegimen` VARCHAR(255), IN `pPatente` VARCHAR(100), IN `pNumero` VARCHAR(100), IN `pIdUsuarios` INT, OUT `pIdPedimento` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vGuiaExiste INT DEFAULT 0;
  DECLARE vPedimentoExiste INT DEFAULT 0;

  -- Si algo falla, rollback
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validaciones básicas
  IF pIdGuia IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El ID de guía es obligatorio';
  END IF;

  IF pRegimen IS NULL OR TRIM(pRegimen) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El régimen es obligatorio';
  END IF;

  IF pPatente IS NULL OR TRIM(pPatente) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La patente es obligatoria';
  END IF;

  IF pNumero IS NULL OR TRIM(pNumero) = '' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El número es obligatorio';
  END IF;

  -- Verificar que exista la guía
  SELECT COUNT(*) INTO vGuiaExiste
  FROM guia
  WHERE id = pIdGuia;

  IF vGuiaExiste = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía indicada no existe';
  END IF;

  -- Verificar que no exista ya un pedimento para esta guía
  SELECT COUNT(*) INTO vPedimentoExiste
  FROM pedimento
  WHERE idGuia = pIdGuia;

  IF vPedimentoExiste > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Ya existe un pedimento registrado para esta guía';
  END IF;

  -- Setear usuario para Bitacora
  IF pIdUsuarios IS NOT NULL THEN
    SET @idUsuarios = pIdUsuarios;
  END IF;

  -- Insertar pedimento (idComprobante es NULL por defecto)
  INSERT INTO pedimento (idGuia, regimen, patente, numero)
  VALUES (pIdGuia, pRegimen, pPatente, pNumero);

  SET pIdPedimento = LAST_INSERT_ID();
  SET pAfectados = ROW_COUNT();

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarPOD` (IN `pIdGuia` INT, IN `pReceptor` VARCHAR(60), IN `pCondicion` VARCHAR(30), IN `pObservaciones` VARCHAR(200))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    INSERT INTO pod (
        idGuia,
        receptor,
        horaEntrega,
        condicion,
        observaciones
    ) VALUES (
        pIdGuia,
        pReceptor,
        NOW(),
        pCondicion,
        pObservaciones
    );

    COMMIT;
    
    SELECT LAST_INSERT_ID() AS idPOD, ROW_COUNT() AS afectados;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `registrarRetiro` (IN `pIdGuia` INT, IN `pUnidad` VARCHAR(20), IN `pPlacas` VARCHAR(20), IN `pOperador` VARCHAR(40), IN `pIdUsuariosRecinto` INT, OUT `pIdRetiroNuevo` INT, OUT `pAfectados` INT)   BEGIN
  DECLARE vExisteGuia INT DEFAULT 0;
  DECLARE vEstadoActual VARCHAR(20);
  DECLARE vIdRetiro INT DEFAULT NULL;

  -- rollback si falla algo
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- Validar guía existente y traer estado
  SELECT COUNT(*), estado
    INTO vExisteGuia, vEstadoActual
  FROM guia
  WHERE id = pIdGuia
  LIMIT 1;

  IF vExisteGuia = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no existe';
  END IF;

  -- Validar estado permitido para retiro
  -- Solo se puede retirar si está liberado (o ya retirado para permitir re-registro)
  IF vEstadoActual NOT IN ('liberado','retirado') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La guía no está liberada para retiro';
  END IF;

  -- setear usuario recinto para triggers de bitácora (si viene)
  IF pIdUsuariosRecinto IS NOT NULL THEN
    SET @idUsuarios = pIdUsuariosRecinto;
  END IF;

  -- Ver si ya existe un retiro para esta guía
  SELECT id INTO vIdRetiro
  FROM retiro
  WHERE idGuia = pIdGuia
  LIMIT 1;

  IF vIdRetiro IS NULL THEN
    -- INSERT con fecha actual
    INSERT INTO retiro (idGuia, unidad, placas, operador, fechaProgramada)
    VALUES (pIdGuia, pUnidad, pPlacas, pOperador, NOW());

    SET pIdRetiroNuevo = LAST_INSERT_ID();
    SET pAfectados = 1;
  ELSE
    -- UPDATE y refresca fecha a actual
    UPDATE retiro
    SET unidad = pUnidad,
        placas = pPlacas,
        operador = pOperador,
        fechaProgramada = NOW()
    WHERE id = vIdRetiro;

    SET pIdRetiroNuevo = vIdRetiro;
    SET pAfectados = ROW_COUNT();
  END IF;

  -- Cambiar estado a retirado
  UPDATE guia
  SET estado = 'retirado'
  WHERE id = pIdGuia;

  COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id` int(11) NOT NULL,
  `idUsuarios` int(11) DEFAULT NULL,
  `fechaHora` datetime DEFAULT NULL,
  `tablaAfectada` varchar(50) DEFAULT NULL,
  `registroId` int(11) DEFAULT NULL,
  `accion` varchar(30) DEFAULT NULL,
  `valorAnterior` text DEFAULT NULL,
  `valorNuevo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacorapdf`
--

CREATE TABLE `bitacorapdf` (
  `id` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `tipoPDF` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idGuia` int(11) NOT NULL,
  `fechaHora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bitacorapdf`
--


--
-- Disparadores `bitacorapdf`
--
DELIMITER $$
CREATE TRIGGER `trgBitacoraPDFAi` AFTER INSERT ON `bitacorapdf` FOR EACH ROW BEGIN
    DECLARE nombreUsuario VARCHAR(100);
    
    
    SELECT nombre INTO nombreUsuario 
    FROM usuarios 
    WHERE id = NEW.idUsuario 
    LIMIT 1;
    
    
    INSERT INTO bitacora (
        idUsuarios,
        fechaHora,
        tablaAfectada,
        registroId,
        accion,
        valorAnterior,
        valorNuevo
    ) VALUES (
        NEW.idUsuario,
        NOW(),
        'bitacoraPDF',
        NEW.id,
        'INSERT',
        NULL,
        CONCAT('PDF generado: ', NEW.tipoPDF, ' | ID GuÝa: ', NEW.idGuia, ' | Usuario: ', COALESCE(nombreUsuario, 'Desconocido'))
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bultos`
--

CREATE TABLE `bultos` (
  `id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `pesoNeto` double DEFAULT NULL,
  `volumen` double DEFAULT NULL,
  `ancho` double DEFAULT NULL,
  `alto` double DEFAULT NULL,
  `largo` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `bultos`
--



-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bultosreal`
--

CREATE TABLE `bultosreal` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `pesoNeto` double DEFAULT NULL,
  `volumen` double DEFAULT NULL,
  `ancho` double DEFAULT NULL,
  `alto` double DEFAULT NULL,
  `largo` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `bultosreal`
--



--
-- Disparadores `bultosreal`
--
DELIMITER $$
CREATE TRIGGER `trgBultosRealAd` AFTER DELETE ON `bultosreal` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'BultosReal', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'cantidad', OLD.cantidad,
     'pesoNeto', OLD.pesoNeto,
     'volumen', OLD.volumen,
     'ancho', OLD.ancho,
     'alto', OLD.alto,
     'largo', OLD.largo
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgBultosRealAi` AFTER INSERT ON `bultosreal` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'BultosReal', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'cantidad', NEW.cantidad,
     'pesoNeto', NEW.pesoNeto,
     'volumen', NEW.volumen,
     'ancho', NEW.ancho,
     'alto', NEW.alto,
     'largo', NEW.largo
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgBultosRealAu` AFTER UPDATE ON `bultosreal` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'BultosReal', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'cantidad', OLD.cantidad,
     'pesoNeto', OLD.pesoNeto,
     'volumen', OLD.volumen,
     'ancho', OLD.ancho,
     'alto', OLD.alto,
     'largo', OLD.largo
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'cantidad', NEW.cantidad,
     'pesoNeto', NEW.pesoNeto,
     'volumen', NEW.volumen,
     'ancho', NEW.ancho,
     'alto', NEW.alto,
     'largo', NEW.largo
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificadoorigen`
--

CREATE TABLE `certificadoorigen` (
  `id` int(11) NOT NULL,
  `tratado` varchar(50) DEFAULT NULL,
  `vigencia` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `certificadoorigen`
--



-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobante`
--

CREATE TABLE `comprobante` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `numero` varchar(40) DEFAULT NULL,
  `emisor` varchar(100) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `moneda` varchar(30) DEFAULT NULL,
  `total` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `comprobante`
--


--
-- Disparadores `comprobante`
--
DELIMITER $$
CREATE TRIGGER `trgComprobanteAd` AFTER DELETE ON `comprobante` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Comprobante', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'numero', OLD.numero,
     'emisor', OLD.emisor,
     'fecha', OLD.fecha,
     'moneda', OLD.moneda,
     'total', OLD.total
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgComprobanteAi` AFTER INSERT ON `comprobante` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Comprobante', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'numero', NEW.numero,
     'emisor', NEW.emisor,
     'fecha', NEW.fecha,
     'moneda', NEW.moneda,
     'total', NEW.total
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgComprobanteAu` AFTER UPDATE ON `comprobante` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Comprobante', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'numero', OLD.numero,
     'emisor', OLD.emisor,
     'fecha', OLD.fecha,
     'moneda', OLD.moneda,
     'total', OLD.total
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'numero', NEW.numero,
     'emisor', NEW.emisor,
     'fecha', NEW.fecha,
     'moneda', NEW.moneda,
     'total', NEW.total
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fechas`
--

CREATE TABLE `fechas` (
  `id` int(11) NOT NULL,
  `etd` date DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `fechaEstimadaIngreso` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `fechas`
--



-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `guia`
--

CREATE TABLE `guia` (
  `id` int(11) NOT NULL,
  `idFechas` int(11) NOT NULL,
  `idIdentificadores` int(11) NOT NULL,
  `idLogistica` int(11) NOT NULL,
  `idPartes` int(11) NOT NULL,
  `idCertificadoOrigen` int(11) DEFAULT NULL,
  `idBultos` int(11) NOT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `guia`
--



--
-- Disparadores `guia`
--
DELIMITER $$
CREATE TRIGGER `trgGuiaAd` AFTER DELETE ON `guia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  (idUsuarios, fechaHora, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
  VALUES
  (COALESCE(@idUsuarios, NULL), NOW(), 'Guia', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idFechas', OLD.idFechas,
     'idIdentificadores', OLD.idIdentificadores,
     'idLogistica', OLD.idLogistica,
     'idPartes', OLD.idPartes,
     'idCertificadoOrigen', OLD.idCertificadoOrigen,
     'idBultos', OLD.idBultos,
     'estado', OLD.estado
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgGuiaAi` AFTER INSERT ON `guia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  (idUsuarios, fechaHora, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
  VALUES
  (COALESCE(@idUsuarios, NULL), NOW(), 'Guia', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idFechas', NEW.idFechas,
     'idIdentificadores', NEW.idIdentificadores,
     'idLogistica', NEW.idLogistica,
     'idPartes', NEW.idPartes,
     'idCertificadoOrigen', NEW.idCertificadoOrigen,
     'idBultos', NEW.idBultos,
     'estado', NEW.estado
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgGuiaAu` AFTER UPDATE ON `guia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  (idUsuarios, fechaHora, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
  VALUES
  (COALESCE(@idUsuarios, NULL), NOW(), 'Guia', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idFechas', OLD.idFechas,
     'idIdentificadores', OLD.idIdentificadores,
     'idLogistica', OLD.idLogistica,
     'idPartes', OLD.idPartes,
     'idCertificadoOrigen', OLD.idCertificadoOrigen,
     'idBultos', OLD.idBultos,
     'estado', OLD.estado
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idFechas', NEW.idFechas,
     'idIdentificadores', NEW.idIdentificadores,
     'idLogistica', NEW.idLogistica,
     'idPartes', NEW.idPartes,
     'idCertificadoOrigen', NEW.idCertificadoOrigen,
     'idBultos', NEW.idBultos,
     'estado', NEW.estado
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `identificadores`
--

CREATE TABLE `identificadores` (
  `id` int(11) NOT NULL,
  `guia` varchar(40) DEFAULT NULL,
  `master` varchar(40) DEFAULT NULL,
  `contenedor` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `identificadores`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencia`
--

CREATE TABLE `incidencia` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `idTiposIncidencia` int(11) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `incidencia`
--


--
-- Disparadores `incidencia`
--
DELIMITER $$
CREATE TRIGGER `trgIncidenciaAd` AFTER DELETE ON `incidencia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Incidencia', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'idTiposIncidencia', OLD.idTiposIncidencia,
     'descripcion', OLD.descripcion
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgIncidenciaAi` AFTER INSERT ON `incidencia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Incidencia', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'idTiposIncidencia', NEW.idTiposIncidencia,
     'descripcion', NEW.descripcion
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgIncidenciaAu` AFTER UPDATE ON `incidencia` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Incidencia', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'idTiposIncidencia', OLD.idTiposIncidencia,
     'descripcion', OLD.descripcion
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'idTiposIncidencia', NEW.idTiposIncidencia,
     'descripcion', NEW.descripcion
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inspeccion`
--

CREATE TABLE `inspeccion` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `resultado` varchar(10) DEFAULT NULL,
  `hallazgos` varchar(200) DEFAULT NULL,
  `actaNum` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Disparadores `inspeccion`
--
DELIMITER $$
CREATE TRIGGER `trgInspeccionAd` AFTER DELETE ON `inspeccion` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Inspeccion', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'resultado', OLD.resultado,
     'hallazgos', OLD.hallazgos,
     'actaNum', OLD.actaNum
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgInspeccionAi` AFTER INSERT ON `inspeccion` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Inspeccion', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'resultado', NEW.resultado,
     'hallazgos', NEW.hallazgos,
     'actaNum', NEW.actaNum
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgInspeccionAu` AFTER UPDATE ON `inspeccion` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Inspeccion', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'resultado', OLD.resultado,
     'hallazgos', OLD.hallazgos,
     'actaNum', OLD.actaNum
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'resultado', NEW.resultado,
     'hallazgos', NEW.hallazgos,
     'actaNum', NEW.actaNum
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `liberacion`
--

CREATE TABLE `liberacion` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `fechaHora` datetime NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `observaciones` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `liberacion`


--
-- Disparadores `liberacion`
--
DELIMITER $$
CREATE TRIGGER `trgLiberacionAi` AFTER INSERT ON `liberacion` FOR EACH ROW BEGIN
    DECLARE vJson JSON;
    
    -- Crear objeto JSON con todos los campos del registro insertado
    SET vJson = JSON_OBJECT(
        'id', NEW.id,
        'idGuia', NEW.idGuia,
        'fechaHora', NEW.fechaHora,
        'idUsuario', NEW.idUsuario,
        'observaciones', NEW.observaciones
    );
    
    -- Registrar en bitácora
    INSERT INTO bitacora (idUsuarios, tablaAfectada, registroId, accion, valorAnterior, valorNuevo)
    VALUES (
        @idUsuarios,
        'liberacion',
        NEW.id,
        'INSERT',
        NULL,
        vJson
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logistica`
--

CREATE TABLE `logistica` (
  `id` int(11) NOT NULL,
  `origen` varchar(40) NOT NULL,
  `destino` varchar(40) NOT NULL,
  `aduana` varchar(20) DEFAULT NULL,
  `seccion` varchar(20) DEFAULT NULL,
  `modo` varchar(20) DEFAULT NULL,
  `incoterm` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `logistica`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partes`
--

CREATE TABLE `partes` (
  `id` int(11) NOT NULL,
  `consignatario` varchar(30) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `importadorRfc` varchar(13) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `partes`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedimento`
--

CREATE TABLE `pedimento` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `regimen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patente` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idComprobante` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedimento`
--


--
-- Disparadores `pedimento`
--
DELIMITER $$
CREATE TRIGGER `trgPedimentoAi` AFTER INSERT ON `pedimento` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'pedimento', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'regimen', NEW.regimen,
     'patente', NEW.patente,
     'numero', NEW.numero,
     'idComprobante', NEW.idComprobante
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `autoridad` varchar(30) DEFAULT NULL,
  `vigencia` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `permisos`
--


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisospaquete`
--

CREATE TABLE `permisospaquete` (
  `idGuia` int(11) NOT NULL,
  `idPermisos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `permisospaquete`
--


--
-- Disparadores `permisospaquete`
--
DELIMITER $$
CREATE TRIGGER `trgPermisosPaqueteAd` AFTER DELETE ON `permisospaquete` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'PermisosPaquete', OLD.idGuia, 'DELETE',
   JSON_OBJECT(
     'idGuia', OLD.idGuia,
     'idPermisos', OLD.idPermisos
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgPermisosPaqueteAi` AFTER INSERT ON `permisospaquete` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'PermisosPaquete', NEW.idGuia, 'INSERT',
   NULL,
   JSON_OBJECT(
     'idGuia', NEW.idGuia,
     'idPermisos', NEW.idPermisos
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pod`
--

CREATE TABLE `pod` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `receptor` varchar(60) DEFAULT NULL,
  `horaEntrega` datetime DEFAULT NULL,
  `condicion` varchar(30) DEFAULT NULL,
  `observaciones` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `pod`
--



--
-- Disparadores `pod`
--
DELIMITER $$
CREATE TRIGGER `trgPodAd` AFTER DELETE ON `pod` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'POD', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'receptor', OLD.receptor,
     'horaEntrega', OLD.horaEntrega,
     'condicion', OLD.condicion,
     'observaciones', OLD.observaciones
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgPodAi` AFTER INSERT ON `pod` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'POD', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'receptor', NEW.receptor,
     'horaEntrega', NEW.horaEntrega,
     'condicion', NEW.condicion,
     'observaciones', NEW.observaciones
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgPodAu` AFTER UPDATE ON `pod` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'POD', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'receptor', OLD.receptor,
     'horaEntrega', OLD.horaEntrega,
     'condicion', OLD.condicion,
     'observaciones', OLD.observaciones
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'receptor', NEW.receptor,
     'horaEntrega', NEW.horaEntrega,
     'condicion', NEW.condicion,
     'observaciones', NEW.observaciones
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `retiro`
--

CREATE TABLE `retiro` (
  `id` int(11) NOT NULL,
  `idGuia` int(11) NOT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `placas` varchar(20) DEFAULT NULL,
  `operador` varchar(40) DEFAULT NULL,
  `fechaProgramada` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `retiro`


--
-- Disparadores `retiro`
--
DELIMITER $$
CREATE TRIGGER `trgRetiroAd` AFTER DELETE ON `retiro` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Retiro', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'unidad', OLD.unidad,
     'placas', OLD.placas,
     'operador', OLD.operador,
     'fechaProgramada', OLD.fechaProgramada
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgRetiroAi` AFTER INSERT ON `retiro` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Retiro', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'unidad', NEW.unidad,
     'placas', NEW.placas,
     'operador', NEW.operador,
     'fechaProgramada', NEW.fechaProgramada
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgRetiroAu` AFTER UPDATE ON `retiro` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Retiro', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'idGuia', OLD.idGuia,
     'unidad', OLD.unidad,
     'placas', OLD.placas,
     'operador', OLD.operador,
     'fechaProgramada', OLD.fechaProgramada
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'idGuia', NEW.idGuia,
     'unidad', NEW.unidad,
     'placas', NEW.placas,
     'operador', NEW.operador,
     'fechaProgramada', NEW.fechaProgramada
   ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `rol` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `rol`) VALUES
(1, 'Operador'),
(2, 'Recinto'),
(3, 'Supervisor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarifas`
--

CREATE TABLE `tarifas` (
  `id` int(10) UNSIGNED NOT NULL,
  `kg` decimal(10,4) NOT NULL,
  `volumen` decimal(10,6) NOT NULL,
  `kgExtra` decimal(10,4) NOT NULL,
  `volumenExtra` decimal(10,6) NOT NULL,
  `aduana` decimal(6,4) NOT NULL,
  `iva` decimal(6,4) NOT NULL,
  `cantExtra` decimal(6,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `tarifas`
--

INSERT INTO `tarifas` (`id`, `kg`, `volumen`, `kgExtra`, `volumenExtra`, `aduana`, `iva`, `cantExtra`) VALUES
(1, '6.00', '0.15', '12.00', '0.30', '0.01', '0.16', '0.02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposincidencia`
--

CREATE TABLE `tiposincidencia` (
  `id` int(11) NOT NULL,
  `tipoIncidencia` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `tiposincidencia`
--

INSERT INTO `tiposincidencia` (`id`, `tipoIncidencia`) VALUES
(1, 'Diferencia de peso'),
(2, 'Diferencia de tamaño'),
(3, 'Diferencia de cantidad');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `idRoles` int(11) NOT NULL,
  `contrasenaHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `idRoles`, `contrasenaHash`) VALUES
(1, 'Ana Operadora', 1, '$2y$10$anaOperadoraHashDemo'),
(2, 'Rene Recinto', 2, '$2y$10$reneRecintoHashDemo'),
(14, 'Hector', 3, '$2y$10$zX7MUB8k7YRvElmgTmi53eyhhbTe.xqwXboD7ZGAoZtD0aeH79Zm2'),
(17, 'Retanaa', 3, '$2y$10$Ut4wIILx.6ARY2q2huutleA2/1H.Y3qpnk3UuU1gYLh7myUO9k.ma'),
(19, 'Kiko', 2, '$2y$10$Ozb46tQWIwVmFbhy8Y0eZefaaBOGT/HYYIqr405tCTGuhIybtHT.u'),
(20, 'Jose', 1, '$2y$10$3jzBSW3Cs7UcuBFuO6gdmOCiil2JFPBABglXmsIruz4wuujtOirHu'),
(22, 'Emilia', 3, '$2y$10$sMvqXgW3bZmjnWOA7CbxrOSsoqeaVaRbeYoYXFr7dAsWcGexySX0q'),
(23, 'Deku', 1, '$2y$10$Eu1xNIQ7WxVduuHQ5fl6LeRfWTRJEd9jN42HoVefikTNRxvpFqkb2'),
(24, 'Jared', 1, '$2y$10$YQUPfD4gxCQVqaxGgIdC7OiS9G.v6xt.R7xkGc5ZKM2tO6qUq3NHC'),
(26, 'Pancho', 2, '$2y$10$RYwt.YBa.h/lyyZ/QNGMz.ToRsHE4AlvyvZQXL0QPsT383SQHIPsm'),
(27, 'Neone', 1, '$2y$10$xtawTdkBzl5EDqM3iGtxIuJbzKBGrVnAJg6bw1iI9RUOMShpv/R8S'),
(28, 'Farolo', 1, '$2y$10$uOYMRyRBvO6Ude4RvxeiiOQPGlgI8FC8k2dkgpmQPeeIU41ZoCiFy'),
(29, 'Sancho panza', 1, '$2y$10$YGSAiHqsB3N2YfFiY1RTS.wNP5WUErfUr0gl5CnLRYEjllUJOVP1u');

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trgUsuariosAd` AFTER DELETE ON `usuarios` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Usuarios', OLD.id, 'DELETE',
   JSON_OBJECT(
     'id', OLD.id,
     'nombre', OLD.nombre,
     'idRoles', OLD.idRoles
   ),
   NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgUsuariosAi` AFTER INSERT ON `usuarios` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Usuarios', NEW.id, 'INSERT',
   NULL,
   JSON_OBJECT(
     'id', NEW.id,
     'nombre', NEW.nombre,
     'idRoles', NEW.idRoles
   ));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trgUsuariosAu` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
  INSERT INTO Bitacora
  VALUES
  (NULL, COALESCE(@idUsuarios, NULL), NOW(), 'Usuarios', NEW.id, 'UPDATE',
   JSON_OBJECT(
     'id', OLD.id,
     'nombre', OLD.nombre,
     'idRoles', OLD.idRoles
   ),
   JSON_OBJECT(
     'id', NEW.id,
     'nombre', NEW.nombre,
     'idRoles', NEW.idRoles
   ));
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxBitacoraIdUsuarios` (`idUsuarios`);

--
-- Indices de la tabla `bitacorapdf`
--
ALTER TABLE `bitacorapdf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idUsuario` (`idUsuario`),
  ADD KEY `idGuia` (`idGuia`);

--
-- Indices de la tabla `bultos`
--
ALTER TABLE `bultos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `bultosreal`
--
ALTER TABLE `bultosreal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uqBultosRealIdGuia` (`idGuia`),
  ADD KEY `idxBultosRealIdGuia` (`idGuia`);

--
-- Indices de la tabla `certificadoorigen`
--
ALTER TABLE `certificadoorigen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uqComprobanteIdGuia` (`idGuia`),
  ADD KEY `idxComprobanteIdGuia` (`idGuia`);

--
-- Indices de la tabla `fechas`
--
ALTER TABLE `fechas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `guia`
--
ALTER TABLE `guia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uqGuiaIdFechas` (`idFechas`),
  ADD UNIQUE KEY `uqGuiaIdIdentificadores` (`idIdentificadores`),
  ADD UNIQUE KEY `uqGuiaIdLogistica` (`idLogistica`),
  ADD UNIQUE KEY `uqGuiaIdPartes` (`idPartes`),
  ADD UNIQUE KEY `uqGuiaIdBultos` (`idBultos`),
  ADD UNIQUE KEY `uqGuiaIdCertificadoOrigen` (`idCertificadoOrigen`),
  ADD KEY `idxGuiaIdFechas` (`idFechas`),
  ADD KEY `idxGuiaIdIdentificadores` (`idIdentificadores`),
  ADD KEY `idxGuiaIdLogistica` (`idLogistica`),
  ADD KEY `idxGuiaIdPartes` (`idPartes`),
  ADD KEY `idxGuiaIdCertificadoOrigen` (`idCertificadoOrigen`),
  ADD KEY `idxGuiaIdBultos` (`idBultos`);

--
-- Indices de la tabla `identificadores`
--
ALTER TABLE `identificadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guia` (`guia`);

--
-- Indices de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxIncidenciaIdGuia` (`idGuia`),
  ADD KEY `idxIncidenciaIdTiposIncidencia` (`idTiposIncidencia`);

--
-- Indices de la tabla `inspeccion`
--
ALTER TABLE `inspeccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxInspeccionIdGuia` (`idGuia`);

--
-- Indices de la tabla `liberacion`
--
ALTER TABLE `liberacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idUsuario` (`idUsuario`),
  ADD KEY `fkLiberacionGuia` (`idGuia`);

--
-- Indices de la tabla `logistica`
--
ALTER TABLE `logistica`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `partes`
--
ALTER TABLE `partes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pedimento`
--
ALTER TABLE `pedimento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedimento_comprobante` (`idComprobante`),
  ADD KEY `fkPedimentoGuia` (`idGuia`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `permisospaquete`
--
ALTER TABLE `permisospaquete`
  ADD PRIMARY KEY (`idGuia`,`idPermisos`),
  ADD KEY `idxPermisosPaqueteIdPermisos` (`idPermisos`);

--
-- Indices de la tabla `pod`
--
ALTER TABLE `pod`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxPodIdGuia` (`idGuia`);

--
-- Indices de la tabla `retiro`
--
ALTER TABLE `retiro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxRetiroIdGuia` (`idGuia`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tarifas`
--
ALTER TABLE `tarifas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tiposincidencia`
--
ALTER TABLE `tiposincidencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idxUsuariosIdRoles` (`idRoles`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT de la tabla `bitacorapdf`
--
ALTER TABLE `bitacorapdf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `bultos`
--
ALTER TABLE `bultos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `bultosreal`
--
ALTER TABLE `bultosreal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `certificadoorigen`
--
ALTER TABLE `certificadoorigen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `comprobante`
--
ALTER TABLE `comprobante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `guia`
--
ALTER TABLE `guia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `identificadores`
--
ALTER TABLE `identificadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `inspeccion`
--
ALTER TABLE `inspeccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `liberacion`
--
ALTER TABLE `liberacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `logistica`
--
ALTER TABLE `logistica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `partes`
--
ALTER TABLE `partes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `pedimento`
--
ALTER TABLE `pedimento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pod`
--
ALTER TABLE `pod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `retiro`
--
ALTER TABLE `retiro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tarifas`
--
ALTER TABLE `tarifas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tiposincidencia`
--
ALTER TABLE `tiposincidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `fkBitacoraUsuarios` FOREIGN KEY (`idUsuarios`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `bitacorapdf`
--
ALTER TABLE `bitacorapdf`
  ADD CONSTRAINT `bitacorapdf_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bitacorapdf_ibfk_2` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `bultosreal`
--
ALTER TABLE `bultosreal`
  ADD CONSTRAINT `fkBultosRealGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `comprobante`
--
ALTER TABLE `comprobante`
  ADD CONSTRAINT `fkComprobanteGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `guia`
--
ALTER TABLE `guia`
  ADD CONSTRAINT `fkGuiaBultos` FOREIGN KEY (`idBultos`) REFERENCES `bultos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkGuiaCertificadoOrigen` FOREIGN KEY (`idCertificadoOrigen`) REFERENCES `certificadoorigen` (`id`),
  ADD CONSTRAINT `fkGuiaFechas` FOREIGN KEY (`idFechas`) REFERENCES `fechas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkGuiaIdentificadores` FOREIGN KEY (`idIdentificadores`) REFERENCES `identificadores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkGuiaLogistica` FOREIGN KEY (`idLogistica`) REFERENCES `logistica` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkGuiaPartes` FOREIGN KEY (`idPartes`) REFERENCES `partes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `fkIncidenciaGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkIncidenciaTiposIncidencia` FOREIGN KEY (`idTiposIncidencia`) REFERENCES `tiposincidencia` (`id`);

--
-- Filtros para la tabla `inspeccion`
--
ALTER TABLE `inspeccion`
  ADD CONSTRAINT `fkInspeccionGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`);

--
-- Filtros para la tabla `liberacion`
--
ALTER TABLE `liberacion`
  ADD CONSTRAINT `fkLiberacionGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `liberacion_ibfk_1` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`),
  ADD CONSTRAINT `liberacion_ibfk_2` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedimento`
--
ALTER TABLE `pedimento`
  ADD CONSTRAINT `fkPedimentoGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedimento_comprobante` FOREIGN KEY (`idComprobante`) REFERENCES `comprobante` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedimento_guia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `permisospaquete`
--
ALTER TABLE `permisospaquete`
  ADD CONSTRAINT `fkPermisosPaqueteGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fkPermisosPaquetePermisos` FOREIGN KEY (`idPermisos`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pod`
--
ALTER TABLE `pod`
  ADD CONSTRAINT `fkPODGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `retiro`
--
ALTER TABLE `retiro`
  ADD CONSTRAINT `fkRetiroGuia` FOREIGN KEY (`idGuia`) REFERENCES `guia` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fkUsuariosRoles` FOREIGN KEY (`idRoles`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
