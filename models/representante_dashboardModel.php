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
                h.Id AS Hijo_Id,
                h.Nombre AS Alumno,
                pc.Estado,
                pc.motivo_cancelacion
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
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
