<?php

require_once __DIR__ . '/../config.php';

class AuthModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function login($usuario, $contrasenaIngresada)
{
    $sql = "SELECT 
        Id,
        Usuario,
        Contrasena,
        Rol,
        Estado,
        Nombre,
        Correo,
        Telefono,
        Saldo
        FROM Usuarios
        WHERE Usuario = :usuario
        LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }
    if (($user['Estado'] ?? '') !== 'activo') {
        return ['error' => 'inactive'];
    }

    $hash = $user['Contrasena'] ?? '';
    $isHashed = preg_match('/^\$2y\$/', $hash);

    if (
        (!$isHashed && $hash === $contrasenaIngresada) ||
        ($isHashed && password_verify($contrasenaIngresada, $hash))
    ) {
        return $user;
    }

    return null;
}

}
