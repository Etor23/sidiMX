<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guias = $data['guias'] ?? [];
$roleName = $data['roleName'] ?? '';
$isOperador = !empty($data['isOperador']);
$isRecinto = !empty($data['isRecinto']);
$isSupervisor = !empty($data['isSupervisor']);
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row mb-3 align-items-center">
        <div class="col-md-8 d-flex gap-2 align-items-center">
            <h3 class="mb-0 me-3">Guias disponibles</h3>
            <a href="<?= URLROOT ?>/guias/reporteDiario" class="btn btn-outline-secondary btn-sm" target="_blank">Reporte diario</a>
            <a href="<?= URLROOT ?>/guias/reporteMensual" class="btn btn-outline-secondary btn-sm" target="_blank">Reporte mensual</a>
            <a href="<?= URLROOT ?>/guias/reporteIncidencias" class="btn btn-outline-secondary btn-sm" target="_blank">Reporte de incidencias</a>
        </div>
        <div class="col-md-4 text-end">
            <?php if (!$isRecinto): ?>
                <a href="<?= URLROOT ?>/guias/create" class="btn btn-primary btn-sm">Agregar Guia</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                    <tr>
                    <th>ID</th>
                    <th>Identificadores</th>
                    <th>Logistica</th>
                    <th>Partes</th>
                    <th>Certificado</th>
                    <th>Comprobante</th>
                    <th>Permisos</th>
                    <th>Bultos</th>
                    <th>Fechas</th>
                    <th>Estado</th>
                    <th>PDF</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($guias)): ?>
                    <tr><td colspan="13" class="text-center">No hay guías</td></tr>
                <?php else: ?>
                    <?php foreach ($guias as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['id']) ?></td>
                            <td><a href="<?= URLROOT ?>/guias/identificadores/<?= $g['idIdentificadores'] ?? null ?>">Ver</a></td>
                            <td><a href="<?= URLROOT ?>/guias/logistica/<?= $g['idLogistica'] ?? null ?>">Ver</a></td>
                            <td><a href="<?= URLROOT ?>/guias/partes/<?= $g['idPartes'] ?? null ?>">Ver</a></td>
                            <td><a href="<?= URLROOT ?>/guias/certificado/<?= $g['idCertificadoOrigen'] ?? null ?>">Ver</a></td>
                                <td>
                                        <?php if (!empty($g['hasComprobante'])): ?>
                                            <a href="<?= URLROOT ?>/guias/comprobante/<?= $g['id'] ?>">Ver</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($g['hasPermisos'])): ?>
                                            <a href="<?= URLROOT ?>/guias/permisos/<?= $g['id'] ?>">Ver</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="<?= URLROOT ?>/guias/bultos/<?= $g['idBultos'] ?? null ?>">Ver</a></td>
                            <td><a href="<?= URLROOT ?>/guias/fechas/<?= $g['idFechas'] ?? null ?>">Ver</a></td>
                            <td><?= htmlspecialchars($g['estado'] ?? '') ?></td>
                            <td><a href="<?= URLROOT ?>/guias/documentos/<?= $g['id'] ?>">PDF</a></td>
                            <td>
                                <?php $estado = $g['estado'] ?? ''; ?>
                                <div class="d-flex align-items-center">
                                    <!-- Primary actions: Edit / Delete (fixed area) -->
                                    <div class="me-2" style="min-width:120px; display:flex; gap:6px;">
                                        <?php if (!$isRecinto && ($g['estado'] !== 'retirado') && ($g['estado'] !== 'entregado')): ?>
                                            <a href="<?= URLROOT ?>/guias/editg/<?= $g['id'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa fa-edit"></i></a>
                                            <a href="<?= URLROOT ?>/guias/destroy/<?= $g['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta guía? Esta acción no se puede deshacer.');" title="Eliminar"><i class="fa fa-trash"></i></a>
                                        <?php else: ?>
                                            <a class="btn btn-warning btn-sm" style="visibility:hidden"><i class="fa fa-edit"></i></a>
                                            <a class="btn btn-danger btn-sm" style="visibility:hidden"><i class="fa fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Secondary actions: role-specific (fixed min width to keep position) -->
                                    <div class="d-flex" style="min-width:200px; gap:6px; flex-wrap:wrap;">
                                        <?php if ($isOperador): ?>
                                            <?php if ($estado === 'preAlerta'): ?>
                                                <a href="<?= URLROOT ?>/guias/enviarARecinto/<?= $g['id'] ?>" class="btn btn-sm btn-primary">Enviar a recinto</a>
                                            <?php endif; ?>
                                            <?php if ($estado === 'conIncidencia'): ?>
                                                <a href="<?= URLROOT ?>/incidencias/create?guia=<?= $g['id'] ?>" class="btn btn-sm btn-danger">Crear incidencia</a>
                                            <?php endif; ?>
                                            <?php if ($estado === 'ordenDePago'): ?>
                                                <a href="<?= URLROOT ?>/guias/elaborarPedimento/<?= $g['id'] ?>" class="btn btn-sm btn-info">Elaborar pedimento</a>
                                            <?php endif; ?>
                                            <?php if ($estado === 'esperandoPago'): ?>
                                                <a href="<?= URLROOT ?>/guias/marcarPagado/<?= $g['id'] ?>" class="btn btn-sm btn-success">Ya pagado</a>
                                            <?php endif; ?>
                                            <?php if ($estado === 'retirado'): ?>
                                                <a href="<?= URLROOT ?>/pods/registrar/<?= $g['id'] ?>" class="btn btn-sm btn-success">Registrar POD</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- placeholder to keep spacing -->
                                            <span style="display:inline-block; width:1px; visibility:hidden"></span>
                                        <?php endif; ?>

                                        <?php if ($isRecinto): ?>
                                            <?php if ($estado === 'enRecinto'): ?>
                                                <a href="<?= URLROOT ?>/guias/recepcion/<?= $g['id'] ?>" class="btn btn-sm btn-primary">Pasar a recinto</a>
                                            <?php endif; ?>
                                            <?php if ($estado === 'liberado'): ?>
                                                <a href="<?= URLROOT ?>/retiros/crear/<?= $g['id'] ?>" class="btn btn-sm btn-success">Registrar retiro</a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($isSupervisor): ?>
                                            <?php if ($estado === 'pagado'): ?>
                                                <a href="<?= URLROOT ?>/liberaciones/crear/<?= $g['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Ya revisó que los documentos sean correctos?')">Autorizar liberación</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                        <a class="page-link" href="<?= $current > 1 ? URLROOT . '/guias?page=' . ($current - 1) : '#' ?>">Anterior</a>
                    </li>

                    <!-- Page numbers -->
                    <?php 
                        $start = max(1, $current - 2);
                        $end = min($totalPages, $current + 2);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . URLROOT . '/guias?page=1">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $current ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . URLROOT . '/guias?page=' . $i . '">' . $i . '</a></li>';
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . URLROOT . '/guias?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                        }
                    ?>

                    <!-- Next -->
                    <li class="page-item <?= $current === $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $current < $totalPages ? URLROOT . '/guias?page=' . ($current + 1) : '#' ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>

            <div class="text-center text-muted small mt-2">
                Página <?= $current ?> de <?= $totalPages ?> (<?= $total ?> guías total)
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>