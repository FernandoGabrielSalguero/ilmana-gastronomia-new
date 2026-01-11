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
                VALUES (:usuarioId, :monto, 'Pendiente de aprobación', :comprobante, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'usuarioId' => $usuarioId,
            'monto' => $monto,
            'comprobante' => $comprobante
        ]);
    }
}
