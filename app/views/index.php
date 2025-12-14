<?php require_once APPROOT . '/views/layouts/header.inc.php'; ?>

<div class="container mt-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h1 class="display-4 fw-bold mb-4">SIDI-MX</h1>
            <h2 class="h3 text-muted mb-4">Sistema Integral de Documentación Importación México</h2>
            <p class="lead mb-4">Plataforma especializada en la gestión completa de procesos de importación, logística y documentación aduanal en México.</p>
            <?php if (!estaLogueado()): ?>
                <a href="<?= URLROOT ?>/usuarios/login" class="btn btn-primary btn-lg">Iniciar Sesión</a>
            <?php else: ?>
                <a href="<?= URLROOT ?>/guias" class="btn btn-primary btn-lg">Ir al Sistema</a>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <div class="alert alert-info text-center p-5">
                <i class="fas fa-shipping-fast" style="font-size: 4rem; color: #0d6efd;"></i>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="row mb-5">
        <h2 class="mb-4 text-center">¿Qué hace nuestra empresa?</h2>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 2.5rem; color: #0d6efd; margin-bottom: 1rem;"></i>
                    <h5 class="card-title">Gestión de Importaciones</h5>
                    <p class="card-text">Facilitamos el procesamiento de documentación aduanal y trámites de importación de mercancías.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-boxes" style="font-size: 2.5rem; color: #0d6efd; margin-bottom: 1rem;"></i>
                    <h5 class="card-title">Logística Integral</h5>
                    <p class="card-text">Control completo de envíos, recepción de mercancía, inventario y distribución de productos.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle" style="font-size: 2.5rem; color: #0d6efd; margin-bottom: 1rem;"></i>
                    <h5 class="card-title">Cumplimiento Normativo</h5>
                    <p class="card-text">Garantizamos el cumplimiento de regulaciones aduanales y documentación requerida por autoridades.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Features -->
    <div class="row mb-5">
        <h2 class="mb-4 text-center">Funcionalidades del Sistema</h2>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Gestión de Guías</h5>
                    <p class="card-text">Crear, editar y monitorear guías de envío con todos los detalles de importación.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-pdf"></i> Generación de Documentos</h5>
                    <p class="card-text">Generación automática de PDFs (actas de recepción, pedimentos, comprobantes de pago, etc.)</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calculator"></i> Cálculo de Tarifas</h5>
                    <p class="card-text">Cálculo automático de costos basado en peso, volumen y tarifas vigentes.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-history"></i> Bitácora de Auditoría</h5>
                    <p class="card-text">Registro detallado de todos los cambios realizados en el sistema para trazabilidad.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-shield-alt"></i> Control de Acceso</h5>
                    <p class="card-text">Gestión de usuarios con permisos y roles específicos según responsabilidades.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-triangle"></i> Gestión de Incidencias</h5>
                    <p class="card-text">Registro y seguimiento de problemas, discrepancias y eventos durante el proceso.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="bg-light p-5 rounded-lg text-center">
                <h3 class="mb-3">¿Listo para comenzar?</h3>
                <p class="mb-4 text-muted">Acceda al sistema para gestionar sus importaciones y logística de forma eficiente.</p>
                <?php if (!estaLogueado()): ?>
                    <a href="<?= URLROOT ?>/usuarios/login" class="btn btn-primary btn-lg">Iniciar Sesión</a>
                <?php else: ?>
                    <a href="<?= URLROOT ?>/guias" class="btn btn-primary btn-lg">Continuar al Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Info Footer -->
    <div class="row mt-5 pt-5 border-top">
        <div class="col-md-4 mb-4">
            <h6 class="fw-bold">Sobre SIDI-MX</h6>
            <p class="small text-muted">Solución integral para empresas importadoras que requieren gestionar documentación aduanal y procesos logísticos.</p>
        </div>
        <div class="col-md-4 mb-4">
            <h6 class="fw-bold">Características Principales</h6>
            <ul class="small text-muted" style="list-style: none; padding: 0;">
                <li>✓ Gestión centralizada</li>
                <li>✓ Auditoría completa</li>
                <li>✓ Automatización de procesos</li>
                <li>✓ Reportes detallados</li>
            </ul>
        </div>
        <div class="col-md-4 mb-4">
            <h6 class="fw-bold">Seguridad</h6>
            <p class="small text-muted">Protección de datos, control de acceso granular y registro completo de auditoría para máxima confiabilidad.</p>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
