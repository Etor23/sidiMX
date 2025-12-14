<?php
require_once APPROOT . '/views/layouts/header.inc.php';

// $data expected to be an array with keys 'usuarios' and 'roles'
$usuarios = $data['usuarios'] ?? [];
$roles = $data['roles'] ?? [];
$pagination = $data['pagination'] ?? null;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="page-box">

        <!-- Encabezado -->
        <h3 class="mb-3" style="border-bottom:2px solid #000;padding-bottom:6px;">
            Gestión de Usuarios y Roles
        </h3>


        <!-- Contenedor de tablas -->
        <div class="row">

            <!-- Tabla Usuarios -->
            <div class="col-md-6">
                <h4 class="text-center mb-3">Usuarios</h4>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr><td colspan="4" class="text-center">No hay usuarios</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['id']) ?></td>
                                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                                        <td><?= htmlspecialchars($u['rolNombre'] ?? 'Sin rol') ?></td>
                                        <td class="text-center">
                                            <a href="<?= URLROOT ?>/usuarios/edit/<?= $u['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="<?= URLROOT ?>/usuarios/destroy/<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este usuario?');" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Botones -->
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <a href="<?= URLROOT ?>/usuarios/create" class="btn btn-primary">Agregar</a>
                </div>

                <!-- Paginación -->
                <?php if (!empty($pagination) && ($pagination['totalPages'] ?? 0) > 1):
                    $cur = (int)$pagination['current'];
                    $tp = (int)$pagination['totalPages'];
                    $total = (int)$pagination['total'];
                ?>
                    <nav aria-label="Paginación usuarios">
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= URLROOT ?>/usuarios?page=<?= max(1, $cur - 1) ?>" aria-label="Anterior">&laquo;</a>
                            </li>
                            <?php for ($p = 1; $p <= $tp; $p++): ?>
                                <li class="page-item <?= $p === $cur ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= URLROOT ?>/usuarios?page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $cur >= $tp ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= URLROOT ?>/usuarios?page=<?= min($tp, $cur + 1) ?>" aria-label="Siguiente">&raquo;</a>
                            </li>
                        </ul>
                    </nav>

                    <div class="text-center text-muted small mt-2">
                        Página <?= $cur ?> de <?= $tp ?> (<?= $total ?> usuarios total)
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabla Roles -->
            <div class="col-md-6">
                <h4 class="text-center mb-3">Roles</h4>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roles)): ?>
                                <tr><td colspan="2" class="text-center">No hay roles</td></tr>
                            <?php else: ?>
                                <?php foreach ($roles as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['id']) ?></td>
                                        <td><?= htmlspecialchars($r['rol']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Botones: rol agregación eliminada por solicitud -->
            </div>

        </div>
    </div>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
