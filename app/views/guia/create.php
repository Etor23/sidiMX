<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$errors = $data['errors'] ?? [];
$old = $data['old'] ?? [];
$isEdit = !empty($data['edit']) || !empty($old['pIdGuia']);
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <h3><?= $isEdit ? 'Editar Guía' : 'Crear Guía' ?></h3>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= URLROOT ?>/guias/<?= $isEdit ? 'update' : 'store' ?>" id="formCrearGuia" novalidate data-is-edit="<?= $isEdit ? '1' : '0' ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($old['pIdGuia'] ?? '') ?>">
        <?php endif; ?>
        <div class="card mb-3">
            <div class="card-header">Identificadores</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4" id="grupo_pGuia">
                        <label class="form-label">Guía externa</label>
                        <input id="pGuia" name="pGuia" class="form-control" value="<?= htmlspecialchars($old['pGuia'] ?? '') ?>">
                        <div class="text-danger"><?= htmlspecialchars($errors['pGuia'] ?? '') ?></div>
                    </div>
                    <div class="col-md-4" id="grupo_pMaster">
                        <label class="form-label">Master</label>
                        <input id="pMaster" name="pMaster" class="form-control" value="<?= htmlspecialchars($old['pMaster'] ?? '') ?>">
                    </div>
                    <div class="col-md-4" id="grupo_pContenedor">
                        <label class="form-label">Contenedor</label>
                        <input id="pContenedor" name="pContenedor" class="form-control" value="<?= htmlspecialchars($old['pContenedor'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Logística</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4" id="grupo_pOrigen"><label class="form-label">Origen</label><input id="pOrigen" name="pOrigen" class="form-control" value="<?= htmlspecialchars($old['pOrigen'] ?? '') ?>"></div>
                    <div class="col-md-4" id="grupo_pDestino"><label class="form-label">Destino</label><input id="pDestino" name="pDestino" class="form-control" value="<?= htmlspecialchars($old['pDestino'] ?? '') ?>"></div>
                    <div class="col-md-2" id="grupo_pAduana"><label class="form-label">Aduana</label><input id="pAduana" name="pAduana" class="form-control" value="<?= htmlspecialchars($old['pAduana'] ?? '') ?>"></div>
                    <div class="col-md-2"><label class="form-label">Sección</label><input name="pSeccion" class="form-control" value="Importación" disabled readonly></div>
                    <div class="col-md-3 mt-2" id="grupo_pModo"><label class="form-label">Modo</label>
                        <select id="pModo" name="pModo" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="Terrestre" <?= ($old['pModo'] ?? '') === 'Terrestre' ? 'selected' : '' ?>>Terrestre</option>
                            <option value="Aereo" <?= ($old['pModo'] ?? '') === 'Aereo' ? 'selected' : '' ?>>Aéreo</option>
                            <option value="Maritimo" <?= ($old['pModo'] ?? '') === 'Maritimo' ? 'selected' : '' ?>>Marítimo</option>
                        </select>
                    </div>
                    <div class="col-md-3 mt-2" id="grupo_pIncoterm"><label class="form-label">Incoterm</label><input id="pIncoterm" name="pIncoterm" class="form-control" value="<?= htmlspecialchars($old['pIncoterm'] ?? '') ?>"></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Partes / Certificado</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4" id="grupo_pConsignatario"><label class="form-label">Consignatario</label><input id="pConsignatario" name="pConsignatario" class="form-control" value="<?= htmlspecialchars($old['pConsignatario'] ?? '') ?>"></div>
                    <div class="col-md-4" id="grupo_pContacto"><label class="form-label">Contacto</label><input id="pContacto" name="pContacto" type="email" class="form-control" value="<?= htmlspecialchars($old['pContacto'] ?? '') ?>"></div>
                    <div class="col-md-4" id="grupo_pImportadorRfc"><label class="form-label">Importador RFC</label><input id="pImportadorRfc" name="pImportadorRfc" class="form-control" value="<?= htmlspecialchars($old['pImportadorRfc'] ?? '') ?>"></div>

                    <div class="col-md-6 mt-2" id="grupo_pCertTratado"><label class="form-label">Certificado - Tratado</label><input id="pCertTratado" name="pCertTratado" class="form-control" value="<?= htmlspecialchars($old['pCertTratado'] ?? '') ?>"></div>
                    <div class="col-md-6 mt-2" id="grupo_pCertVigencia"><label class="form-label">Certificado - Vigencia</label><input id="pCertVigencia" type="date" name="pCertVigencia" class="form-control" value="<?= htmlspecialchars($old['pCertVigencia'] ?? '') ?>"></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Permisos (puedes agregar varios)</div>
            <div class="card-body">
                <div id="permisosContainer">
                    <!-- filas dinámicas -->
                </div>
                <button type="button" id="addPermisoBtn" class="btn btn-sm btn-outline-primary mt-2">Agregar permiso</button>
                <input type="hidden" name="pPermisosJson" id="pPermisosJson" value='<?= htmlspecialchars($old['pPermisosJson'] ?? '') ?>'>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Bultos</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-2" id="grupo_pCantidad"><label class="form-label">Cantidad</label><input id="pCantidad" name="pCantidad" type="number" class="form-control" value="<?= htmlspecialchars($old['pCantidad'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-2" id="grupo_pPesoNeto"><label class="form-label">Peso Neto (Kg)</label><input id="pPesoNeto" name="pPesoNeto" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($old['pPesoNeto'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-2" id="grupo_pAncho"><label class="form-label">Ancho (cm)</label><input id="pAncho" name="pAncho" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($old['pAncho'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-2" id="grupo_pAlto"><label class="form-label">Alto (cm)</label><input id="pAlto" name="pAlto" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($old['pAlto'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-2" id="grupo_pLargo"><label class="form-label">Largo (cm)</label><input id="pLargo" name="pLargo" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($old['pLargo'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-2" id="grupo_pVolumen"><label class="form-label">Volumen (cm³)</label><input id="pVolumen" name="pVolumen" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($old['pVolumen'] ?? '') ?>" disabled><div class="invalid-feedback"></div></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Fechas</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4" id="grupo_pEtd"><label class="form-label">Fecha de salida del país de origen</label><input id="pEtd" name="pEtd" type="date" class="form-control" value="<?= htmlspecialchars($old['pEtd'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-4" id="grupo_pEta"><label class="form-label">Fecha de entrada a México</label><input id="pEta" name="pEta" type="date" class="form-control" value="<?= htmlspecialchars($old['pEta'] ?? '') ?>"><div class="invalid-feedback"></div></div>
                    <div class="col-md-4" id="grupo_pFechaEstIngreso"><label class="form-label">Fecha de ingreso</label><input id="pFechaEstIngreso" name="pFechaEstIngreso" type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly><div class="invalid-feedback"></div></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear Guía' ?></button>
        </div>
    </form>

    <script src="<?= URLROOT ?>/assets/js/agregar-permisos-guia.js"></script>
    <script src="<?= URLROOT ?>/assets/js/validacion-guia.js"></script>

<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
