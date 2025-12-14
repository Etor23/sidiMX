<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$reg = $data['comprobante'] ?? null;
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Comprobante</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($reg)): ?>
        <div class="alert alert-warning">No se encontró el comprobante para esta guía.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID Guía</th>
                        <th>Número</th>
                        <th>Emisor</th>
                        <th>Fecha</th>
                        <th>Moneda</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($reg['id']) ?></td>
                        <td><?= htmlspecialchars($reg['idGuia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['numero'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['emisor'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['fecha'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['moneda'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['total'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
