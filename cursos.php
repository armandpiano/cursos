<?php
session_start();

function scheme_host(): string {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
  $scheme = $https ? 'https://' : 'http://';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme.$host;
}
function app_dir(): string { return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); }
function url_to(string $file): string { return scheme_host() . app_dir() . '/' . ltrim($file, '/'); }

/* =============== Validación de sesión =============== */
if (empty($_SESSION['user'])) { header('Location: ' . url_to('index.php')); exit; }

$user    = $_SESSION['user'];
$name    = htmlspecialchars($user['name'] ?? 'Usuario');
$email   = htmlspecialchars($user['email'] ?? '');
$picture = htmlspecialchars($user['picture'] ?? '');
$logout  = url_to('index.php?action=logout');

/* (Opcional) BD */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'app_auth';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';
$created = $last = null;
try {
  $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
  $stmt = $pdo->prepare("SELECT created_at, last_login_at FROM users_social WHERE id=:id");
  $stmt->execute([':id'=>$user['id']]);
  $row = $stmt->fetch(); $created = $row['created_at'] ?? null; $last = $row['last_login_at'] ?? null;
} catch(Throwable $e){}

/* ====== Rutas ====== */
$banner_img   = url_to('assets/img/BannerSuperior.webp');
$module_img   = url_to('assets/img/imgModulos.png');
$profile_url  = url_to('perfil.php');

