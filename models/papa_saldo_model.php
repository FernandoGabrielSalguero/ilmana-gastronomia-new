<?php
require_once __DIR__ . '/../config.php';

class PapaSaldoModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function crearSolicitudSaldo($usuarioId, $monto, $comprobante)
    {
        $sql = "INSERT INTO Pedidos_Saldo (Usuario_Id, Saldo, Estado, Comprobante, Fecha_pedido)
                VALUES (:usuarioId, :monto, 'Pendiente de aprobacion', :comprobante, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'usuarioId' => $usuarioId,
            'monto' => $monto,
            'comprobante' => $comprobante
        ]);
    }

    public function obtenerSaldoPendiente($usuarioId)
    {
        $sql = "SELECT COALESCE(SUM(Saldo), 0) AS TotalPendiente
            FROM Pedidos_Saldo
            WHERE Usuario_Id = :usuarioId
            AND Estado = 'Pendiente de aprobacion'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['TotalPendiente'] : 0.0;
    }
}
