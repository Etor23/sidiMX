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
        <h4 class="mb-0">Registrar Comprobante de Pago</h4>
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
            <form method="POST" action="<?= URLROOT ?>/comprobantes/store" id="formComprobante" novalidate>
                <input type="hidden" name="pIdGuia" value="<?= htmlspecialchars($guia['id'] ?? '') ?>">
                <input type="hidden" id="totalBaseMXN" value="<?= htmlspecialchars($old['pTotal'] ?? '') ?>">

                <div class="row">
                    <!-- Número de cuenta -->
                    <div class="col-md-6 mb-3" id="grupo_pNumero">
                        <label for="pNumero" class="form-label">Número de cuenta <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control <?= isset($errors['pNumero']) ? 'is-invalid' : '' ?>" 
                            id="pNumero" 
                            name="pNumero" 
                            inputmode="numeric"
                            pattern="\d{10,20}"
                            maxlength="20"
                            placeholder="10 a 20 dígitos"
                            value="<?= htmlspecialchars($old['pNumero'] ?? '') ?>" 
                            required
                        >
                        <?php if (isset($errors['pNumero'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pNumero']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Emisor -->
                    <div class="col-md-6 mb-3" id="grupo_pEmisor">
                        <label for="pEmisor" class="form-label">Emisor <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control <?= isset($errors['pEmisor']) ? 'is-invalid' : '' ?>" 
                            id="pEmisor" 
                            name="pEmisor" 
                            pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s&/\-\.()]{3,100}"
                            maxlength="100"
                            placeholder="Ej: Banco Internacional (México)"
                            value="<?= htmlspecialchars($old['pEmisor'] ?? '') ?>" 
                            required
                        >
                        <?php if (isset($errors['pEmisor'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pEmisor']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Moneda -->
                    <div class="col-md-6 mb-3">
                        <label for="pMoneda" class="form-label">Moneda <span class="text-danger">*</span></label>
                        <select 
                            class="form-select <?= isset($errors['pMoneda']) ? 'is-invalid' : '' ?>" 
                            id="pMoneda" 
                            name="pMoneda" 
                            required
                        >
                            <option value="">Seleccione una moneda</option>
                            <option value="USD" <?= ($old['pMoneda'] ?? '') === 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
                            <option value="MXN" <?= ($old['pMoneda'] ?? '') === 'MXN' ? 'selected' : '' ?>>Pesos Mexicanos (MXN)</option>
                        </select>
                        <?php if (isset($errors['pMoneda'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pMoneda']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Total -->
                    <div class="col-md-6 mb-3">
                        <label for="pTotal" class="form-label">Total (calculado) <span class="text-danger">*</span></label>
                        <input 
                            type="number" 
                            step="0.01" 
                            class="form-control <?= isset($errors['pTotal']) ? 'is-invalid' : '' ?>" 
                            id="pTotal" 
                            name="pTotal" 
                            value="" 
                            readonly
                            style="background-color: #e9ecef;"
                            required
                        >
                        <?php if (isset($errors['pTotal'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pTotal']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= URLROOT ?>/guias" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Comprobante</button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<script>
// Script para actualizar el total según la moneda seleccionada
document.addEventListener('DOMContentLoaded', () => {
    const selectMoneda = document.getElementById('pMoneda');
    const inputTotal = document.getElementById('pTotal');
    const totalBaseMXN = parseFloat(document.getElementById('totalBaseMXN').value) || 0;
    const tasaCambio = 17;

    function actualizarTotal() {
        const moneda = selectMoneda.value;
        
        if (moneda === '') {
            inputTotal.value = '';
        } else if (moneda === 'MXN') {
            inputTotal.value = totalBaseMXN.toFixed(2);
        } else if (moneda === 'USD') {
            const totalUSD = totalBaseMXN / tasaCambio;
            inputTotal.value = totalUSD.toFixed(2);
        }
    }

    selectMoneda.addEventListener('change', actualizarTotal);
    
    // Actualizar al cargar si ya hay una moneda seleccionada
    actualizarTotal();
});
</script>
<script src="<?= URLROOT ?>/assets/js/validacion-comprobante.js?v=1"></script>
<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