/* ====== Módulos ====== */
$modules = [
  ['num'=>1,'title'=>'Fundamentos de la logística farmacéutica','desc'=>'Brindar a todos los colaboradores de nuevo ingreso una comprensión clara del rol, funciones y relevancia del área de Atención a Clientes dentro de FarmaDEC.','href'=>url_to('modulo1.php')],
  ['num'=>2,'title'=>'Fundamentos de la logística farmacéutica','desc'=>'Brindar a todos los colaboradores de nuevo ingreso una comprensión clara del rol, funciones y relevancia del área de Atención a Clientes dentro de FarmaDEC.','href'=>url_to('modulo2.php')],
  ['num'=>3,'title'=>'Fundamentos de la logística farmacéutica','desc'=>'Brindar a todos los colaboradores de nuevo ingreso una comprensión clara del rol, funciones y relevancia del área de Atención a Clientes dentro de FarmaDEC.','href'=>url_to('modulo3.php')],
  ['num'=>4,'title'=>'Conducta y atención al cliente','desc'=>'Brindar a todos los colaboradores de nuevo ingreso una comprensión clara del rol, funciones y relevancia del área de Atención a Clientes dentro de FarmaDEC.','href'=>url_to('modulo4.php')],
];
?>
<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cursos — Área protegida</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
:root{
  --fd-blue:#103b7b;
  --fd-magenta:#d04695;
}
.card{border:none;border-radius:1.25rem;box-shadow:0 10px 30px rgba(0,0,0,.08);}
.avatar{width:72px;height:72px;border-radius:50%;object-fit:cover}
.badge-soft{background:rgba(13,110,253,.1);color:#0d6efd;font-weight:600;padding:.35rem .6rem;border-radius:999px}

/* ===== Banner ===== */
.hero{
  position:relative; min-height: 100px !important;;
  background:#082449 url('<?php echo $banner_img; ?>') center/cover no-repeat;
}
.hero::before{content:'';position:absolute;inset:0;}
.profile-menu{position:absolute;right:1rem;top:1rem;z-index:3;}
.profile-btn{width:44px;height:44px;border-radius:50%;padding:0;overflow:hidden;background:#fff;border:none;box-shadow:0 6px 18px rgba(0,0,0,.25);}
.profile-initial{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#3a3a3a;}
@media (min-width:992px){ .hero{min-height: 100px !important;} }

/* ===== Tarjeta de módulo ===== */
.module-card{border-radius:1.25rem;overflow:hidden;background:#fff;height:100%;display:flex;flex-direction:column;justify-content:flex-start;box-shadow:0 10px 30px rgba(0,0,0,.08);}
.module-top img{display:block;width:100%;height:210px;object-fit:cover;}
.module-divider{height:8px;background:var(--fd-blue);}
.module-body{padding:1.25rem 1rem 1rem;text-align:center}
.module-kicker{color:var(--fd-blue);font-weight:900;letter-spacing:.04em;font-size:1.35rem;}
.module-sub{color:var(--fd-blue);font-weight:800;font-size:1rem;margin-top:.15rem;margin-bottom:.5rem;}
.module-desc{color:#6b6b6b;font-size:.98rem;line-height:1.6;}
.module-cta{padding:1rem;display:flex;justify-content:center}
.module-cta .btn{border-radius:999px;padding:.55rem 1.5rem;font-weight:700;min-width:112px;background:var(--fd-magenta);border-color:var(--fd-magenta);}
.module-cta .btn:hover{filter:brightness(.95)}
</style>
</head>
<body>

<!-- ===== Banner con menú de perfil ===== -->
<header class="hero">
  <div class="profile-menu">
    <div class="dropdown">
      <button class="profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <?php if ($picture): ?>
          <img src="<?php echo $picture; ?>" alt="Perfil" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <span class="profile-initial"><?php echo strtoupper($name[0] ?? 'U'); ?></span>
        <?php endif; ?>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($profile_url); ?>"><i class="bi bi-person"></i> Ver Perfil</a></li>
        <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?php echo htmlspecialchars($logout); ?>"><i class="bi bi-box-arrow-left"></i> Salir</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ===== Encabezado textual (fuera del banner) ===== -->
<section class="container text-center mt-2 mb-4">
  <h2 class="fw-bold" style="color:var(--fd-blue);font-size:2.4rem;">MÓDULOS</h2>
  <div class="fw-semibold" style="color:var(--fd-blue);">PLAN DE INDUCCIÓN FARMACÉUTICA</div>
  <div class="mt-2 mb-3" style="color:var(--fd-magenta);font-weight:700;">
    Aquí comienza tu formación: el camino hacia la excelencia logística.
  </div>
  <p class="text-muted mb-1">¡Tu aprendizaje comienza aquí!</p>
  <p class="mb-0">Cada módulo te acerca a dominar los procesos que hacen de <strong>FarmaDEC</strong> una empresa líder en transporte especializado.</p>
  <p class="text-muted">Revisa los temas disponibles, avanza paso a paso y sigue tu ruta de crecimiento.</p>
</section>

<main class="container pb-5">

  <!-- Tarjeta de usuario -->
  <!--<div class="card p-4 p-md-5 mb-5">
    <div class="d-flex align-items-center gap-3">
      <?php if ($picture): ?>
        <img src="<?php echo $picture; ?>" alt="Foto de perfil" class="avatar">
      <?php else: ?>
        <div class="avatar bg-light d-flex align-items-center justify-content-center"><span class="fw-bold"><?php echo strtoupper($name[0] ?? 'U'); ?></span></div>
      <?php endif; ?>
      <div>
        <div class="h4 m-0"><?php echo $name; ?></div>
        <div class="text-muted"><?php echo $email; ?></div>
        <div class="mt-2"><span class="badge-soft">Acceso válido</span></div>
      </div>
    </div>

    <hr class="my-4">
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="p-3 bg-light rounded-3">
          <div class="small text-muted">Fecha de alta</div>
          <div class="fw-semibold"><?php echo htmlspecialchars($created ?? '—'); ?></div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="p-3 bg-light rounded-3">
          <div class="small text-muted">Último acceso</div>
          <div class="fw-semibold"><?php echo htmlspecialchars($last ?? '—'); ?></div>
        </div>
      </div>
    </div>
  </div>-->

  <!-- Rejilla de módulos -->
  <div class="row g-4">
    <?php foreach ($modules as $m): ?>
      <div class="col-12 col-md-6 col-xl-3">
        <article class="module-card">
          <div class="module-top">
            <img src="<?php echo $module_img; ?>" alt="Imagen módulo">
          </div>
          <div class="module-divider"></div>
          <div class="module-body">
            <div class="module-kicker">MÓDULO <?php echo (int)$m['num']; ?></div>
            <div class="module-sub">
              <?php echo ($m['num'] <= 3) ? 'Fundamentos de la<br>logística farmacéutica' : 'Conducta y atención al cliente'; ?>
            </div>
            <p class="module-desc"><?php echo htmlspecialchars($m['desc']); ?></p>
          </div>
          <div class="module-cta">
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($m['href']); ?>">Ver</a>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
