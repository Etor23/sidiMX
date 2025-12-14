<!DOCTYPE html>
<html lang="en" class="h-100" data-bs-theme="auto">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Astro v5.13.2">
    <title>Principal</title>
    <link href="<?= URLROOT ?>/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <meta name="theme-color" content="#712cf9">
    <link href="<?= URLROOT ?>/assets/css/sticky.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= URLROOT ?>/assets/css/usuarios.css">
    <link href="<?= URLROOT ?>/assets/fontawesome/css/all.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">


    <header> <!-- Fixed navbar -->
        <nav class="navbar navbar-expand-md navbar-secondary bg-secondary fixed-top">
            <div class="container-fluid"> <a class="navbar-brand" href="<?= URLROOT ?>"> <img src="<?= URLROOT ?>/assets/img/logo.png" alt="logo" height="30px"> </a> <button
                    class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
                    aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation"> <span
                        class="navbar-toggler-icon"></span> </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <?php
                        // Obtener información del rol pasada desde el controlador
                        // Si no está disponible (para vistas que no la pasan), calcularla aquí
                        $roleInfo = $roleInfo ?? ['id' => null, 'rol' => '', 'nombre_lower' => ''];
                        
                        if (empty($roleInfo['rol']) && estaLogueado()) {
                            $idRol = $_SESSION['idRoles'] ?? null;
                            if (!empty($idRol)) {
                                require_once APPROOT . 'models/Rol.php';
                                $rolModel = new Rol();
                                $rol = $rolModel->find((int)$idRol);
                                $roleInfo = [
                                    'id' => $rol['id'] ?? null,
                                    'rol' => $rol['rol'] ?? '',
                                    'nombre_lower' => strtolower($rol['rol'] ?? '')
                                ];
                            }
                        }

                        $isSupervisor = ($roleInfo['nombre_lower'] === 'supervisor');
                        $isOperador = ($roleInfo['nombre_lower'] === 'operador');
                        $isRecinto = ($roleInfo['nombre_lower'] === 'recinto');
                    ?>

                    <?php if (estaLogueado()) { ?>
                        <!-- Main navigation (compact) -->
                        <ul class="navbar-nav me-auto mb-2 mb-md-0 align-items-center">
                            <?php if ($isSupervisor) { ?>
                                <li class="nav-item ms-2">
                                    <a class="nav-link d-flex align-items-center px-2" href="<?= URLROOT ?>/usuarios">
                                        <i class="fa fa-users me-1" aria-hidden="true"></i>
                                        <span class="d-none d-md-inline">Usuarios</span>
                                    </a>
                                </li>
                            <?php } ?>

                            <?php if ($isSupervisor || $isOperador || $isRecinto) { ?>
                                <li class="nav-item ms-2">
                                    <a class="nav-link d-flex align-items-center px-2" href="<?= URLROOT ?>/guias">
                                        <i class="fa fa-boxes me-1" aria-hidden="true"></i>
                                        <span class="d-none d-md-inline">Guías</span>
                                    </a>
                                </li>
                            <?php } ?>

                            <?php if ($isSupervisor) { ?>
                                <li class="nav-item ms-2">
                                    <a class="nav-link d-flex align-items-center px-2" href="<?= URLROOT ?>/bitacoras">
                                        <i class="fa fa-book me-1" aria-hidden="true"></i>
                                        <span class="d-none d-md-inline">Bitácora</span>
                                    </a>
                                </li>
                                <li class="nav-item ms-2">
                                    <a class="nav-link d-flex align-items-center px-2" href="<?= URLROOT ?>/bitacorapdfs">
                                        <i class="fa fa-file-pdf me-1" aria-hidden="true"></i>
                                        <span class="d-none d-md-inline">Bitácora PDF</span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>

                    <?php if (!estaLogueado()) { ?>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link active" href="<?= URLROOT ?>/usuarios/login">login</a>
                            </li>
                        </ul>
                    <?php } else { ?>
                        <div class="dropdown">
                            <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?= $_SESSION['usuario_nombre']  ?>
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                <li><a class="dropdown-item" href="<?= URLROOT?>/usuarios/logout">Salir</a></li>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </nav>
    </header> <!-- Begin page content -->
    <main class="flex-shrink-0">
        <div class="container">

            <!-- fin de encabezado (header)-->