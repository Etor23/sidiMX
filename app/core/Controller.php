<?php
/**
 * Clase base para los controladores
 */

class Controller{

    public function view($view,$data=[]){
        $path=APPROOT.'views/'.$view.'.php';
        if(!file_exists($path)){
            die("Vista {$view} no existe");
        }
        
        // Agregar información del rol a los datos si no está ya presente
        if (!isset($data['roleInfo']) && estaLogueado()) {
            $data['roleInfo'] = $this->getCurrentRoleInfo();
        }
        
        require_once $path;
    }


    /**
     * 
     * 
     * @param string $model 
     * @return object
     */
    public function model($model){
        $path=APPROOT.'models/'.ucwords($model).'.php';
        if(!file_exists($path)){
            die("Modelo {$model} no existe");
        }

        require_once $path;
        $model=ucwords($model);
        return new $model();
    }

    /**
     * Verificar si el usuario tiene permiso según su rol
     * 
     * @param array $allowedRoles Array de nombres de roles permitidos
     * @return bool
     */
    protected function checkPermissionByRole(array $allowedRoles)
    {
        $roleId = $_SESSION['idRoles'] ?? null;
        $roleModel = $this->model('Rol');
        $role = $roleModel->find((int)$roleId);
        $roleName = $role['rol'] ?? '';
        return in_array($roleName, $allowedRoles, true);
    }

    /**
     * Obtener información del rol del usuario actual
     * Útil para pasar a vistas sin que éstas tengan que instanciar modelos
     * 
     * @return array Array con información del rol: ['id' => int, 'rol' => string, 'nombre_lower' => string]
     */
    protected function getCurrentRoleInfo()
    {
        if (!isset($_SESSION['idRoles'])) {
            return ['id' => null, 'rol' => '', 'nombre_lower' => ''];
        }

        $roleModel = $this->model('Rol');
        $role = $roleModel->find((int)$_SESSION['idRoles']);
        
        if (empty($role)) {
            return ['id' => null, 'rol' => '', 'nombre_lower' => ''];
        }

        return [
            'id' => $role['id'] ?? null,
            'rol' => $role['rol'] ?? '',
            'nombre_lower' => strtolower($role['rol'] ?? '')
        ];
    }

    /**
     * Obtener flags booleanos de rol del usuario actual
     * Útil para pasar a vistas para control de permiso
     * 
     * @return array Array con ['isSupervisor' => bool, 'isOperador' => bool, 'isRecinto' => bool]
     */
    protected function getRoleFlags()
    {
        $roleInfo = $this->getCurrentRoleInfo();
        $roleName = $roleInfo['nombre_lower'];

        return [
            'isSupervisor' => ($roleName === 'supervisor'),
            'isOperador' => ($roleName === 'operador'),
            'isRecinto' => ($roleName === 'recinto')
        ];
    }

    /**
     * Obtener instancia del servicio de PDF
     * 
     * @return object PdfService instance
     */
    protected function getPdfService()
    {
        $path = APPROOT . 'services/PdfService.php';
        if (!file_exists($path)) {
            die("Servicio PdfService no existe");
        }
        require_once $path;
        return new PdfService();
    }
}