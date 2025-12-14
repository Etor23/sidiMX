<?php
require_once APPROOT . '/views/layouts/header.inc.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h5 class="mb-4">
                        <?= (($data['accion'] ?? '')=='editar') ? 'Editar' : 'Agregar' ?> Usuario
                    </h5>

                    <div class="alert alert-warning <?= (!empty($data['error']) ? 'd-block' : 'd-none'); ?>">
                        <?= (!empty($data['error']) ? implode(', ', $data['error']) : ''); ?>
                    </div>

                    <form action="" method="POST" id="formUsuario" novalidate data-is-edit="<?= (($data['accion'] ?? '')=='editar') ? '1' : '0' ?>">
                        <?php if (($data['accion'] ?? '')=='editar'): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($data['id'] ?? '') ?>">
                        <?php endif; ?>

                        <div class="mb-3" id="grupo_nombre">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input
                                type="text"
                                class="form-control"
                                name="nombre"
                                id="nombre"
                                pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}"
                                minlength="3"
                                maxlength="40"
                                placeholder="Mínimo 3 caracteres, solo letras"
                                value="<?= htmlspecialchars($data['nombre'] ?? '') ?>"
                                required
                            >
                            <div class="invalid-feedback" id="feedback_nombre">El nombre debe contener solo letras y tener entre 3 y 40 caracteres.</div>
                        </div>

                        <div class="mb-3" id="grupo_idRoles">
                            <label for="idRoles" class="form-label">Rol</label>
                            <select class="form-select" name="idRoles" id="idRoles" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach (($data['roles'] ?? []) as $rol): ?>
                                    <option value="<?= $rol['id'] ?>"
                                        <?= ((string)($data['idRoles'] ?? '') === (string)$rol['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rol['rol']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Debe seleccionar un rol.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6" id="grupo_contrasena">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    name="contrasena"
                                    id="contrasena"
                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                                    minlength="8"
                                    placeholder="Min. 8 caracteres: mayúscula, minúscula, número"
                                    <?= (($data['accion'] ?? '')=='editar') ? '' : 'required' ?>
                                >
                                <div class="invalid-feedback">La contraseña debe tener mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número.</div>
                            </div>

                            <div class="col-12 col-md-6" id="grupo_contrasenaConfirm">
                                <label for="contrasenaConfirm" class="form-label">Confirmar contraseña</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    name="contrasenaConfirm"
                                    id="contrasenaConfirm"
                                    placeholder="Repite tu contraseña"
                                    <?= (($data['accion'] ?? '')=='editar') ? '' : 'required' ?>
                                >
                                <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                            </div>

                            <?php if (($data['accion'] ?? '')=='editar'): ?>
                                <small class="text-muted">
                                    Deja la contraseña vacía si no deseas cambiarla.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <?= (($data['accion'] ?? '')=='editar') ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <a href="<?= URLROOT ?>/usuarios" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= URLROOT ?>/assets/js/validacion-usuarios.js"></script>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
