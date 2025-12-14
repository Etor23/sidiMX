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
        <h4 class="mb-0">Elaborar Pedimento</h4>
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
            <form method="POST" action="<?= URLROOT ?>/pedimentos/store" id="formPedimento" novalidate>
                <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id'] ?? '') ?>">

                <div class="row">
                    <!-- Régimen -->
                    <div class="col-md-6 mb-3" id="grupo_pRegimen">
                        <label for="pRegimen" class="form-label">Régimen <span class="text-danger">*</span></label>
                        <select
                            class="form-select <?= isset($errors['pRegimen']) ? 'is-invalid' : '' ?>"
                            id="pRegimen"
                            name="pRegimen"
                            required
                        >
                            <option value="">-- Seleccione --</option>
                            <?php
                                $regimenes = [
                                    'Importación definitiva',
                                    'Importación temporal',
                                    'Devolucion'
                                ];
                                $regimenSeleccionado = $old['pRegimen'] ?? '';
                            ?>
                            <?php foreach ($regimenes as $regimen): ?>
                                <option value="<?= htmlspecialchars($regimen) ?>" <?= ($regimen === $regimenSeleccionado) ? 'selected' : '' ?>><?= htmlspecialchars($regimen) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['pRegimen'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pRegimen']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Patente -->
                    <div class="col-md-6 mb-3" id="grupo_pPatente">
                        <label for="pPatente" class="form-label">Patente <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control <?= isset($errors['pPatente']) ? 'is-invalid' : '' ?>" 
                            id="pPatente" 
                            name="pPatente"
                            inputmode="numeric"
                            pattern="\d{4}"
                            maxlength="4"
                            placeholder="4 dígitos"
                            value="<?= htmlspecialchars($old['pPatente'] ?? '') ?>" 
                            required
                        >
                        <?php if (isset($errors['pPatente'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pPatente']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Número -->
                    <div class="col-md-6 mb-3" id="grupo_pNumero">
                        <label for="pNumero" class="form-label">Número <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control <?= isset($errors['pNumero']) ? 'is-invalid' : '' ?>" 
                            id="pNumero" 
                            name="pNumero"
                            inputmode="numeric"
                            pattern="\d{15}"
                            maxlength="15"
                            placeholder="15 dígitos"
                            value="<?= htmlspecialchars($old['pNumero'] ?? '') ?>" 
                            required
                        >
                        <?php if (isset($errors['pNumero'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pNumero']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= URLROOT ?>/guias" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Pedimento</button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<script src="<?= URLROOT ?>/assets/js/validacion-pedimento.js?v=1"></script>
<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
