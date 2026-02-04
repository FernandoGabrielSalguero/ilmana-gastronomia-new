<?php
require_once __DIR__ . '/../config.php';

class PapaDashboardModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerHijosPorUsuario($usuarioId)
    {
        $sql = "SELECT h.Id, h.Nombre 
                FROM Usuarios_Hijos uh
                JOIN Hijos h ON h.Id = uh.Hijo_Id
                WHERE uh.Usuario_Id = :usuarioId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerHijosDetallePorUsuario($usuarioId)
    {
        $sql = "SELECT 
                h.Id,
                h.Nombre,
                h.Colegio_Id,
                h.Curso_Id,
                COALESCE(pa.Nombre, h.Preferencias_Alimenticias) AS Preferencia,
                c.Nombre AS Colegio,
                cu.Nombre AS Curso
            FROM Usuarios_Hijos uh
            JOIN Hijos h ON h.Id = uh.Hijo_Id
            LEFT JOIN Preferencias_Alimenticias pa ON pa.Id = h.Preferencias_Alimenticias
            LEFT JOIN Colegios c ON c.Id = h.Colegio_Id
            LEFT JOIN Cursos cu ON cu.Id = h.Curso_Id
            WHERE uh.Usuario_Id = :usuarioId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCursosDisponibles()
    {
        $sql = "SELECT Id, Nombre, Colegio_Id, Nivel_Educativo
                FROM Cursos
                ORDER BY Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tieneActualizacionCursoAnual($usuarioId, $anio)
    {
        if (!$usuarioId || !$anio) {
            return false;
        }

        $inicio = sprintf('%d-01-01 00:00:00', (int) $anio);
        $fin = sprintf('%d-01-01 00:00:00', (int) $anio + 1);

        try {
            $sql = "SELECT 1
                    FROM Auditoria_Eventos
                    WHERE Usuario_Id = :usuarioId
                      AND Evento = 'papa_actualizar_curso'
                      AND Creado_En >= :inicio
                      AND Creado_En < :fin
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'usuarioId' => $usuarioId,
                'inicio' => $inicio,
                'fin' => $fin,
            ]);
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    public function actualizarCursosHijos($usuarioId, array $cursosPorHijo)
    {
        if (!$usuarioId || empty($cursosPorHijo)) {
            return ['ok' => false, 'error' => 'No se recibieron cursos para actualizar.'];
        }

        $stmt = $this->db->prepare("SELECT h.Id, h.Curso_Id, h.Colegio_Id
            FROM Usuarios_Hijos uh
            JOIN Hijos h ON h.Id = uh.Hijo_Id
            WHERE uh.Usuario_Id = :usuarioId");
        $stmt->execute(['usuarioId' => $usuarioId]);
        $hijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$hijos) {
            return ['ok' => false, 'error' => 'No hay hijos asociados al usuario.'];
        }

        $hijosMap = [];
        foreach ($hijos as $hijo) {
            $hijosMap[(int) $hijo['Id']] = [
                'curso_id' => $hijo['Curso_Id'] !== null ? (int) $hijo['Curso_Id'] : null,
                'colegio_id' => $hijo['Colegio_Id'] !== null ? (int) $hijo['Colegio_Id'] : null,
            ];
        }

        $cursosIds = [];
        foreach ($cursosPorHijo as $hijoId => $cursoId) {
            $cursoId = (int) $cursoId;
            if ($cursoId > 0) {
                $cursosIds[] = $cursoId;
            }
        }
        $cursosIds = array_values(array_unique($cursosIds));

        $cursosMap = [];
        if (!empty($cursosIds)) {
            $placeholders = implode(',', array_fill(0, count($cursosIds), '?'));
            $stmt = $this->db->prepare("SELECT Id, Colegio_Id FROM Cursos WHERE Id IN ($placeholders)");
            $stmt->execute($cursosIds);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $curso) {
                $cursosMap[(int) $curso['Id']] = $curso['Colegio_Id'] !== null ? (int) $curso['Colegio_Id'] : null;
            }
        }

        $actualizados = 0;
        $detalles = [];

        try {
            $this->db->beginTransaction();
            $stmtUpdate = $this->db->prepare("UPDATE Hijos SET Curso_Id = :cursoId WHERE Id = :hijoId");

            foreach ($cursosPorHijo as $hijoId => $cursoId) {
                $hijoId = (int) $hijoId;
                if (!isset($hijosMap[$hijoId])) {
                    continue;
                }

                $nuevoCursoId = (int) $cursoId;
                $nuevoCursoId = $nuevoCursoId > 0 ? $nuevoCursoId : null;
                $cursoActual = $hijosMap[$hijoId]['curso_id'];

                if ($nuevoCursoId === $cursoActual) {
                    continue;
                }

                if ($nuevoCursoId !== null) {
                    if (!isset($cursosMap[$nuevoCursoId])) {
                        $this->db->rollBack();
                        return ['ok' => false, 'error' => 'El curso seleccionado no es valido.'];
                    }
                    $colegioHijo = $hijosMap[$hijoId]['colegio_id'];
                    $colegioCurso = $cursosMap[$nuevoCursoId];
                    if ($colegioHijo !== null && $colegioCurso !== null && $colegioHijo !== $colegioCurso) {
                        $this->db->rollBack();
                        return ['ok' => false, 'error' => 'El curso seleccionado no corresponde al colegio del hijo.'];
                    }
                }

                $stmtUpdate->execute([
                    'cursoId' => $nuevoCursoId,
                    'hijoId' => $hijoId
                ]);
                $actualizados += 1;
                $detalles[] = [
                    'hijo_id' => $hijoId,
                    'curso_id' => $nuevoCursoId
                ];
            }

            if ($actualizados === 0) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'No se detectaron cambios para guardar.'];
            }

            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'error' => 'No se pudieron guardar los cambios.'];
        }

        return [
            'ok' => true,
            'actualizados' => $actualizados,
            'detalles' => $detalles
        ];
    }

    public function obtenerPedidosSaldo($usuarioId, $desde = null, $hasta = null)
    {
        $sql = "SELECT Id, Saldo, Estado, Comprobante, Observaciones, Fecha_pedido
            FROM Pedidos_Saldo 
            WHERE Usuario_Id = :usuarioId";

        $params = ['usuarioId' => $usuarioId];

        if ($desde && $hasta) {
            $sql .= " AND Fecha_pedido BETWEEN :desde AND :hasta";
            $params['desde'] = $desde . ' 00:00:00';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $sql .= " ORDER BY Id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenerPedidosComida($usuarioId, $hijoId = null, $desde = null, $hasta = null)
    {
        $sql = "SELECT 
                pc.Id,
                h.Nombre AS Alumno,
                m.Nombre AS Menu,
                pc.Fecha_entrega,
                pc.Estado,
                m.Fecha_hora_cancelacion,
                CASE
                    WHEN pc.Estado = 'Procesando'
                        AND (m.Fecha_hora_cancelacion IS NULL OR m.Fecha_hora_cancelacion >= NOW())
                    THEN 1
                    ELSE 0
                END AS Puede_cancelar
            FROM Pedidos_Comida pc
            JOIN Usuarios_Hijos uh ON pc.Hijo_Id = uh.Hijo_Id
            JOIN Hijos h ON h.Id = pc.Hijo_Id
            JOIN Menú m ON m.Id = pc.Menú_Id
            WHERE uh.Usuario_Id = :usuarioId";

        $params = ['usuarioId' => $usuarioId];

        if ($hijoId) {
            $sql .= " AND pc.Hijo_Id = :hijoId";
            $params['hijoId'] = $hijoId;
        }

        if ($desde && $hasta) {
            $sql .= " AND pc.Fecha_entrega BETWEEN :desde AND :hasta";
            $params['desde'] = $desde;
            $params['hasta'] = $hasta;
        }

        $sql .= " ORDER BY pc.Id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function obtenerSaldoUsuario($usuarioId)
    {
        $sql = "SELECT Saldo FROM Usuarios WHERE Id = :usuarioId LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuarioId' => $usuarioId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['Saldo'] : 0.0;
    }

    public function cancelarPedidoComida($usuarioId, $pedidoId, $motivo)
    {
        if (!$usuarioId || !$pedidoId) {
            return ["ok" => false, "error" => "Datos invalidos."];
        }

        try {
            $sql = "SELECT pc.Id, pc.Estado, m.Fecha_hora_cancelacion, m.Precio
                FROM Pedidos_Comida pc
                JOIN Usuarios_Hijos uh ON pc.Hijo_Id = uh.Hijo_Id
                JOIN Menú m ON m.Id = pc.Menú_Id
                WHERE pc.Id = :pedidoId
                AND uh.Usuario_Id = :usuarioId
                LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                "pedidoId" => $pedidoId,
                "usuarioId" => $usuarioId
            ]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                return ["ok" => false, "error" => "Pedido no encontrado."];
            }

            if ($pedido["Estado"] !== "Procesando") {
                return ["ok" => false, "error" => "El pedido ya no puede cancelarse."];
            }

            if (!empty($pedido["Fecha_hora_cancelacion"])) {
                $stmt = $this->db->prepare("SELECT CASE WHEN :limite >= NOW() THEN 1 ELSE 0 END");
                $stmt->execute(["limite" => $pedido["Fecha_hora_cancelacion"]]);
                $puede = (int) $stmt->fetchColumn();
                if ($puede !== 1) {
                    return ["ok" => false, "error" => "Se vencio el plazo de cancelacion."];
                }
            }

            $reintegro = $pedido["Precio"] !== null ? (float)$pedido["Precio"] : 0.0;

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE Pedidos_Comida
                SET Estado = 'Cancelado', motivo_cancelacion = :motivo
                WHERE Id = :pedidoId");
            $stmt->execute([
                "motivo" => $motivo,
                "pedidoId" => $pedidoId
            ]);

            $stmt = $this->db->prepare("UPDATE Usuarios
                SET Saldo = Saldo + :reintegro
                WHERE Id = :usuarioId");
            $stmt->execute([
                "reintegro" => $reintegro,
                "usuarioId" => $usuarioId
            ]);

            $this->db->commit();

            return ["ok" => true];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $mensaje = $e->getMessage();
            if (stripos($mensaje, "motivo_cancelacion") !== false) {
                return [
                    "ok" => false,
                    "error" => "Falta la columna motivo_cancelacion en Pedidos_Comida."
                ];
            }
            return ["ok" => false, "error" => "Error al cancelar el pedido."];
        }
    }
}
