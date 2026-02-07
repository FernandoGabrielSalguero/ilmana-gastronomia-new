<?php
require_once __DIR__ . '/../config.php';

class RepresentanteDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerCursosConPedidos($representanteId, $fechaEntrega)
    {
        $sql = "SELECT DISTINCT
                c.Id AS Curso_Id,
                c.Nombre AS Curso_Nombre,
                col.Nombre AS Colegio_Nombre,
                h.Id AS Hijo_Id,
                h.Nombre AS Alumno,
                pc.Estado,
                pc.motivo_cancelacion
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            LEFT JOIN Colegios col ON col.Id = h.Colegio_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
                AND pc.Fecha_entrega = :fechaEntrega
            ORDER BY c.Nombre, h.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRegalosPorFechaYColegios(string $fechaEntrega, array $colegios): array
    {
        if (empty($colegios)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($colegios), '?'));
        $sql = "SELECT Alumno_Nombre, Colegio_Nombre
            FROM Regalos_Colegio
            WHERE Fecha_Entrega_Jueves = ?
              AND Colegio_Nombre IN ($placeholders)";

        $stmt = $this->db->prepare($sql);
        $params = array_merge([$fechaEntrega], array_values($colegios));
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResumenPedidosPorCurso($representanteId, $fechaEntrega)
    {
        $sql = "SELECT
                c.Id AS Curso_Id,
                c.Nombre AS Curso_Nombre,
                COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
              AND pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'
            GROUP BY c.Id, c.Nombre
            ORDER BY c.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTotalPedidosDia($representanteId, $fechaEntrega)
    {
        $sql = "SELECT COUNT(pc.Id) AS Total
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
              AND pc.Fecha_entrega = :fechaEntrega
              AND pc.Estado <> 'Cancelado'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['Total'] : 0;
    }

    public function obtenerColegiosPorRepresentante($representanteId)
    {
        $sql = "SELECT DISTINCT c.Nombre
            FROM Colegios c
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = c.Id
            WHERE rc.Representante_Id = :representanteId
            ORDER BY c.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId
        ]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerCursosPorRepresentante($representanteId)
    {
        $sql = "SELECT DISTINCT c.Id, c.Nombre
            FROM Cursos c
            JOIN Colegios col ON col.Id = c.Colegio_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = col.Id
            WHERE rc.Representante_Id = :representanteId
            ORDER BY c.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'representanteId' => $representanteId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAlumnosPorRepresentante($representanteId, $fechaEntrega = null, $nombre = null)
    {
        $sql = "SELECT DISTINCT h.Id, h.Nombre, h.Curso_Id, c.Nombre AS Curso
            FROM Hijos h
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id";

        $params = [
            'representanteId' => $representanteId
        ];

        if ($fechaEntrega) {
            $sql .= " JOIN Pedidos_Comida pc ON pc.Hijo_Id = h.Id";
        }

        $sql .= " WHERE rc.Representante_Id = :representanteId";

        if ($fechaEntrega) {
            $sql .= " AND pc.Fecha_entrega = :fechaEntrega";
            $params['fechaEntrega'] = $fechaEntrega;
        }

        if ($nombre) {
            $sql .= " AND h.Nombre LIKE :nombre";
            $params['nombre'] = '%' . $nombre . '%';
        }

        $sql .= " ORDER BY h.Id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarCursoHijo($representanteId, $hijoId, $cursoId)
    {
        $sql = "UPDATE Hijos h
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            SET h.Curso_Id = :cursoId
            WHERE h.Id = :hijoId
              AND rc.Representante_Id = :representanteId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cursoId' => $cursoId,
            'hijoId' => $hijoId,
            'representanteId' => $representanteId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function obtenerDetalleCursoPedidos($representanteId, $cursoId, $fechaEntrega)
    {
        $cursoCondicion = $cursoId === 'sin_curso' ? 'h.Curso_Id IS NULL' : 'h.Curso_Id = :cursoId';

        $sql = "SELECT
                c.Nombre AS Curso,
                col.Nombre AS Colegio,
                h.Nombre AS Alumno,
                pc.Estado,
                pc.motivo_cancelacion,
                m.Nombre AS Menu,
                COALESCE(pa.Nombre, h.Preferencias_Alimenticias) AS Preferencias
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Preferencias_Alimenticias pa ON pa.Id = h.Preferencias_Alimenticias
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            LEFT JOIN Colegios col ON col.Id = h.Colegio_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            JOIN Representantes_Colegios rc ON rc.Colegio_Id = h.Colegio_Id
            WHERE rc.Representante_Id = :representanteId
              AND pc.Fecha_entrega = :fechaEntrega
              AND {$cursoCondicion}
            ORDER BY h.Nombre";

        $params = [
            'representanteId' => $representanteId,
            'fechaEntrega' => $fechaEntrega
        ];

        if ($cursoId !== 'sin_curso') {
            $params['cursoId'] = $cursoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $viandas = 0;
        foreach ($rows as $row) {
            if (($row['Estado'] ?? '') !== 'Cancelado') {
                $viandas++;
            }
        }

        return [
            'rows' => $rows,
            'viandas' => $viandas
        ];
    }
}
