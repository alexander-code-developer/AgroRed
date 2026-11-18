# AgroRed Hidalgo · Guía de instalación y explicación del proyecto

## 1. Requisitos

| Qué | Versión | Para qué |
|---|---|---|
| XAMPP, WAMP o Laragon (Apache + PHP + MySQL/MariaDB) | PHP **8.1 o superior** | Servidor local |
| Navegador moderno | Chrome, Firefox, Edge o Safari | Ver el sitio |
| Conexión a internet | — | Cargar Bootstrap, iconos, Leaflet, tipografías y los mosaicos de OpenStreetMap desde CDN |

La extensión **pdo_mysql** viene activada por defecto en XAMPP.

## 2. Instalación paso a paso (XAMPP en Windows)

1. **Instala XAMPP** y abre el *XAMPP Control Panel*. Pulsa **Start** en **Apache** y en **MySQL**.
2. **Copia la carpeta `agrored-hidalgo`** completa dentro de `C:\xampp\htdocs\`.
   Debe quedar así: `C:\xampp\htdocs\agrored-hidalgo\index.php`
3. **Crea la base de datos.** Abre `http://localhost/phpmyadmin`:
   1. Pestaña **Importar** → elige `database/schema.sql` → **Importar** (crea la base `agrored` y todas las tablas).
   2. Vuelve a **Importar** → elige `database/seed.sql` (usuarios, huertos y datos de prueba).
4. **Revisa `config/config.php`.** Con XAMPP no necesitas cambiar nada (usuario `root`, sin contraseña). Si tu MySQL tiene contraseña, escríbela en `'pass'`.
5. **Abre** `http://localhost/agrored-hidalgo/`

> Si la carpeta no se llama `agrored-hidalgo` o la pones en la raíz del servidor, cambia `'base_url'` en `config/config.php`
> (por ejemplo `''` si está en la raíz, o `'/mi-carpeta'`).

### Alternativa sin Apache (servidor integrado de PHP)
```bash
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql
cd agrored-hidalgo
# pon 'base_url' => '' en config/config.php
php -S localhost:8000
```

## 3. Usuarios de prueba (contraseña de todos: `Agrored2026!`)

| Rol | Correo | Qué puede hacer |
|---|---|---|
| Productor | `mario.cordero@ejemplo.com` | Registrar huertos, inventario, pedidos, mensajes, finanzas |
| Productor | `rosa.hernandez@ejemplo.com` | Igual (otro huerto, para probar mensajes) |
| Recolector | `pedro.recolector@ejemplo.com` | Logística: rutas OSRM y simulación |
| Coordinador | `ana.coordinadora@ejemplo.com` | Logística y dashboard de impacto |
| Cliente | `laura@ejemplo.com` | Comprar y dejar reseñas |
| Administrador | `admin@agrored.test` | Acceso a todo |

⚠️ **Cambia o elimina estos usuarios antes de publicar el sitio en un servidor real.**

## 4. Estructura de carpetas

```
agrored-hidalgo/
├── index.php                  Inicio: hero, mapa de huertos, módulos, ODS
├── .htaccess                  Cabeceras de seguridad, sin listado de carpetas
├── config/
│   ├── config.php             Base de datos, URL base, OSRM, punto de distribución, factor CO₂
│   └── database.php           Conexión PDO (consultas preparadas)
├── includes/
│   ├── helpers.php            e(), url(), flash, CSRF, JSON, validaciones
│   ├── auth.php               Sesión, login, roles (RBAC), límite de intentos
│   ├── header.php             Barra de navegación Bootstrap (reutilizada en todas las páginas)
│   └── footer.php             Pie y carga de scripts
├── pages/
│   ├── auth/                  login, crear-cuenta, recuperar, restablecer, logout
│   ├── huertos/               registrar (mapa), compra (mapa+pedido+reseñas), inventario
│   ├── productor/             pedidos, mensajes, finanzas
│   ├── logistica/             rutas (OSRM + simulación de flota)
│   ├── perfil.php             Perfil por rol
│   └── dashboard.php          Impacto social y ambiental
├── api/
│   ├── huertos.php            JSON de huertos (con privacidad de ubicación)
│   └── ruta.php               Proxy a OSRM: ruta optimizada (máx. 20 puntos)
├── assets/
│   ├── css/agrored.css        Tema visual (paleta del logo)
│   ├── js/agrored.js          Mapas Leaflet y utilidades
│   └── img/                   Logo y favicon
├── database/
│   ├── schema.sql             Tablas, llaves foráneas y vista de finanzas
│   └── seed.sql               Datos de prueba
└── docs/GUIA_INSTALACION.md   Este documento
```

