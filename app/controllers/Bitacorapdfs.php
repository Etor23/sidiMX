<?php

class Bitacorapdfs extends Controller
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = $this->model('BitacoraPDF');
    }

    /**
     * Muestra el listado paginado de bitácoras PDF
     * Solo accesible para usuarios autenticados con rol de supervisor
     * Obtiene todos los registros con información asociada de usuario y guía
     * 
     * @return void Renderiza la vista bitacorapdf/index con registros paginados
     */
    public function index()
    {
        if (!estaLogueado()) {
            refresh('/usuarios/login');
            return;
        }

        // Verificar que sea supervisor
        $roleId = $_SESSION['idRoles'] ?? null;
        $roleModel = $this->model('Rol');
        $role = $roleModel->find((int)$roleId);
        $roleName = strtolower($role['rol'] ?? '');

        if ($roleName !== 'supervisor') {
            refresh('/guias');
            return;
        }

        // Paginación
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Obtener todos los registros con información de usuario y guía
        $allRegistros = $this->modelo->getAllWithDetails();

        // Aplicar paginación
        $total = count($allRegistros);
        $registros = array_slice($allRegistros, $offset, $perPage);
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $data = [
            'registros' => $registros,
            'pagination' => [
                'current' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'offset' => $offset
            ]
        ];

        $this->view('bitacorapdf/index', $data);
    }
}
