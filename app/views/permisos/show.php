<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$permisos = $data['permisos'] ?? [];
$guiaId = $data['guiaId'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Permisos</h4>
            <?php if (!empty($guiaId)): ?>
                <small class="text-muted">Guía ID: <?= htmlspecialchars($guiaId) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <?php if (empty($permisos)): ?>
        <div class="alert alert-warning">No hay permisos asociados a esta guía.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Autoridad</th>
                        <th>Vigencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permisos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['id']) ?></td>
                            <td><?= htmlspecialchars($p['tipo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['autoridad'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['vigencia'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
