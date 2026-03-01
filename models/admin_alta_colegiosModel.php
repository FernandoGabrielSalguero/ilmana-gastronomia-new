<?php
require_once __DIR__ . '/../config.php';

class AdminAltaColegiosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerColegios()
    {
        $sql = "SELECT Id, Nombre
                FROM Colegios
                ORDER BY Nombre";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerColegiosConRepresentante()
    {
        $sql = "SELECT c.Id,
                       c.Nombre,
                       c.`Dirección` AS Direccion,
                       GROUP_CONCAT(DISTINCT u.Nombre ORDER BY u.Nombre SEPARATOR ', ') AS Representantes_Nombres
                FROM Colegios c
                LEFT JOIN Representantes_Colegios rc ON rc.Colegio_Id = c.Id
                LEFT JOIN Usuarios u ON u.Id = rc.Representante_Id
                GROUP BY c.Id, c.Nombre, c.`Dirección`
                ORDER BY c.Nombre";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerCursos()
    {
        $sql = "SELECT cu.Id,
                       cu.Nombre,
                       cu.Nivel_Educativo,
                       cu.Colegio_Id,
                       c.Nombre AS Colegio_Nombre
                FROM Cursos cu
                LEFT JOIN Colegios c ON c.Id = cu.Colegio_Id
                ORDER BY c.Nombre, cu.Nombre";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function obtenerRepresentantes()
    {
        $sql = "SELECT Id, Nombre, Usuario, Correo
                FROM Usuarios
                WHERE Rol = 'representante'
                ORDER BY Nombre";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function crearColegio(array $data)
    {
        $sql = "INSERT INTO Colegios (Nombre, `Dirección`)
                VALUES (:nombre, :direccion)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'nombre' => $data['nombre'],
                'direccion' => $data['direccion']
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo crear el colegio.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Colegio creado correctamente.'
        ];
    }

    public function crearCurso(array $data)
    {
        $sql = "INSERT INTO Cursos (Nombre, Colegio_Id, Nivel_Educativo)
                VALUES (:nombre, :colegio_id, :nivel)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'nombre' => $data['nombre'],
                'colegio_id' => $data['colegio_id'],
                'nivel' => $data['nivel_educativo']
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo crear el curso.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Curso creado correctamente.'
        ];
    }

    public function asignarRepresentante(int $colegioId, int $representanteId)
    {
        $sqlFind = "SELECT Id
                    FROM Representantes_Colegios
                    WHERE Colegio_Id = :colegio_id
                    LIMIT 1";
        $stmtFind = $this->db->prepare($sqlFind);
        $stmtFind->execute([
            'colegio_id' => $colegioId
        ]);
        $existingId = (int) ($stmtFind->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $sql = "UPDATE Representantes_Colegios
                    SET Representante_Id = :representante_id
                    WHERE Id = :id
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            try {
                $stmt->execute([
                    'representante_id' => $representanteId,
                    'id' => $existingId
                ]);
            } catch (Exception $e) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo actualizar el representante.'
                ];
            }
        } else {
            $sql = "INSERT INTO Representantes_Colegios (Representante_Id, Colegio_Id)
                    VALUES (:representante_id, :colegio_id)";
            $stmt = $this->db->prepare($sql);
            try {
                $stmt->execute([
                    'representante_id' => $representanteId,
                    'colegio_id' => $colegioId
                ]);
            } catch (Exception $e) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo asignar el representante.'
                ];
            }
        }

        return [
            'ok' => true,
            'mensaje' => 'Representante asignado correctamente.'
        ];
    }
}
