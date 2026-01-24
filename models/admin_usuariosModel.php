<?php
require_once __DIR__ . '/../config.php';

class AdminUsuariosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerColegios()
    {
        $stmt = $this->db->query("SELECT Id, Nombre FROM Colegios ORDER BY Nombre");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerCursos()
    {
        $stmt = $this->db->query("SELECT Id, Nombre, Colegio_Id FROM Cursos ORDER BY Nombre");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerPreferencias()
    {
        $stmt = $this->db->query("SELECT Id, Nombre FROM Preferencias_Alimenticias ORDER BY Nombre");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function obtenerUsuariosConHijos()
    {
        $stmt = $this->db->query("SELECT Id, Nombre, Usuario, Telefono, Correo, Rol, Saldo FROM Usuarios ORDER BY Nombre");
        $usuarios = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (empty($usuarios)) {
            return [];
        }

        $ids = array_map('intval', array_column($usuarios, 'Id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtHijos = $this->db->prepare("SELECT uh.Usuario_Id, h.Id, h.Nombre, h.Preferencias_Alimenticias, h.Colegio_Id, h.Curso_Id
            FROM Usuarios_Hijos uh
            INNER JOIN Hijos h ON h.Id = uh.Hijo_Id
            WHERE uh.Usuario_Id IN ($placeholders)
            ORDER BY h.Id");
        $stmtHijos->execute($ids);
        $hijosRows = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);

        $hijosPorUsuario = [];
        foreach ($hijosRows as $hijo) {
            $usuarioId = (int) $hijo['Usuario_Id'];
            $hijosPorUsuario[$usuarioId][] = [
                'id' => (int) $hijo['Id'],
                'nombre' => $hijo['Nombre'],
                'preferencias' => $hijo['Preferencias_Alimenticias'],
                'colegio_id' => $hijo['Colegio_Id'],
                'curso_id' => $hijo['Curso_Id']
            ];
        }

        foreach ($usuarios as &$usuario) {
            $usuarioId = (int) $usuario['Id'];
            $usuario['hijos'] = $hijosPorUsuario[$usuarioId] ?? [];
        }
        unset($usuario);

        return $usuarios;
    }

    public function crearUsuarioConHijos(array $data, array $hijos)
    {
        try {
            $stmtCheck = $this->db->prepare("SELECT Id FROM Usuarios WHERE Usuario = :usuario LIMIT 1");
            $stmtCheck->execute(['usuario' => $data['usuario']]);
            if ($stmtCheck->fetchColumn()) {
                return [
                    'ok' => false,
                    'mensaje' => 'El usuario ya existe.'
                ];
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO Usuarios
                (Nombre, Usuario, Contrasena, Telefono, Correo, Pedidos_saldo, Saldo, Pedidos_comida, Rol, Hijos, Estado)
                VALUES
                (:nombre, :usuario, :contrasena, :telefono, :correo, :pedidos_saldo, :saldo, :pedidos_comida, :rol, :hijos, :estado)");

            $stmt->execute([
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'contrasena' => $data['contrasena'],
                'telefono' => $data['telefono'],
                'correo' => $data['correo'],
                'pedidos_saldo' => $data['pedidos_saldo'],
                'saldo' => $data['saldo'],
                'pedidos_comida' => $data['pedidos_comida'],
                'rol' => $data['rol'],
                'hijos' => $data['hijos'],
                'estado' => $data['estado']
            ]);

            $usuarioId = (int) $this->db->lastInsertId();

            if ($data['rol'] === 'papas' && !empty($hijos)) {
                $stmtHijo = $this->db->prepare("INSERT INTO Hijos
                    (Nombre, Preferencias_Alimenticias, Colegio_Id, Curso_Id)
                    VALUES (:nombre, :preferencias, :colegio_id, :curso_id)");
                $stmtLink = $this->db->prepare("INSERT INTO Usuarios_Hijos (Usuario_Id, Hijo_Id)
                    VALUES (:usuario_id, :hijo_id)");

                foreach ($hijos as $hijo) {
                    $stmtHijo->execute([
                        'nombre' => $hijo['nombre'],
                        'preferencias' => $hijo['preferencias_id'],
                        'colegio_id' => $hijo['colegio_id'],
                        'curso_id' => $hijo['curso_id']
                    ]);

                    $hijoId = (int) $this->db->lastInsertId();
                    $stmtLink->execute([
                        'usuario_id' => $usuarioId,
                        'hijo_id' => $hijoId
                    ]);
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'ok' => false,
                'mensaje' => 'No se pudo crear el usuario.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Usuario creado correctamente.'
        ];
    }
}
