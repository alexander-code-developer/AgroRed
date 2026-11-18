<?php
/** Sesión segura, login y control de acceso por roles (RBAC - RF-07). */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => cfg('sesion_horas') * 3600,
        'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function logueado(): bool { return usuario() !== null; }
function es_rol(string ...$roles): bool
{
    $u = usuario();
    return $u && ($u['rol'] === 'admin' || in_array($u['rol'], $roles, true));
}
function requerir_login(): void
{
    if (!logueado()) { flash('warning', 'Inicia sesión para continuar.'); redirigir('pages/auth/login.php'); }
}
function requerir_rol(string ...$roles): void
{
    requerir_login();
    if (!es_rol(...$roles)) { http_response_code(403); flash('danger', 'No tienes permiso para ver esa sección.'); redirigir('index.php'); }
}
function iniciar_sesion(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'correo' => $u['correo'],
                            'telefono' => $u['telefono'], 'rol' => $u['rol']];
}
function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path']); }
    session_destroy();
}
/** Limita intentos de login por sesión (anti fuerza bruta básico). */
function demasiados_intentos(): bool
{
    $t = $_SESSION['intentos'] ?? ['n' => 0, 'hasta' => 0];
    return $t['n'] >= 5 && time() < $t['hasta'];
}
function registrar_intento(bool $ok): void
{
    if ($ok) { unset($_SESSION['intentos']); return; }
    $t = $_SESSION['intentos'] ?? ['n' => 0, 'hasta' => 0];
    $t['n']++; if ($t['n'] >= 5) $t['hasta'] = time() + 600;
    $_SESSION['intentos'] = $t;
}
