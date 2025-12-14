<?php require_once APPROOT . '/views/layouts/header.inc.php'; ?>

<div class="d-flex align-items-center justify-content-center" style="min-height:60vh;">
    <div class="card shadow-sm w-100" style="max-width:420px;">
        <div class="card-body">
            <div class="text-center mb-3">
                <img src="<?= URLROOT ?>/assets/img/logo.png" alt="logo" height="40" class="mb-2">
                <h4 class="mb-0">Acceso al sistema</h4>
                <small class="text-muted">Ingrese sus credenciales</small>
            </div>

            <?php if (!empty($data['error'])): ?>
                <div class="alert alert-warning"><?= htmlspecialchars(implode(', ', $data['error'])) ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="formLogin" novalidate>
                <div class="mb-3" id="grupo_nombre">
                    <label for="nombre" class="form-label">Usuario</label>
                    <input 
                        type="text" 
                        name="nombre" 
                        id="nombre" 
                        class="form-control"
                        pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}"
                        minlength="3"
                        maxlength="40"
                        placeholder="Ingrese su nombre de usuario"
                        required 
                        autofocus
                    >
                    <div class="invalid-feedback">El nombre debe contener solo letras y tener entre 3 y 40 caracteres.</div>
                </div>

                <div class="mb-3" id="grupo_contrasena">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input 
                        type="password" 
                        name="contrasena" 
                        id="contrasena" 
                        class="form-control"
                        pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$"
                        minlength="8"
                        placeholder="Mínimo 8 caracteres"
                        required
                    >
                    <div class="invalid-feedback">La contraseña debe tener mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center text-muted small">
            <a href="<?= URLROOT ?>" class="text-decoration-none">Volver al inicio</a>
        </div>
    </div>
</div>

<script src="<?= URLROOT ?>/assets/js/validacion-login.js"></script>

