<?php
require_once __DIR__ . '/../config.php';

class AdminViandasColegioModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerMenuActual()
    {
        $sql = "SELECT Id, Nombre, Fecha_entrega, Fecha_hora_compra, Fecha_hora_cancelacion, Precio, Estado, Nivel_Educativo
                FROM MenÃº
                ORDER BY Fecha_entrega DESC, Id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearMenu(array $data, array $niveles)
    {
        $niveles = array_values(array_unique(array_filter($niveles)));
        if (empty($niveles)) {
            return [
                'ok' => false,
                'mensaje' => 'Selecciona al menos un nivel educativo.'
            ];
        }

        $sql = "INSERT INTO MenÃº (Nombre, Fecha_entrega, Fecha_hora_compra, Fecha_hora_cancelacion, Precio, Estado, Nivel_Educativo)
                VALUES (:nombre, :fecha_entrega, :fecha_hora_compra, :fecha_hora_cancelacion, :precio, :estado, :nivel)";
        $stmt = $this->db->prepare($sql);

        try {
            $this->db->beginTransaction();

            foreach ($niveles as $nivel) {
                $stmt->execute([
                    'nombre' => $data['nombre'],
                    'fecha_entrega' => $data['fecha_entrega'],
                    'fecha_hora_compra' => $data['fecha_hora_compra'],
                    'fecha_hora_cancelacion' => $data['fecha_hora_cancelacion'],
                    'precio' => $data['precio'],
                    'estado' => $data['estado'],
                    'nivel' => $nivel
                ]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'ok' => false,
                'mensaje' => 'No se pudo guardar el menu.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Menu guardado correctamente.'
        ];
    }
}
