<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guia = $data['guia'] ?? null;
$bultos = $data['bultos'] ?? null;
$bultosReal = $data['bultosReal'] ?? null;
$usuarioNombre = $_SESSION['usuario_nombre'] ?? '';

?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <h4>Recepción a recinto - Guía <?= htmlspecialchars($guia['id'] ?? '') ?></h4>

    <?php if (empty($bultos)): ?>
        <div class="alert alert-warning">No hay datos de bultos registrados para esta guía.</div>
    <?php else: ?>

        <form method="post" action="<?= URLROOT ?>/recepcion/store" id="formRecepcion">
            <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id']) ?>">

            <div class="card mb-3">
                <div class="card-header">Bultos registrados</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2"><label class="form-label">Cantidad</label><input class="form-control" value="<?= htmlspecialchars($bultos['cantidad'] ?? '') ?>" readonly disabled></div>
                        <div class="col-md-2"><label class="form-label">Peso Neto (Kg)</label><input class="form-control" value="<?= htmlspecialchars($bultos['pesoNeto'] ?? '') ?>" readonly disabled></div>
                        <div class="col-md-2"><label class="form-label">Ancho (cm)</label><input class="form-control" value="<?= htmlspecialchars($bultos['ancho'] ?? '') ?>" readonly disabled></div>
                        <div class="col-md-2"><label class="form-label">Alto (cm)</label><input class="form-control" value="<?= htmlspecialchars($bultos['alto'] ?? '') ?>" readonly disabled></div>
                        <div class="col-md-2"><label class="form-label">Largo (cm)</label><input class="form-control" value="<?= htmlspecialchars($bultos['largo'] ?? '') ?>" readonly disabled></div>
                        <div class="col-md-2"><label class="form-label">Volumen (cm³)</label><input class="form-control" value="<?= htmlspecialchars($bultos['volumen'] ?? '') ?>" readonly disabled></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Bultos reales</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2" id="grupo_pCantidad"><label class="form-label">Cantidad real</label><input name="pCantidad" id="pCantidad" type="number" class="form-control" value="" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-2" id="grupo_pPesoNeto"><label class="form-label">Peso Neto real (Kg)</label><input name="pPesoNeto" id="pPesoNeto" type="number" step="0.01" class="form-control" value="" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-2" id="grupo_pAncho"><label class="form-label">Ancho real (cm)</label><input name="pAncho" id="pAncho" type="number" step="0.01" class="form-control" value="" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-2" id="grupo_pAlto"><label class="form-label">Alto real (cm)</label><input name="pAlto" id="pAlto" type="number" step="0.01" class="form-control" value="" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-2" id="grupo_pLargo"><label class="form-label">Largo real (cm)</label><input name="pLargo" id="pLargo" type="number" step="0.01" class="form-control" value="" required><div class="invalid-feedback"></div></div>
                        <div class="col-md-2" id="grupo_pVolumen"><label class="form-label">Volumen real (cm³)</label><input name="pVolumen" id="pVolumen" type="number" step="0.01" class="form-control" value="" required disabled><div class="invalid-feedback"></div></div>
                    </div>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="confirmCheck">
                <label class="form-check-label" for="confirmCheck">
                    Yo <strong><?= htmlspecialchars($usuarioNombre) ?></strong> aseguro que todo lo puesto aquí ha sido revisado imparcialmente
                </label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="<?= URLROOT ?>/guias" class="btn btn-secondary me-2">Cancelar</a>
                <button id="submitBtn" type="submit" class="btn btn-primary" disabled>Enviar</button>
            </div>
        </form>

        <script src="<?= URLROOT ?>/assets/js/validacion-recepcion.js?v=1"></script>

    <?php endif; ?>

<?php endif; ?>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
