<?php
require_once __DIR__ . '/../config.php';

class AdminRegalosColegioModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerResumenSemanal(string $fechaDesde, string $fechaHasta)
    {
        $sql = "SELECT
                h.Id AS Hijo_Id,
                h.Nombre AS Hijo_Nombre,
                co.Nombre AS Colegio_Nombre,
                c.Nombre AS Curso_Nombre,
                c.Nivel_Educativo AS Nivel_Educativo,
                COUNT(pc.Id) AS Total_Pedidos,
                COUNT(DISTINCT pc.Fecha_entrega) AS Dias_Con_Compra,
                MIN(pc.Fecha_entrega) AS Primera_Entrega,
                MAX(pc.Fecha_entrega) AS Ultima_Entrega
            FROM Pedidos_Comida pc
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            LEFT JOIN Cursos c ON c.Id = h.Curso_Id
            LEFT JOIN Colegios co ON co.Id = h.Colegio_Id
            WHERE pc.Fecha_entrega BETWEEN :desde AND :hasta
              AND pc.Estado <> 'Cancelado'
            GROUP BY h.Id, h.Nombre, co.Nombre, c.Nombre, c.Nivel_Educativo
            ORDER BY co.Nombre, c.Nombre, h.Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
