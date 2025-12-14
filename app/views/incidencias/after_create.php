<?php
require_once APPROOT . '/views/layouts/header.inc.php';

$idGuia = $data['idGuia'] ?? null;
$idInc = $data['idIncidencia'] ?? null;
?>

<div class="container mt-4">
    <div class="alert alert-success">Incidencia registrada (ID: <?= htmlspecialchars($idInc) ?>).</div>

    <p>¿Desea registrar otra incidencia para la guía <strong>#<?= htmlspecialchars($idGuia) ?></strong>?</p>

    <div class="d-flex gap-2">
        <a id="btn-otra" class="btn btn-primary" href="<?= URLROOT ?>/incidencias/create?guia=<?= htmlspecialchars($idGuia) ?>">Sí, registrar otra</a>
        <button id="btn-no" class="btn btn-secondary" data-guia-id="<?= htmlspecialchars($idGuia) ?>" data-url-root="<?= URLROOT ?>">No, terminar</button>
    </div>
</div>

<script src="<?= URLROOT ?>/assets/js/incidencias-after-create.js"></script>

<?php require_once APPROOT . '/views/layouts/footer.inc.php'; ?>