## 5. Qué se hizo y con qué

**Rediseño visual.** Se tomaron los colores del logo (verde bosque y naranja de fruto) sobre fondo crema. Tipografías:
*Fraunces* (títulos) y *Nunito Sans* (texto). Bootstrap 5 aporta rejilla responsiva, formularios, tablas y menú; **Bootstrap Icons**
reemplaza los emojis del diseño anterior. El menú sigue los wireframes (Inicio, Registro, Compra, Inventario, Logística, Dashboard, Perfil) y
se vuelve hamburguesa en celular (diseño desde 360 px, como pide el ERS).

**Mapa (Leaflet + OpenStreetMap).**
- *Inicio y Compra:* marcadores propios de huerto; el popup muestra nombre, estrellas y botón "Ver / comprar". En Compra el buscador por nombre filtra el mapa en vivo.
- *Registro de huerto:* se toca el mapa para fijar el punto (el marcador se puede arrastrar) o se usa "Usar mi ubicación"; las coordenadas se llenan solas.
- *Logística:* dibuja la ruta optimizada con paradas numeradas, simula el recorrido de un camión y una flota virtual de 10 a 50 vehículos.
- *Privacidad (ERS):* quien no ha iniciado sesión solo recibe coordenadas redondeadas a 2 decimales (≈1 km) y **no** ve teléfonos. La lógica está en `api/huertos.php`, no en el navegador.

**Base de datos (MySQL).** 14 tablas normalizadas (usuarios, roles, municipios, colonias, huertos, cultivos, inventario, pedidos, estados_pedido,
reseñas, mensajes, movimientos de finanzas, tokens de recuperación) y la vista `v_finanzas_mensual`. Parte del modelo del DER del equipo y de las
tablas que ya usaba el proyecto anterior (reseñas, mensajes, finanzas, recuperación de contraseña).

**Backend (PHP 8 + PDO).** Cada página procesa su propio formulario. Los datos se guardan con consultas preparadas.

**Seguridad aplicada** (de los requisitos del ERS):
- Contraseñas con `password_hash` (bcrypt); nunca se guardan en claro.
- Sesión de 8 horas, `session_regenerate_id` al entrar, cookie `HttpOnly`.
- Token **CSRF** en todos los formularios POST; salida escapada con `htmlspecialchars` (anti XSS).
- **RBAC**: `requerir_rol()` limita cada página (admin, productor, recolector, coordinador, usuario).
- Máximo 5 intentos de login fallidos por sesión (bloqueo 10 min).
- Recuperación de contraseña con token de un solo uso que vence a los 30 minutos (solo se guarda su hash SHA-256) y mensaje idéntico exista o no el correo.
- Carpetas `config`, `includes`, `database`, `docs` bloqueadas por `.htaccess`.

**Historias de usuario cubiertas**

| HU | Dónde está |
|---|---|
| 001-004 Cuenta, login, roles, recuperar contraseña | `pages/auth/`, `includes/auth.php` |
| 005 Alta y geolocalización de huertos | `pages/huertos/registrar.php` |
| 006-007 Mapa y búsqueda | `index.php`, `compra.php`, `api/huertos.php` |
| 008-009 Inventario y nutrición | `pages/huertos/inventario.php` |
| 010-012 Compra directa, seguimiento, reseñas | `compra.php`, `productor/pedidos.php` |
| 013 Redes sociales del huerto | campo Facebook en registro y ficha en `compra.php` |
| 014-016 Perfil, mensajes, finanzas | `perfil.php`, `productor/mensajes.php`, `productor/finanzas.php` |
| 017-019 Rutas OSRM, hoja de ruta, telemetría simulada | `pages/logistica/rutas.php`, `api/ruta.php` |
| 020-021 Impacto y privacidad | `dashboard.php`, `api/huertos.php` |

El flujo de pedidos es una máquina de estados fija en código (`solicitado → contactado → completado | cancelado`; los pedidos cerrados no se reabren).

## 6. OSRM (rutas)

