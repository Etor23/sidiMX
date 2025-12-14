<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$ident = $data['identificadores'] ?? null;
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Identificadores</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php elseif ($ident && !empty($ident['guia'])): ?>
                <small class="text-muted">Guía: <?= htmlspecialchars($ident['guia']) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($ident)): ?>
        <div class="alert alert-warning">No se encontró el registro de identificadores.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guía externa</th>
                        <th>Master</th>
                        <th>Contenedor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($ident['id']) ?></td>
                        <td><?= htmlspecialchars($ident['guia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ident['master'] ?? '') ?></td>
                        <td><?= htmlspecialchars($ident['contenedor'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
