<?php
require_once __DIR__ . '/../config.php';

class AdminLogsModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerUsuariosConCorreosLog()
    {
        $stmt = $this->db->query("SELECT DISTINCT u.Id, u.Nombre, u.Correo
            FROM Usuarios u
            INNER JOIN Correos_Log cl ON cl.Usuario_Id = u.Id
            ORDER BY u.Nombre");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerCorreosLog($usuarioId = null, $limit = 200)
    {
        $params = [];
        $where = [];

        if ($usuarioId) {
            $where[] = "cl.Usuario_Id = :usuarioId";
            $params['usuarioId'] = $usuarioId;
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 200;
        }

        $sql = "SELECT cl.Id, cl.Usuario_Id, cl.Correo, cl.Nombre, cl.Asunto, cl.Template,
                    cl.Mensaje_HTML, cl.Mensaje_Text, cl.Estado, cl.Error, cl.Creado_En,
                    u.Nombre AS UsuarioNombre, u.Correo AS UsuarioCorreo, u.Usuario AS UsuarioLogin
                FROM Correos_Log cl
                LEFT JOIN Usuarios u ON u.Id = cl.Usuario_Id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY cl.Id DESC LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarCorreosLog($termino, $limit = 200)
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $this->obtenerCorreosLog(null, $limit);
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 200;
        }

        $like = '%' . $termino . '%';
        $sql = "SELECT cl.Id, cl.Usuario_Id, cl.Correo, cl.Nombre, cl.Asunto, cl.Template,
                    cl.Mensaje_HTML, cl.Mensaje_Text, cl.Estado, cl.Error, cl.Creado_En,
                    u.Nombre AS UsuarioNombre, u.Correo AS UsuarioCorreo, u.Usuario AS UsuarioLogin
                FROM Correos_Log cl
                LEFT JOIN Usuarios u ON u.Id = cl.Usuario_Id
                WHERE u.Nombre LIKE :termino
                   OR u.Correo LIKE :termino
                   OR u.Usuario LIKE :termino
                   OR cl.Correo LIKE :termino
                   OR cl.Nombre LIKE :termino
                ORDER BY cl.Id DESC
                LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['termino' => $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAuditoriaEventos($limit = 200, $desde = null, $hasta = null)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 200;
        }

        $where = [];
        $params = [];
        if (!empty($desde)) {
            $where[] = "ae.Creado_En >= :desde";
            $params['desde'] = $desde . ' 00:00:00';
        }
        if (!empty($hasta)) {
            $where[] = "ae.Creado_En <= :hasta";
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $sql = "SELECT ae.Id, ae.Usuario_Id, ae.Usuario_Login, ae.Rol, ae.Evento, ae.Modulo,
                    ae.Entidad, ae.Entidad_Id, ae.Estado, ae.Ip, ae.Datos, ae.Creado_En,
                    u.Nombre AS UsuarioNombre, u.Correo AS UsuarioCorreo, u.Usuario AS UsuarioLogin
                FROM Auditoria_Eventos ae
                LEFT JOIN Usuarios u ON u.Id = ae.Usuario_Id";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ae.Id DESC LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarAuditoriaEventos($termino, $limit = 200, $desde = null, $hasta = null)
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $this->obtenerAuditoriaEventos($limit, $desde, $hasta);
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 200;
        }

        $like = '%' . $termino . '%';
        $where = [
            "(
                u.Nombre LIKE :termino
                OR u.Correo LIKE :termino
                OR u.Usuario LIKE :termino
                OR ae.Usuario_Login LIKE :termino
            )"
        ];
        $params = ['termino' => $like];

        if (!empty($desde)) {
            $where[] = "ae.Creado_En >= :desde";
            $params['desde'] = $desde . ' 00:00:00';
        }
        if (!empty($hasta)) {
            $where[] = "ae.Creado_En <= :hasta";
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $sql = "SELECT ae.Id, ae.Usuario_Id, ae.Usuario_Login, ae.Rol, ae.Evento, ae.Modulo,
                    ae.Entidad, ae.Entidad_Id, ae.Estado, ae.Ip, ae.Datos, ae.Creado_En,
                    u.Nombre AS UsuarioNombre, u.Correo AS UsuarioCorreo, u.Usuario AS UsuarioLogin
                FROM Auditoria_Eventos ae
                LEFT JOIN Usuarios u ON u.Id = ae.Usuario_Id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ae.Id DESC
                LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
