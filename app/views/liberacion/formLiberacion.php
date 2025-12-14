<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guia = $data['guia'] ?? null;
$errors = $data['errors'] ?? [];
$old = $data['old'] ?? [];
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="mb-4">
        <h4 class="mb-0">Autorizar Liberación</h4>
        <small class="text-muted">Guía #<?= htmlspecialchars($guia['id'] ?? '') ?></small>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors['exception'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['exception']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= URLROOT ?>/liberaciones/store" id="formLiberacion" novalidate>
                <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id'] ?? '') ?>">

                <div class="row">
                    <!-- Observaciones -->
                    <div class="col-md-12 mb-3" id="grupo_pObservaciones">
                        <label for="pObservaciones" class="form-label">Observaciones</label>
                        <textarea 
                            class="form-control <?= isset($errors['pObservaciones']) ? 'is-invalid' : '' ?>" 
                            id="pObservaciones" 
                            name="pObservaciones" 
                            placeholder="Ej: Liberación autorizada, sin incidencias"
                            rows="4"
                            minlength="3"
                            maxlength="200"
                        ><?= htmlspecialchars($old['pObservaciones'] ?? '') ?></textarea>
                        <?php if (isset($errors['pObservaciones'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pObservaciones']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= URLROOT ?>/guias" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Liberación</button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<script src="<?= URLROOT ?>/assets/js/validacion-liberacion.js?v=1"></script>
<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
