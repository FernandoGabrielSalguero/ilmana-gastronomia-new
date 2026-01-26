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
}
