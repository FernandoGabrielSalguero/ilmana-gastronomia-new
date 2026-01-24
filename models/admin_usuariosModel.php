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
        $stmt = $this->db->query("SELECT Id, Nombre, Usuario, Telefono, Correo, Rol, Saldo, Estado FROM Usuarios ORDER BY Nombre");
        $usuarios = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return $this->attachHijos($usuarios);
    }

    public function buscarUsuariosConHijos($termino)
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $this->obtenerUsuariosConHijos();
        }

        $stmt = $this->db->prepare("SELECT Id, Nombre, Usuario, Telefono, Correo, Rol, Saldo, Estado
            FROM Usuarios
            WHERE Nombre LIKE :termino
            ORDER BY Nombre");
        $stmt->execute([
            'termino' => '%' . $termino . '%'
        ]);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->attachHijos($usuarios);
    }

    public function obtenerUsuarioConHijos($usuarioId)
    {
        $stmt = $this->db->prepare("SELECT Id, Nombre, Usuario, Telefono, Correo, Rol, Saldo, Estado
            FROM Usuarios
            WHERE Id = :id
            LIMIT 1");
        $stmt->execute(['id' => $usuarioId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            return null;
        }
        $usuarios = $this->attachHijos([$usuario]);
        return $usuarios[0] ?? null;
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
            'mensaje' => 'Usuario creado correctamente.',
            'usuario_id' => $usuarioId ?? null
        ];
    }

    public function actualizarEstadoUsuario($usuarioId, $estado)
    {
        $stmt = $this->db->prepare("UPDATE Usuarios SET Estado = :estado WHERE Id = :id");
        $stmt->execute([
            'estado' => $estado,
            'id' => $usuarioId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function usuarioExiste($usuario, $excluirId = null)
    {
        $sql = "SELECT Id FROM Usuarios WHERE Usuario = :usuario";
        $params = ['usuario' => $usuario];
        if ($excluirId) {
            $sql .= " AND Id <> :excluirId";
            $params['excluirId'] = $excluirId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function actualizarUsuarioConHijos($usuarioId, array $data, array $hijos, $contrasenaHash = null)
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE Usuarios
                SET Nombre = :nombre,
                    Usuario = :usuario,
                    Telefono = :telefono,
                    Correo = :correo,
                    Saldo = :saldo,
                    Rol = :rol";
            $params = [
                'nombre' => $data['nombre'],
                'usuario' => $data['usuario'],
                'telefono' => $data['telefono'],
                'correo' => $data['correo'],
                'saldo' => $data['saldo'],
                'rol' => $data['rol'],
                'id' => $usuarioId
            ];

            if ($contrasenaHash) {
                $sql .= ", Contrasena = :contrasena";
                $params['contrasena'] = $contrasenaHash;
            }

            $sql .= " WHERE Id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $stmtHijos = $this->db->prepare("SELECT Hijo_Id FROM Usuarios_Hijos WHERE Usuario_Id = :usuarioId");
            $stmtHijos->execute(['usuarioId' => $usuarioId]);
            $hijosIds = $stmtHijos->fetchAll(PDO::FETCH_COLUMN);

            $stmtDeleteLinks = $this->db->prepare("DELETE FROM Usuarios_Hijos WHERE Usuario_Id = :usuarioId");
            $stmtDeleteLinks->execute(['usuarioId' => $usuarioId]);

            if (!empty($hijosIds)) {
                $placeholders = implode(',', array_fill(0, count($hijosIds), '?'));
                $stmtDeleteHijos = $this->db->prepare("DELETE FROM Hijos WHERE Id IN ($placeholders)");
                $stmtDeleteHijos->execute(array_map('intval', $hijosIds));
            }

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
                'mensaje' => 'No se pudo actualizar el usuario.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Usuario actualizado correctamente.'
        ];
    }

    private function attachHijos(array $usuarios)
    {
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
}
