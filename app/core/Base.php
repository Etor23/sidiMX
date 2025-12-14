<?php
/**
 * 
 * Clase para conectar a BD y adicional ORM
 */


class Base
{
    //Inicialización de parametris
    private $dbh; //Handler de BD
    private $stmt; //Manejo de Sentencia 
    private $table; //Tabla a utilizar
    private $wheres = [];
    private $bindings = [];
    private $limit;
    private $offset;

    private $user = DBUSER;
    private $pwd = DBPWD;
    private $driver = DBDRIVER;
    private $host = DBHOST;
    private $db = DBNAME;
    private $charset = 'utf8mb4';


    public function __construct($table)
    {
        $this->table = $table;
        $options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  //Que regrese un arreglo asociativo
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //Que maneje errores con excepciones
            PDO::ATTR_EMULATE_PREPARES => false //Que no se puedan hacer inyecciones SQL
        ];
        try {
            $dsn = "{$this->driver}:host={$this->host};dbname={$this->db};charset={$this->charset}";
            $this->dbh = new PDO($dsn, $this->user, $this->pwd, $options);

        } catch (PDOException $e) {
            echo 'Error en conexion a Base de Datos ' . $e->getMessage();
        }
    } //Fin de construct

    public function query($sql)
    {
        $this->stmt = $this->dbh->prepare($sql);
    }

    public function bind($parametro, $valor, $tipo = null)
    {
        switch (is_null($tipo)) {
            case is_int($valor):
                $tipo = PDO::PARAM_INT;
                break;
            case is_bool($valor):
                $tipo = PDO::PARAM_BOOL;
                break;
            case is_null($valor):
                $tipo = PDO::PARAM_NULL;
                break;
            default:
                $tipo = PDO::PARAM_STR;
                break;
        }
        $this->stmt->bindValue($parametro, $valor, $tipo);

    }

    public function execute()
    {
        return $this->stmt->execute();
    }


    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $sql .= $this->addWheres();
        if ($this->limit) {
            $sql .= " LIMIT " . $this->limit;
        }
        if (is_numeric($this->offset)) {
            $sql .= " OFFSET " . (int) $this->offset;
        }
        $this->query($sql);
        //Vincular con bind
        foreach ($this->bindings as $param => $value) {
            $this->bind($param, $value);
        }
        $this->execute();
        $resultado = $this->stmt->fetchAll();
        $this->limpiar();
        return $resultado;
    }

    public function where($column, $value, $operator = '=')
    {
        $this->wheres[] = ['type' => 'AND', 'column' => $column, 'value' => $value, 'operator' => $operator];
        return $this;

    }

    public function addWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $this->bindings = [];
        $clauses = [];
        foreach ($this->wheres as $index => $where) {
            $param = ":where_{$index}";
            switch ($where['operator']) {
                case 'IS NULL':
                case 'IS NOT NULL':
                    $clause = "{$where['column']} {$where['operator']}";
                    break;
                default:
                    $clause = "{$where['column']} {$where['operator']} {$param}";
                    $this->bindings[$param] = $where['value'];
            }
            if ($index == 0) {
                $clauses[] = $clause;
            } else {
                $clauses[] = " {$where['type']} {$clause} ";
            }
        }

        return " WHERE " . implode(' ', $clauses);
    }

    public function find($id)
    {
        return $this->where('id', $id)->first();
    }

    public function first()
    {
        $this->limit(1);
        $result = $this->get();

        return $result[0] ?? null;
    }

    public function limit($limit)
    {
        //Agregar a la consulta el limite
        $this->limit = $limit;
        return $this; //Uso de fluent
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Devuelve el conteo total de registros para la consulta actual 
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        $sql .= $this->addWheres();
        $this->query($sql);

        foreach ($this->bindings as $param => $value) {
            $this->bind($param, $value);
        }

        $this->execute();
        $row = $this->stmt->fetch();
        $this->limpiar();
        return (int) ($row['total'] ?? 0);
    }



    public function update($data)
    {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = :$key";
        }
        $sets = implode(', ', $sets);

        $sql = "UPDATE {$this->table} SET $sets " . $this->addWheres();
        // d($sql)
        $this->query($sql);

        // 1. Aplicar binds para el WHERE 
        foreach ($this->bindings as $param => $value) {
            $this->bind($param, $value);
        }

        // 2. Aplicar binds para el SET 
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }

        $this->execute();
        $afectados = $this->afectados();
        #limpiar variable
        $this->limpiar();
        return $afectados;
    } //fin de update


    public function afectados()
    {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    public function limpiar()
    {
        $this->wheres = [];
        $this->bindings = [];
        $this->limit = null;
        $this->offset = null;
        $this->stmt = null;
    }

    public function create($data)
    {

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        // d($sql)
        $this->query($sql);
        // 2. Aplicar binds para el SET 
        foreach ($data as $key => $value) {

            $this->bind(":{$key}", $value);
        }

        $this->execute();
        $lastId = $this->dbh->lastInsertId();
        #limpiar variable
        $this->limpiar();
        return $lastId;
    } //fin de  create


    /**
     * M etodo delete
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->table} " . $this->addWheres();
        // d($sql)
        $this->query($sql);

        // 1. Aplicar binds para el WHERE (esto está bien)
        foreach ($this->bindings as $param => $value) {
            $this->bind($param, $value);
        }

        $this->execute();
        $afectados = $this->afectados();
        #limpiar variable
        $this->limpiar();
        return $afectados;
    }


    public function raw($sql, $params = [], $fetchMode = 'all')
    {
        $this->query($sql);

        foreach ($params as $param => $value) {
            $this->bind($param, $value);
        }

        $this->execute();

        if ($fetchMode === 'none') {
            $this->limpiar();
            return true;
        }

        if ($fetchMode === 'one') {
            $resultado = $this->stmt->fetch();
            $this->limpiar();
            return $resultado ?: null;
        }

        $resultado = $this->stmt->fetchAll();
        $this->limpiar();
        return $resultado;
    }

} //Fin de la clase