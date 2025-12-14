<?php
/**
 * Controlador Bitacoras
 */
class Bitacoras extends Controller
{
    public function __construct()
    {
        // nada por ahora
    }

    /**
     * Muestra el listado paginado de registros de bitácora
     * Solo accesible para usuarios autenticados
     * Obtiene todos los registros de la bitácora con paginación de 10 por página
     * 
     * @return void Renderiza la vista bitacora/index con registros paginados
     */
    public function index()
    {
        if (!estaLogueado()) { refresh('/login'); return; }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $model = $this->model('Bitacora');
        $total = $model->count();
        $rows = $model->paginated($perPage, $offset);

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $pagination = [
            'current' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'offset' => $offset
        ];

        $this->view('bitacora/index', ['rows' => $rows, 'pagination' => $pagination]);
    }
}
