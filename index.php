<?php
/**
 * Google Login + MySQL (Bootstrap) – INDEX
 * Estructura separada por carpetas:
 * - modelos/db.php  → conexión PDO, ensure_schema(), upsert_user()
 * - modelos/config.php → credenciales OAuth (NO versionar)
 * - css/login.css   → estilos
 * - clls/app.js     → JS (mínimo)
 *
 * Flujo:
 * - /index.php               → Renderiza login y crea state CSRF
 * - /index.php?code=...      → Callback OAuth, guarda/actualiza usuario y crea sesión
 * - /index.php?action=logout → Cierra la sesión
 */

session_start();

/* ==========================
   CARGA DE CONFIG (OAuth)
   ========================== */
$cfgPath = __DIR__ . '/modelos/config.php';
if (!file_exists($cfgPath)) {
    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Config faltante</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
          <main class="container py-5"><div class="alert alert-danger">
          Falta <code>modelos/config.php</code>. Copia <code>modelos/config.example.php</code> a <code>modelos/config.php</code> y coloca tus credenciales de Google.
          </div></main></body></html>';
    exit;
}
require_once $cfgPath; // define CLIENT_ID, CLIENT_SECRET, REDIRECT_URI

/* ==========================
   CONST OAUTH
   ========================== */
const AUTH_URL    = 'https://accounts.google.com/o/oauth2/v2/auth';
const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
const USERINFO    = 'https://openidconnect.googleapis.com/v1/userinfo';
const OAUTH_SCOPE = 'openid email profile';

/* ==========================
   HELPERS (HTTP/URL/CSRF)
   ========================== */
function scheme_host(): string {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . $host;
}
function app_dir(): string {
    // p.ej. /cursos
    return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
}
function url_to(string $file): string {
    // arma URL absoluta a un archivo en el mismo directorio del proyecto
    return scheme_host() . app_dir() . '/' . ltrim($file, '/');
}
function ensure_https_meta(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    return $https ? '<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">' : '';
}
function csrf_state(): string {
    if (empty($_SESSION['oauth2state'])) {
        $_SESSION['oauth2state'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['oauth2state'];
}
function clear_csrf_state(): void { unset($_SESSION['oauth2state']); }

function http_post_json(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err) throw new Exception('Error cURL: ' . $err);
    $json = json_decode($resp, true) ?? [];
    if ($code >= 400) throw new Exception('HTTP '.$code.' → '.($json['error_description'] ?? $json['error'] ?? 'Error de token'));
    return $json;
}
function http_get_json(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err) throw new Exception('Error cURL: ' . $err);
    $json = json_decode($resp, true) ?? [];
    if ($code >= 400) throw new Exception('HTTP '.$code.' → '.($json['error_description'] ?? $json['error'] ?? 'Error de API'));
    return $json;
}

/* ==========================
   CARGA DE MODELOS (BASE DE DATOS)
   ========================== */
require_once __DIR__ . '/modelos/db.php'; // define pdo(), ensure_schema(), upsert_user()

/* ==========================
   RUTAS
   ========================== */
$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . url_to('index.php'));
    exit;
}

if (isset($_GET['code'])) {
    // Callback OAuth
    $code  = $_GET['code'];
    $state = $_GET['state'] ?? '';
    if (!$state || !isset($_SESSION['oauth2state']) || !hash_equals($_SESSION['oauth2state'], $state)) {
        clear_csrf_state();
        http_response_code(400);
        render_error('Error de seguridad', 'El parámetro "state" no coincide o ha expirado. Intenta nuevamente.');
        exit;
    }
    clear_csrf_state();

    try {
        // Intercambio de código por token
        $token = http_post_json(TOKEN_URL, [
            'code'          => $code,
            'client_id'     => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'redirect_uri'  => REDIRECT_URI, // debe coincidir
            'grant_type'    => 'authorization_code',
        ]);

        $access_token  = $token['access_token']  ?? null;
        $refresh_token = $token['refresh_token'] ?? null; // puede venir la primera vez
        if (!$access_token) throw new Exception('No se recibió access_token.');

        // Obtener perfil
        $profile = http_get_json(USERINFO, ['Authorization: Bearer ' . $access_token]);

        // Persistencia en BD
        $dbUser = upsert_user($profile, $refresh_token);

        // Sesión mínima
        $_SESSION['user'] = [
            'id'      => $dbUser['id'],
            'ext_id'  => $profile['sub'] ?? null,
            'name'    => $dbUser['name'] ?? 'Usuario',
            'email'   => $dbUser['email'] ?? null,
            'picture' => $dbUser['picture'] ?? null,
        ];

        // 👉 Redirige a la página protegida
        header('Location: ' . url_to('cursos.php'));
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        render_error('No se pudo iniciar sesión', 'Descripción: ' . htmlspecialchars($e->getMessage()));
        exit;
    }
}

/* ==========================
   VISTA LOGIN
   ========================== */
render_login();
exit;

function render_login(): void {
    $state = csrf_state();

    // Construir URL de autorización
    $authUrl = AUTH_URL . '?' . http_build_query([
        'client_id'              => CLIENT_ID,
        'redirect_uri'           => REDIRECT_URI, // apunta a index.php (este archivo)
        'response_type'          => 'code',
        'scope'                  => OAUTH_SCOPE,
        'access_type'            => 'offline',
        'include_granted_scopes' => 'true',
        'state'                  => $state,
        'prompt'                 => 'select_account',
    ]);

    echo '<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
'.ensure_https_meta().'
<title>Iniciar sesión</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="'.htmlspecialchars(url_to('css/login.css')).'" rel="stylesheet">
</head>
<body>
<main class="container py-5">
  <div class="row justify-content-center align-items-center" style="min-height: 85vh;">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="text-center mb-4">
        <span class="brand-badge">Acceso Seguro</span>
        <h1 class="mt-3 fw-bold">Bienvenido</h1>
        <p class="helper mt-2">Usa tu cuenta de Google para iniciar sesión.</p>
      </div>

      <div class="card p-4 p-md-5">
        <a class="google-btn mb-3" href="'.htmlspecialchars($authUrl).'">
          <img class="google-logo" alt="" src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg">
          Continuar con Google
        </a>

        <div class="text-center helper mt-3">
          Al continuar aceptas nuestros términos y política de privacidad.
        </div>
      </div>

      <p class="text-center mt-4 helper">¿Problemas? <a class="small-link" href="#" onclick="location.reload()">Refresca</a>.</p>
    </div>
  </div>
</main>

<footer class="container pb-4 text-center small">
  © '.date('Y').' — Login UI con Bootstrap
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="'.htmlspecialchars(url_to('clls/app.js')).'"></script>
</body>
</html>';
}

function render_error(string $title, string $message): void {
    echo '<!doctype html>
<html lang="es" data-bs-theme="auto"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>'.htmlspecialchars($title).'</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="'.htmlspecialchars(url_to('css/login.css')).'" rel="stylesheet">
</head><body>
<main class="container py-5"><div class="row justify-content-center">
<div class="col-12 col-md-8 col-lg-6">
  <div class="alert alert-danger"><strong>'.htmlspecialchars($title).':</strong> '.nl2br(htmlspecialchars($message)).'</div>
  <a class="btn btn-primary" href="'.htmlspecialchars(url_to('index.php')).'">Volver</a>
</div></div></main>
</body></html>';
}
