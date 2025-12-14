<?php
/**
 * Controlador Usuarios para SIDI-MX
 */
class Usuarios extends Controller
{

    private $modeloUsuario;
    private $modeloRol;

    public function __construct()
    {
        $this->modeloUsuario = $this->model('Usuario');
        $this->modeloRol = $this->model('Rol');
    }

    public function index()
    {
        if (!estaLogueado()) {
            $this->view('usuarios/index', []);
            return;
        }

        // paginación
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // obtener datos paginados
        $usuarios = $this->modeloUsuario->paginated($perPage, $offset);
        $roles = $this->modeloRol->all();

        // mapear roles por id para mostrar texto del rol
        $rolesMap = [];
        foreach ($roles as $r) {
            $rolesMap[$r['id']] = $r['rol'];
        }

        foreach ($usuarios as &$u) {
            $u['rolNombre'] = $rolesMap[$u['idRoles']] ?? 'Sin rol';
        }

        $total = $this->modeloUsuario->countAll();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        // pasar tanto usuarios como roles y datos de paginación a la vista
        $data = [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'pagination' => [
                'current' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
                'offset' => $offset
            ]
        ];

        $this->view('usuarios/index', $data);
    }

    public function create()
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $roles = $this->modeloRol->all();

        $data = [
            'accion' => 'crear',
            'error' => [],
            'roles' => $roles,
            'nombre' => '',
            'idRoles' => ''
        ];

        if ($metodo == 'POST') {
            $data = array_merge($data, $_POST);

            $data['error'] = $this->validarUsuario($data, false);

            if (empty($data['error'])) {
                $nuevo = [
                    'nombre' => $data['nombre'],
                    'idRoles' => $data['idRoles'],
                    'contrasenaHash' => password_hash($data['contrasena'], PASSWORD_DEFAULT)
                ];

                $idCreador = $_SESSION['usuario_id'] ?? null;
                $resultado = $this->modeloUsuario->create($nuevo, $idCreador);


                if ($resultado) {
                    refresh('/usuarios');
                    return;
                } else {
                    $data['error'][] = 'No se pudo crear el usuario';
                }
            }
        }

        $this->view('usuarios/create', $data);
    }

    public function edit($id)
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $roles = $this->modeloRol->all();

        if ($metodo == 'POST') {
            $data = $_POST;
            unset($data['id']);

            $data['accion'] = 'editar';
            $data['roles'] = $roles;
            $data['error'] = $this->validarUsuario($data, true, $id);

            if (empty($data['error'])) {
                $update = [
                    'nombre' => $data['nombre'],
                    'idRoles' => $data['idRoles']
                ];

                if (!empty($data['contrasena'])) {
                    $update['contrasenaHash'] =
                        password_hash($data['contrasena'], PASSWORD_DEFAULT);
                } else {
                    $update['contrasenaHash'] = null;
                }

                $idEditor = $_SESSION['usuario_id'] ?? null;
                $resultado = $this->modeloUsuario->update($id, $update, $idEditor);


                if ($resultado) {
                    refresh('/usuarios');
                    return;
                } else {
                    $data['error'][] = 'No se pudo actualizar el usuario';
                }
            }

        } else {
            $data = $this->modeloUsuario->find($id);
            $data['error'] = [];
            $data['accion'] = 'editar';
            $data['roles'] = $roles;
        }

        $this->view('usuarios/create', $data);
    }

    public function destroy($id)
    {
        $idEliminador = $_SESSION['usuario_id'] ?? null; // quien hace la acción

        $resultado = $this->modeloUsuario->delete($id, $idEliminador);

        if ($resultado) {
            refresh('/usuarios');
        } else {
            // opcional: manejar error
            refresh('/usuarios');
        }
    }


    public function login()
    {
        $data['error'] = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $nombre = trim($_POST['nombre'] ?? '');
            $pass = $_POST['contrasena'] ?? '';

            $logueado = $this->modeloUsuario->login($nombre, $pass);

            if ($logueado) {
                $_SESSION['usuario_id'] = $logueado['id'];
                $_SESSION['usuario_nombre'] = $logueado['nombre'];
                $_SESSION['idRoles'] = $logueado['idRoles'];
                refresh('/');
                return;
            } else {
                $data['error'][] = 'Credenciales no válidas';
            }
        }

        $this->view('auth/login', $data);
    }


    public function logout()
    {
        unset($_SESSION['usuario_id']);
        unset($_SESSION['usuario_nombre']);
        unset($_SESSION['idRoles']);
        session_destroy();
        refresh('/usuarios/login');
    }

    private function validarUsuario($data, $edit = false, $id = null)
    {
        $errores = [];

        if (empty($data['nombre'])) {
            $errores[] = 'El nombre es obligatorio';
        }

        if (empty($data['idRoles'])) {
            $errores[] = 'El rol es obligatorio';
        }

        if (!$edit) {
            if (empty($data['contrasena'])) {
                $errores[] = 'La contraseña es obligatoria';
            }
            if (($data['contrasena'] ?? '') !== ($data['contrasenaConfirm'] ?? '')) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        } else {
            if (
                !empty($data['contrasena']) &&
                ($data['contrasena'] !== ($data['contrasenaConfirm'] ?? ''))
            ) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }

        return $errores;
    }

    /**
     * Validar si un nombre de usuario ya existe
     */
    public function validarNombre()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($input['nombre'] ?? '');
        $idUsuario = (int)($input['id_usuario'] ?? 0); // Para excluir en edición
        
        if (empty($nombre)) {
            echo json_encode(['existe' => false]);
            return;
        }
        
        // Buscar si existe otro usuario con ese nombre (excluyendo el actual si es edición)
        $existe = $this->modeloUsuario->existeNombre($nombre, $idUsuario);
        
        echo json_encode(['existe' => $existe]);
    }
}