Por defecto se usa el servidor público de demostración de OSRM (`https://router.project-osrm.org`), suficiente para clases y pruebas, pero sin garantía
de disponibilidad. Para el ERS (respuesta menor a 3 s) conviene levantar tu propio OSRM con datos de OpenStreetMap de México y cambiar
`'osrm_url'` en `config/config.php` (por ejemplo `http://localhost:5000`). Si OSRM no responde, la página muestra un aviso y el resto del sitio sigue funcionando.

## 7. Solución de problemas

| Síntoma | Solución |
|---|---|
| "No se pudo conectar a la base de datos" | Enciende MySQL, revisa usuario/contraseña en `config/config.php` e importa `schema.sql`. |
| Página sin estilos ni mapa | Falta internet (CDN) o la ruta `base_url` no coincide con el nombre de tu carpeta. |
| Acentos raros (Ã±) | Importa los `.sql` desde phpMyAdmin o con `mysql --default-character-set=utf8mb4`. |
| Error 404 al abrir las páginas | Revisa que la carpeta esté en `htdocs` y que `base_url` sea `/agrored-hidalgo`. |
| No llega el correo de recuperación | `mail()` requiere un servidor SMTP. En `modo_dev => true` el enlace se muestra en pantalla. En producción configura SMTP (p. ej. PHPMailer) y pon `modo_dev` en `false`. |
| La ruta marca "OSRM no disponible" | Revisa tu conexión o cambia `osrm_url` a tu propio servidor. |

## 8. Pendientes y mejoras sugeridas

- Envío real de correos (SMTP / PHPMailer).
- Servidor OSRM propio y datos de Hidalgo.
- Panel de administración de usuarios (hoy los roles `admin` y `coordinador` se asignan directo en la base de datos).
- Pruebas automatizadas (PHPUnit) y activar HTTPS en producción.


## Configurar envío y recepción de correo

1. Copia `config/config.local.php.example` como `config/config.local.php` (está en `.gitignore`) y llena tus datos: `activo => true`, `usuario`, `clave` y `desde_correo`.
   - **Gmail:** activa la verificación en 2 pasos y crea una *Contraseña de aplicación* (Cuenta de Google → Seguridad). Úsala en `clave`; host `smtp.gmail.com`, puerto `587`, seguridad `tls`.
   - **Hosting/cPanel:** host `mail.tudominio.com`, usuario = correo completo, puerto `587` (`tls`) o `465` (`ssl`).
2. Si ya tenías la base importada, ejecuta `database/migracion_correo.sql` (agrega `pedidos.correo`). Si importas de cero, `schema.sql` ya lo trae.
3. Entra como administrador (`admin@agrored.test`) → menú de usuario → **Correo** → envía un correo de prueba. Si falla, el error aparece en pantalla.
4. Con `correo.activo = true` ya puedes poner `modo_dev => false`.

**Qué correos envía la plataforma** (`includes/notificaciones.php`; si el correo falla, la operación del usuario NO se interrumpe):

| Evento | Quién recibe |
|---|---|
| Crear cuenta | Usuario nuevo (bienvenida) |
| Solicitud de compra | Dueño del huerto (con teléfono/correo del comprador) + confirmación al comprador si dio correo + copia opcional (`copia_admin`) |
| Cambio de estado del pedido | Comprador (contactado / completado / cancelado) |
| Mensaje entre productores | Dueño del huerto destino; al pulsar *Responder* contesta directo al remitente (`Reply-To`) |
| Recuperar contraseña | Usuario |

**Recibir correos (opcional):** llena `correo_entrada` (IMAP, `imap.gmail.com`, 993, `ssl`) en `config.local.php`, activa `extension=imap` en `php.ini` y reinicia Apache. La bandeja se ve en **Correo** (solo administrador). Las respuestas de los usuarios llegan a la cuenta configurada (o al correo del remitente gracias a `Reply-To`).

**Error de certificado en XAMPP/Windows** ("No se pudo establecer TLS"): descarga `cacert.pem` de curl.se/ca, guárdalo y en `php.ini` pon `openssl.cafile="C:\ruta\cacert.pem"`. Solo como último recurso local usa `'verificar_ssl' => false`.

**Seguridad:** nunca pongas tu contraseña de correo en `config.php`; usa `config.local.php` y no lo subas a GitHub.
