<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guia = $data['guia'] ?? [];
$retiro = $data['retiro'] ?? null;
$errors = $data['errors'] ?? [];
?>

<div class="container mt-3">
    <h4>Registrar retiro - Guía #<?= htmlspecialchars($guia['id'] ?? '') ?></h4>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= URLROOT ?>/retiros/store" id="formRetiro" novalidate>
        <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id'] ?? '') ?>">

        <div class="mb-3" id="grupo_pUnidad">
            <label for="pUnidad" class="form-label">Unidad <span class="text-danger">*</span></label>
            <input 
                id="pUnidad" 
                name="pUnidad" 
                class="form-control" 
                value="<?= htmlspecialchars($retiro['unidad'] ?? '') ?>" 
                pattern="[A-Za-z0-9\s\-_./()]{3,20}"
                maxlength="20"
                placeholder="Ej: Unidad-123"
                required
            >
        </div>

        <div class="mb-3" id="grupo_pPlacas">
            <label for="pPlacas" class="form-label">Placas <span class="text-danger">*</span></label>
            <input 
                id="pPlacas" 
                name="pPlacas" 
                class="form-control" 
                value="<?= htmlspecialchars($retiro['placas'] ?? '') ?>" 
                pattern="[A-Z0-9\-]{5,10}"
                maxlength="10"
                placeholder="Ej: ABC-1234"
                style="text-transform: uppercase;"
                required
            >
        </div>

        <div class="mb-3" id="grupo_pOperador">
            <label for="pOperador" class="form-label">Conductor <span class="text-danger">*</span></label>
            <input 
                id="pOperador" 
                name="pOperador" 
                class="form-control" 
                value="<?= htmlspecialchars($retiro['operador'] ?? '') ?>" 
                pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,40}"
                maxlength="40"
                placeholder="Ej: Juan Pérez"
                required
            >
        </div>

        <button type="submit" class="btn btn-success">Registrar retiro</button>
        <a href="<?= URLROOT ?>/guias" class="btn btn-secondary ms-2">Cancelar</a>
    </form>
</div>

<script src="<?= URLROOT ?>/assets/js/validacion-retiro.js?v=1"></script>
<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
