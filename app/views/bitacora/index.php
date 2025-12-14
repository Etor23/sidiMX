<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$rows = $data['rows'] ?? [];
$pagination = $data['pagination'] ?? null;
/**
 * Renderiza valores de JSOn de la bitacora
 * @param mixed $val
 * @return string
 */
function renderMaybeJson($val){
    if ($val === null) return '<em>-</em>';
    $decoded = json_decode($val, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
        return '<pre>' . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
    // not JSON
    return htmlspecialchars((string)$val);
}
?>

<div class="container mt-3">
    <h3>Bitácora</h3>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>FechaHora</th>
                    <th>Tabla</th>
                    <th>Registro ID</th>
                    <th>Acción</th>
                    <th>Valor Anterior</th>
                    <th>Valor Nuevo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center">No hay entradas</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id']) ?></td>
                            <td><?= htmlspecialchars($r['usuario_nombre'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($r['fechaHora'] ?? $r['fecha_hora'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['tablaAfectada'] ?? $r['tabla_afectada'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['registroId'] ?? $r['registro_id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['accion'] ?? '') ?></td>
                            <td><?= renderMaybeJson($r['valorAnterior'] ?? $r['valor_anterior'] ?? null) ?></td>
                            <td><?= renderMaybeJson($r['valorNuevo'] ?? $r['valor_nuevo'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pagination) && ($pagination['totalPages'] ?? 0) > 1):
        $tp = (int)$pagination['totalPages'];
        $cur = (int)$pagination['current'];
    ?>
    <nav aria-label="Paginación bitácora">
        <ul class="pagination">
            <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= URLROOT ?>/bitacoras?page=<?= max(1, $cur - 1) ?>" aria-label="Anterior">&laquo;</a>
            </li>
            <?php for ($p = 1; $p <= $tp; $p++): ?>
                <li class="page-item <?= $p === $cur ? 'active' : '' ?>"><a class="page-link" href="<?= URLROOT ?>/bitacoras?page=<?= $p ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $cur >= $tp ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= URLROOT ?>/bitacoras?page=<?= min($tp, $cur + 1) ?>" aria-label="Siguiente">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
