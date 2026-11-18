<?php
/** Utilidades generales: configuración, escape, redirecciones, flash, CSRF, JSON. */
function cfg(string $clave = null)
{
    static $c = null;
    $c ??= require __DIR__ . '/../config/config.php';
    return $clave ? ($c[$clave] ?? null) : $c;
}
function url(string $ruta = ''): string { return rtrim(cfg('base_url'), '/') . '/' . ltrim($ruta, '/'); }
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirigir(string $ruta): never { header('Location: ' . url($ruta)); exit; }

function flash(string $tipo, string $msg): void { $_SESSION['flash'][] = [$tipo, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_campo(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_validar(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesión expirada o formulario inválido. Regresa y vuelve a intentarlo.');
    }
}
function es_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function post(string $k, string $def = ''): string { return trim((string)($_POST[$k] ?? $def)); }
function json_out($data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fecha_corta(?string $f): string { return $f ? date('d/m/Y', strtotime($f)) : '—'; }
function estrellas(float $n): string
{
    $r = (int)round($n); $h = '';
    for ($i = 1; $i <= 5; $i++) $h .= '<i class="bi ' . ($i <= $r ? 'bi-star-fill' : 'bi-star') . ' text-warning"></i>';
    return $h;
}
function es_telefono(string $t): bool { return (bool)preg_match('/^\d{10}$/', $t); }
