<?php
require_once __DIR__ . '/../../includes/auth.php';
cerrar_sesion();
session_start();
flash('success', 'Sesión cerrada. ¡Hasta pronto!');
redirigir('index.php');
