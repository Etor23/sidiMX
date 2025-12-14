<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$reg = $data['partes'] ?? null;
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Partes</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($reg)): ?>
        <div class="alert alert-warning">No se encontró el registro de partes.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Consignatario</th>
                        <th>Contacto</th>
                        <th>Importador RFC</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($reg['id']) ?></td>
                        <td><?= htmlspecialchars($reg['consignatario'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['contacto'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['importadorRfc'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
