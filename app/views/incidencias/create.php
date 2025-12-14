<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guia = $data['guia'] ?? [];
$bultosReg = $data['bultosReg'] ?? null;
$bultosReal = $data['bultosReal'] ?? null;
$tipos = $data['tipos'] ?? [];
$errors = $data['errors'] ?? [];
?>

<div class="container mt-3">
    <h4>Registrar incidencia - Guía #<?= htmlspecialchars($guia['id'] ?? '') ?></h4>

    <div class="card mb-3">
        <div class="card-body">
            <h6>Resumen de bultos</h6>
            <div class="row">
                <div class="col-md-6">
                    <strong>Registrados</strong>
                    <?php if ($bultosReg): ?>
                        <ul>
                            <li>Cantidad: <?= htmlspecialchars($bultosReg['cantidad'] ?? '-') ?> (u)</li>
                            <li>Peso neto: <?= htmlspecialchars($bultosReg['pesoNeto'] ?? '-') ?> Kg</li>
                            <li>Volumen: <?= htmlspecialchars($bultosReg['volumen'] ?? '-') ?> cm³</li>
                            <li>Dimensiones (A x H x L): <?= htmlspecialchars($bultosReg['ancho'] ?? '-') ?> cm x <?= htmlspecialchars($bultosReg['alto'] ?? '-') ?> cm x <?= htmlspecialchars($bultosReg['largo'] ?? '-') ?> cm</li>
                        </ul>
                    <?php else: ?>
                        <p>-</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <strong>Reales (recinto)</strong>
                    <?php if ($bultosReal): ?>
                        <ul>
                            <li>Cantidad: <?= htmlspecialchars($bultosReal['cantidad'] ?? '-') ?> (u)</li>
                            <li>Peso neto: <?= htmlspecialchars($bultosReal['pesoNeto'] ?? '-') ?> Kg</li>
                            <li>Volumen: <?= htmlspecialchars($bultosReal['volumen'] ?? '-') ?> cm³</li>
                            <li>Dimensiones (A x H x L): <?= htmlspecialchars($bultosReal['ancho'] ?? '-') ?> cm x <?= htmlspecialchars($bultosReal['alto'] ?? '-') ?> cm x <?= htmlspecialchars($bultosReal['largo'] ?? '-') ?> cm</li>
                        </ul>
                    <?php else: ?>
                        <p>No hay bultos reales registrados aún.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= URLROOT ?>/incidencias/store" id="formIncidencia" novalidate>
        <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id'] ?? '') ?>">

        <div class="mb-3" id="grupo_pIdTiposIncidencia">
            <label for="pIdTiposIncidencia" class="form-label">Tipo de incidencia</label>
            <select id="pIdTiposIncidencia" name="pIdTiposIncidencia" class="form-select" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['tipoIncidencia'] ?? $t['tipo']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="mb-3" id="grupo_pDescripcion">
            <label for="pDescripcion" class="form-label">Descripción</label>
            <textarea id="pDescripcion" name="pDescripcion" class="form-control" rows="4" required></textarea>
            <div class="invalid-feedback"></div>
        </div>

        <button type="submit" class="btn btn-danger">Enviar incidencia</button>
        <a href="<?= URLROOT ?>/guias" class="btn btn-secondary ms-2">Volver</a>
    </form>
</div>

<script src="<?= URLROOT ?>/assets/js/validacion-incidencia.js?v=1"></script>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
