<?php require_once APPROOT . '/views/layouts/header.inc.php'; ?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <h3>Bitácora de PDFs Generados</h3>
            <p class="text-muted">Historial de todos los documentos PDF generados en el sistema</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Tipo de PDF</th>
                    <th>Guía</th>
                    <th>Usuario</th>
                    <th>Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['registros'])): ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay registros en la bitácora de PDFs</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['registros'] as $reg): ?>
                        <tr>
                            <td><?= htmlspecialchars($reg['id']) ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($reg['tipoPDF'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= URLROOT ?>/guias/documentos/<?= $reg['idGuia'] ?>">
                                    <?= htmlspecialchars($reg['idGuia'] ?? 'N/A') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($reg['nombreUsuario'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                    $fechaHora = !empty($reg['fechaHora']) ? date('d/m/Y H:i:s', strtotime($reg['fechaHora'])) : 'N/A';
                                    echo htmlspecialchars($fechaHora);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if (!empty($data['pagination'])): ?>
        <?php 
            $pag = $data['pagination'];
            $current = $pag['current'];
            $totalPages = $pag['totalPages'];
            $total = $pag['total'];
        ?>
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous -->
                    <li class="page-item <?= $current === 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $current > 1 ? URLROOT . '/bitacorapdfs?page=' . ($current - 1) : '#' ?>">Anterior</a>
                    </li>

                    <!-- Page numbers -->
                    <?php 
                        $start = max(1, $current - 2);
                        $end = min($totalPages, $current + 2);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . URLROOT . '/bitacorapdfs?page=1">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $current ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . URLROOT . '/bitacorapdfs?page=' . $i . '">' . $i . '</a></li>';
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . URLROOT . '/bitacorapdfs?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                        }
                    ?>

                    <!-- Next -->
                    <li class="page-item <?= $current === $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $current < $totalPages ? URLROOT . '/bitacorapdfs?page=' . ($current + 1) : '#' ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>

            <div class="text-center text-muted small mt-2">
                Página <?= $current ?> de <?= $totalPages ?> (<?= $total ?> registros total)
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
