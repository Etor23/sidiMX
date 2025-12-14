<?php require_once APPROOT . '/views/layouts/header.inc.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Registrar POD (Proof of Delivery)</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Guía: <?= htmlspecialchars($data['guia']['id'] ?? '') ?></h5>
                    </div>

                    <form action="<?= URLROOT ?>/pods/store" method="POST" id="formPOD" novalidate>
                        <input type="hidden" name="pIdGuia" value="<?= $data['guia']['id'] ?>">

                        <div class="mb-3" id="grupo_pReceptor">
                            <label for="pReceptor" class="form-label">Receptor <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= !empty($data['errors']['pReceptor']) ? 'is-invalid' : '' ?>" 
                                   id="pReceptor" 
                                   name="pReceptor" 
                                   pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,40}"
                                   maxlength="40"
                                   placeholder="Ej: Juan Pérez"
                                   value="<?= $data['pReceptor'] ?? '' ?>"
                                   required>
                            <?php if (!empty($data['errors']['pReceptor'])): ?>
                                <div class="invalid-feedback"><?= $data['errors']['pReceptor'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3" id="grupo_pCondicion">
                            <label for="pCondicion" class="form-label">Condición <span class="text-danger">*</span></label>
                            <select class="form-select <?= !empty($data['errors']['pCondicion']) ? 'is-invalid' : '' ?>" 
                                    id="pCondicion" 
                                    name="pCondicion"
                                    required>
                                <option value="">Seleccione...</option>
                                <option value="Excelente" <?= (isset($data['pCondicion']) && $data['pCondicion'] === 'Excelente') ? 'selected' : '' ?>>Excelente</option>
                                <option value="Buena" <?= (isset($data['pCondicion']) && $data['pCondicion'] === 'Buena') ? 'selected' : '' ?>>Buena</option>
                                <option value="Regular" <?= (isset($data['pCondicion']) && $data['pCondicion'] === 'Regular') ? 'selected' : '' ?>>Regular</option>
                                <option value="Dañada" <?= (isset($data['pCondicion']) && $data['pCondicion'] === 'Dañada') ? 'selected' : '' ?>>Dañada</option>
                            </select>
                            <?php if (!empty($data['errors']['pCondicion'])): ?>
                                <div class="invalid-feedback"><?= $data['errors']['pCondicion'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3" id="grupo_pObservaciones">
                            <label for="pObservaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control <?= !empty($data['errors']['pObservaciones']) ? 'is-invalid' : '' ?>" 
                                      id="pObservaciones" 
                                      name="pObservaciones" 
                                      rows="4"
                                      maxlength="200"
                                      placeholder="Máximo 200 caracteres"><?= $data['pObservaciones'] ?? '' ?></textarea>
                            <?php if (!empty($data['errors']['pObservaciones'])): ?>
                                <div class="invalid-feedback"><?= $data['errors']['pObservaciones'] ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Máximo 200 caracteres</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Registrar POD</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= URLROOT ?>/assets/js/validacion-pod.js?v=1"></script>
<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
