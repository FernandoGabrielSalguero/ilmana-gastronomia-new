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
                COUNT(DISTINCT pc.Fecha_entrega) AS Dias_Con_Entrega,
                COUNT(DISTINCT DATE(pc.Fecha_pedido)) AS Dias_Con_Compra,
                MIN(pc.Fecha_entrega) AS Primera_Entrega,
                MAX(pc.Fecha_entrega) AS Ultima_Entrega,
                MIN(DATE(pc.Fecha_pedido)) AS Primera_Compra,
                MAX(DATE(pc.Fecha_pedido)) AS Ultima_Compra,
                GROUP_CONCAT(DISTINCT pc.Fecha_entrega ORDER BY pc.Fecha_entrega SEPARATOR ',') AS Fechas_Entrega,
                GROUP_CONCAT(DISTINCT CONCAT(pc.Fecha_entrega,'|',COALESCE(m.Nombre,'')) ORDER BY pc.Fecha_entrega SEPARATOR '||') AS Detalle_Entrega
            FROM Pedidos_Comida pc
            LEFT JOIN `Menú` m ON m.Id = pc.Menú_Id
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

    public function obtenerResumenPorEntrega(string $fechaDesde, string $fechaHasta)
    {
        $sql = "SELECT
                pc.Fecha_entrega AS Fecha_Entrega,
                COUNT(pc.Id) AS Total_Viandas,
                COUNT(DISTINCT pc.Hijo_Id) AS Hijos_Unicos
            FROM Pedidos_Comida pc
            WHERE pc.Fecha_entrega BETWEEN :desde AND :hasta
              AND pc.Estado <> 'Cancelado'
            GROUP BY pc.Fecha_entrega
            ORDER BY pc.Fecha_entrega";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertarRegalo(array $data): bool
    {
        $sql = "INSERT INTO Regalos_Colegio
                (Alumno_Nombre, Colegio_Nombre, Curso_Nombre, Nivel_Educativo, Fecha_Entrega_Jueves, Menus_Semana)
                VALUES (:alumno, :colegio, :curso, :nivel, :fecha_jueves, :menus)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'alumno' => $data['alumno'],
            'colegio' => $data['colegio'],
            'curso' => $data['curso'],
            'nivel' => $data['nivel'],
            'fecha_jueves' => $data['fecha_jueves'],
            'menus' => $data['menus']
        ]);
    }

    public function existeRegalo(string $alumnoNombre, string $fechaJueves): bool
    {
        $sql = "SELECT 1
            FROM Regalos_Colegio
            WHERE Alumno_Nombre = :alumno
              AND Fecha_Entrega_Jueves = :fecha
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'alumno' => $alumnoNombre,
            'fecha' => $fechaJueves
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function obtenerRegalos(string $fechaDesde, string $fechaHasta)
    {
        $sql = "SELECT
                Id,
                Alumno_Nombre,
                Colegio_Nombre,
                Curso_Nombre,
                Nivel_Educativo,
                Fecha_Entrega_Jueves,
                Menus_Semana,
                Creado_En
            FROM Regalos_Colegio
            WHERE Fecha_Entrega_Jueves BETWEEN :desde AND :hasta
            ORDER BY Fecha_Entrega_Jueves DESC, Alumno_Nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarRegalo(int $id): bool
    {
        $sql = "DELETE FROM Regalos_Colegio WHERE Id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
