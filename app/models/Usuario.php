<?php
/**
 * Modelo Usuario para SIDI-MX
 * Campos reales: id, nombre, idRoles, contrasenaHash
 */
class Usuario
{
    private $db;
    private $table = 'usuarios';
    public function __construct()
    {
        $this->db = new Base($this->table);
    }

    public function all()
    {
        return $this->db->get();
    }

    /**
     * Obtener lista paginada
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function paginated($limit, $offset)
    {
        return $this->db->limit((int)$limit)->offset((int)$offset)->get();
    }

    /**
     * Contar todos los registros (aplica filtros si se usan métodos where antes)
     */
    public function countAll()
    {
        return $this->db->count();
    }

    public function find($id)
    {
        return $this->db->find($id);
    }

    public function create($data, $idCreador = null)
    {
        // 1) Llamar al SP
        $this->db->raw(
            "CALL crearUsuario(:nombre, :idRoles, :hash, :idCreador, @nuevoId)",
            [
                ':nombre' => $data['nombre'],
                ':idRoles' => (int) $data['idRoles'],
                ':hash' => $data['contrasenaHash'],
                ':idCreador' => $idCreador
            ],
            'none'
        );

        // 2) Leer el OUT usando la misma conexión
        $row = $this->db->raw("SELECT @nuevoId AS id", [], 'one');

        return $row['id'] ?? false;
    }

    public function update($id, $data, $idEditor = null)
    {
        // pContrasenaHash es opcional en el SP
        $hash = $data['contrasenaHash'] ?? null;

        // 1) Llamar al SP
        $this->db->raw(
            "CALL editarUsuario(
            :idUsuario,
            :nombre,
            :idRoles,
            :hash,
            :idEditor,
            @afectados
        )",
            [
                ':idUsuario' => (int) $id,
                ':nombre' => $data['nombre'],
                ':idRoles' => (int) $data['idRoles'],
                ':hash' => $hash,        // null = no cambia contraseña
                ':idEditor' => $idEditor     // para Bitacora
            ],
            'none'
        );

        // 2) Leer OUT
        $row = $this->db->raw("SELECT @afectados AS afectados", [], 'one');

        return (int) ($row['afectados'] ?? 0);
    }

    public function delete($id, $idEliminador = null)
    {
        // 1) Llamar al SP
        $this->db->raw(
            "CALL eliminarUsuario(
            :idUsuario,
            :idEliminador,
            @afectados
        )",
            [
                ':idUsuario' => (int) $id,
                ':idEliminador' => $idEliminador
            ],
            'none'
        );

        // 2) Leer el OUT
        $row = $this->db->raw("SELECT @afectados AS afectados", [], 'one');

        return (int) ($row['afectados'] ?? 0);
    }

    public function login($nombre, $password)
    {
        $usuario = $this->db->where('nombre', $nombre)->first();

        if ($usuario && password_verify($password, $usuario['contrasenaHash'])) {
            return $usuario;
        }
        return false;
    }

    /**
     * Validar datos de usuario para creación/actualización
     * @param array $data Datos a validar
     * @param int|null $idExcluir ID de usuario a excluir de validación de unicidad (para UPDATE)
     * @return array Array de errores (vacío si no hay errores)
     */
    public function validate($data, $idExcluir = null)
    {
        $errors = [];

        // Validar nombre es requerido
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'Nombre de usuario es requerido';
        } elseif (strlen($data['nombre']) < 3) {
            $errors['nombre'] = 'Nombre debe tener al menos 3 caracteres';
        } elseif (strlen($data['nombre']) > 60) {
            $errors['nombre'] = 'Nombre no puede exceder 60 caracteres';
        } else {
            // Validar unicidad del nombre (excepto si es el mismo usuario en UPDATE)
            $existente = $this->db->where('nombre', $data['nombre'])->first();
            if ($existente && (!$idExcluir || $existente['id'] != $idExcluir)) {
                $errors['nombre'] = 'Este nombre de usuario ya existe';
            }
        }

        // Validar rol es requerido
        if (empty($data['idRoles'])) {
            $errors['idRoles'] = 'Rol es requerido';
        } elseif (!is_numeric($data['idRoles']) || (int)$data['idRoles'] <= 0) {
            $errors['idRoles'] = 'Rol inválido';
        }

        // Validar contraseña solo si se proporciona (nuevo usuario o cambio de contraseña)
        if (isset($data['contrasena']) && $data['contrasena'] !== '') {
            if (strlen($data['contrasena']) < 6) {
                $errors['contrasena'] = 'Contraseña debe tener al menos 6 caracteres';
            } elseif (strlen($data['contrasena']) > 255) {
                $errors['contrasena'] = 'Contraseña no puede exceder 255 caracteres';
            }
        } elseif (!$idExcluir) {
            // En creación de nuevo usuario, contraseña es requerida
            $errors['contrasena'] = 'Contraseña es requerida';
        }

        return $errors;
    }

    /**
     * Verificar si un nombre de usuario ya existe
     * @param string $nombre
     * @param int $excludeId ID del usuario actual (para edición)
     * @return bool
     */
    public function existeNombre($nombre, $excludeId = 0)
    {
        $dbUsuarios = new Base('usuarios');
        
        if ($excludeId > 0) {
            // En edición: buscar nombres iguales excluyendo el usuario actual
            $result = $dbUsuarios
                ->where('nombre', $nombre)
                ->where('id', '!=', $excludeId)
                ->count();
        } else {
            // En creación: buscar cualquier coincidencia
            $result = $dbUsuarios
                ->where('nombre', $nombre)
                ->count();
        }
        
        return $result > 0;
    }

}