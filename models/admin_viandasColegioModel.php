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
                FROM `Menú`
                ORDER BY Id DESC
                LIMIT 100";
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

        $sql = "INSERT INTO `Menú` (Nombre, Fecha_entrega, Fecha_hora_compra, Fecha_hora_cancelacion, Precio, Estado, Nivel_Educativo)
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

    public function actualizarMenu(int $id, array $data, string $nivel)
    {
        $sql = "UPDATE `Menú`
                SET Nombre = :nombre,
                    Fecha_entrega = :fecha_entrega,
                    Fecha_hora_compra = :fecha_hora_compra,
                    Fecha_hora_cancelacion = :fecha_hora_cancelacion,
                    Precio = :precio,
                    Estado = :estado,
                    Nivel_Educativo = :nivel
                WHERE Id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'nombre' => $data['nombre'],
                'fecha_entrega' => $data['fecha_entrega'],
                'fecha_hora_compra' => $data['fecha_hora_compra'],
                'fecha_hora_cancelacion' => $data['fecha_hora_cancelacion'],
                'precio' => $data['precio'],
                'estado' => $data['estado'],
                'nivel' => $nivel,
                'id' => $id
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo actualizar el menu.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Menu actualizado correctamente.'
        ];
    }

    public function eliminarMenu(int $id)
    {
        $sql = "DELETE FROM `Menú` WHERE Id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'id' => $id
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo eliminar el menu.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Menu eliminado correctamente.'
        ];
    }

    public function obtenerDescuentos()
    {
        $sql = "SELECT Id, Colegio_Id, Nivel_Educativo, Porcentaje, Viandas_Por_Dia_Min,
                       Vigencia_Desde, Vigencia_Hasta, Dias_Obligatorios, Terminos, Estado
                FROM descuentos_colegios
                ORDER BY Id DESC
                LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearDescuento(array $data)
    {
        $sql = "INSERT INTO descuentos_colegios
                    (Colegio_Id, Nivel_Educativo, Porcentaje, Viandas_Por_Dia_Min,
                     Vigencia_Desde, Vigencia_Hasta, Dias_Obligatorios, Terminos, Estado)
                VALUES
                    (:colegio_id, :nivel, :porcentaje, :viandas_por_dia,
                     :vigencia_desde, :vigencia_hasta, :dias_obligatorios, :terminos, :estado)";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'colegio_id' => $data['colegio_id'],
                'nivel' => $data['nivel_educativo'],
                'porcentaje' => $data['porcentaje'],
                'viandas_por_dia' => $data['viandas_por_dia'],
                'vigencia_desde' => $data['vigencia_desde'],
                'vigencia_hasta' => $data['vigencia_hasta'],
                'dias_obligatorios' => $data['dias_obligatorios'],
                'terminos' => $data['terminos'],
                'estado' => $data['estado']
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo guardar el descuento.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Descuento guardado correctamente.'
        ];
    }

    public function actualizarDescuento(int $id, array $data)
    {
        $sql = "UPDATE descuentos_colegios
                SET Colegio_Id = :colegio_id,
                    Nivel_Educativo = :nivel,
                    Porcentaje = :porcentaje,
                    Viandas_Por_Dia_Min = :viandas_por_dia,
                    Vigencia_Desde = :vigencia_desde,
                    Vigencia_Hasta = :vigencia_hasta,
                    Dias_Obligatorios = :dias_obligatorios,
                    Terminos = :terminos,
                    Estado = :estado
                WHERE Id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'colegio_id' => $data['colegio_id'],
                'nivel' => $data['nivel_educativo'],
                'porcentaje' => $data['porcentaje'],
                'viandas_por_dia' => $data['viandas_por_dia'],
                'vigencia_desde' => $data['vigencia_desde'],
                'vigencia_hasta' => $data['vigencia_hasta'],
                'dias_obligatorios' => $data['dias_obligatorios'],
                'terminos' => $data['terminos'],
                'estado' => $data['estado'],
                'id' => $id
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo actualizar el descuento.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Descuento actualizado correctamente.'
        ];
    }

    public function eliminarDescuento(int $id)
    {
        $sql = "DELETE FROM descuentos_colegios WHERE Id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                'id' => $id
            ]);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo eliminar el descuento.'
            ];
        }

        return [
            'ok' => true,
            'mensaje' => 'Descuento eliminado correctamente.'
        ];
    }
}
