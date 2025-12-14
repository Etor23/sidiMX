<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$reg = $data['certificado'] ?? null;
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Certificado</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($reg)): ?>
        <div class="alert alert-warning">No se encontró el registro de certificado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tratado</th>
                        <th>Vigencia</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($reg['id']) ?></td>
                        <td><?= htmlspecialchars($reg['tratado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['vigencia'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
