<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$reg = $data['logistica'] ?? null;
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Logística</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($reg)): ?>
        <div class="alert alert-warning">No se encontró el registro de logística.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Aduana</th>
                        <th>Sección</th>
                        <th>Modo</th>
                        <th>Incoterm</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($reg['id']) ?></td>
                        <td><?= htmlspecialchars($reg['origen'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['destino'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['aduana'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['seccion'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['modo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reg['incoterm'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
