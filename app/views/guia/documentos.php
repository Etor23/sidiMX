<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$guiaId = $data['guiaId'] ?? null;
$guia = $data['guia'] ?? null;
$estado = $guia['estado'] ?? '';
$tieneIncidencias = $data['tieneIncidencias'] ?? false;
?>

<?php if (!estaLogueado()): ?>
    <div class="alert alert-info">Usted no está autorizado</div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Documentos - Guía #<?= htmlspecialchars($guiaId) ?></h4>
            <small class="text-muted">Estado: <?= htmlspecialchars($estado) ?></small>
        </div>
        <div>
            <a href="<?= URLROOT ?>/guias" class="btn btn-secondary btn-sm">Regresar</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Acta de recepción</th>
                    <th>Acta de incidencia</th>
                    <th>Preliquidación</th>
                    <th>Pedimento</th>
                    <th>Comprobante de pago</th>
                    <th>Comprobante de liberación</th>
                    <th>Comprobante de retiro</th>
                    <th>POD</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- Acta de recepción: conIncidencia, ordenDePago, enPedimento, esperandoPago, pagado, liberado, retirado, entregado -->
                    <td>
                        <?php if (in_array($estado, ['conIncidencia', 'ordenDePago', 'enPedimento', 'esperandoPago', 'pagado', 'liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/actaRecepcion/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Acta de incidencia: ordenDePago, enPedimento, esperandoPago, pagado, liberado, retirado, entregado (solo si tiene incidencias) -->
                    <td>
                        <?php if ($tieneIncidencias && in_array($estado, ['ordenDePago', 'enPedimento', 'esperandoPago', 'pagado', 'liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/actaIncidencia/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Preliquidación: ordenDePago, enPedimento, esperandoPago, pagado, liberado, retirado, entregado -->
                    <td>
                        <?php if (in_array($estado, ['ordenDePago', 'enPedimento', 'esperandoPago', 'pagado', 'liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/preliquidacion/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Pedimento: enPedimento, esperandoPago, pagado, liberado, retirado, entregado -->
                    <td>
                        <?php if (in_array($estado, ['enPedimento', 'esperandoPago', 'pagado', 'liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/pedimento/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Comprobante de pago: pagado, liberado, retirado, entregado (solo activo en pagado o superior) -->
                    <td>
                        <?php if (in_array($estado, ['pagado', 'liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/comprobantePago/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Comprobante de liberación: liberado, retirado, entregado -->
                    <td>
                        <?php if (in_array($estado, ['liberado', 'retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/comprobanteLiberacion/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Comprobante de retiro: retirado, entregado -->
                    <td>
                        <?php if (in_array($estado, ['retirado', 'entregado'])): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/comprobanteRetiro/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- POD: entregado -->
                    <td>
                        <?php if ($estado === 'entregado'): ?>
                            <a href="<?= URLROOT ?>/pdfs/generar/pod/<?= $guiaId ?>" target="_blank">Ver</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php
require_once APPROOT . '/views/layouts/footer.inc.php';
?>
