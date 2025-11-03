<?php
session_start();

require_once __DIR__ . '/modelos/db.php';
require_once __DIR__ . '/src/autoload.php';

use App\Application\UseCase\GetCourseModulesUseCase;
use App\Application\UseCase\ListCoursesUseCase;
use App\Infrastructure\Persistence\Repository\PdoCourseRepository;
use App\Infrastructure\Persistence\Repository\PdoEnrollmentRepository;
use App\Infrastructure\Persistence\Repository\PdoModuleProgressRepository;
use App\Infrastructure\Persistence\Repository\PdoModuleRepository;

function scheme_host(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . $host;
}
function app_dir(): string { return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); }
function url_to(string $file): string { return scheme_host() . app_dir() . '/' . ltrim($file, '/'); }

if (empty($_SESSION['user'])) {
    header('Location: ' . url_to('index.php'));
    exit;
}

$user = $_SESSION['user'];
$userId = (int) ($user['id'] ?? 0);
$name = htmlspecialchars($user['name'] ?? 'Usuario');
$email = htmlspecialchars($user['email'] ?? '');
$picture = htmlspecialchars($user['avatar_url'] ?? $user['picture'] ?? '');
$logout = url_to('index.php?action=logout');

$pdo = pdo();
$courseRepo = new PdoCourseRepository($pdo);
$moduleRepo = new PdoModuleRepository($pdo);
$enrollmentRepo = new PdoEnrollmentRepository($pdo);
$moduleProgressRepo = new PdoModuleProgressRepository($pdo);

$listCoursesUseCase = new ListCoursesUseCase($courseRepo, $moduleRepo, $enrollmentRepo, $moduleProgressRepo);
$getModulesUseCase = new GetCourseModulesUseCase($courseRepo, $moduleRepo, $enrollmentRepo, $moduleProgressRepo);

$view = 'courses';
$selectedCourse = null;
$error = null;

$courseId = isset($_GET['course']) ? (int) $_GET['course'] : null;
if ($courseId) {
    try {
        $selectedCourse = $getModulesUseCase->execute($userId, $courseId);
        $view = 'modules';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$courses = $listCoursesUseCase->execute($userId);
?>
<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cursos — Capacitación interna</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --fd-blue: #103b7b;
  --fd-magenta: #d04695;
  --fd-light: #f7f8fb;
}
body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--fd-light);
}
.card {
  border: none;
  border-radius: 1.25rem;
  box-shadow: 0 10px 30px rgba(0,0,0,.08);
}
.hero {
  position: relative;
  min-height: 160px;
  background: linear-gradient(135deg, #071b3b, #113c7d);
  color: #fff;
  display: flex;
  align-items: flex-end;
}
.hero .profile-menu {
  position: absolute;
  right: 1rem;
  top: 1rem;
}
.profile-btn {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  padding: 0;
  overflow: hidden;
  background: #fff;
  border: none;
  box-shadow: 0 6px 18px rgba(0,0,0,.25);
}
.profile-initial {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #3a3a3a;
}
.module-card {
  border-radius: 1.25rem;
  background: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(16,59,123,0.08);
}
.module-card.locked {
  opacity: .65;
}
.module-card .badge {
  font-weight: 600;
}
.progress {
  height: .65rem;
  border-radius: 999px;
  background-color: rgba(16,59,123,0.1);
}
.progress-bar {
  background-color: var(--fd-magenta);
}
.module-status {
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--fd-blue);
}
.modules-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}
.module-actions .btn {
  border-radius: 999px;
  font-weight: 600;
}
.module-actions .btn.disabled,
.module-actions .btn:disabled {
  background: #adb5bd;
  border-color: #adb5bd;
}
</style>
</head>
<body>
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
        <li class="px-3 py-2 small text-muted"><?php echo $name; ?><br><span class="text-body-secondary"><?php echo $email; ?></span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?php echo htmlspecialchars($logout); ?>"><i class="bi bi-box-arrow-left"></i> Salir</a></li>
      </ul>
    </div>
  </div>
  <div class="container py-5">
    <div class="col-lg-8">
      <span class="badge bg-light text-dark mb-3">Capacitación FarmaDEC</span>
      <h1 class="fw-bold">Tu ruta de aprendizaje</h1>
      <p class="lead mb-0">Consulta tus cursos activos, avanza módulo por módulo y monitorea tus calificaciones.</p>
    </div>
  </div>
</header>

<main class="container py-5">
  <?php if ($error): ?>
    <div class="alert alert-danger">No fue posible cargar la información del curso seleccionado. Detalles: <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if ($view === 'modules' && $selectedCourse): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <a class="btn btn-link text-decoration-none px-0" href="<?php echo htmlspecialchars(url_to('cursos.php')); ?>"><i class="bi bi-arrow-left"></i> Volver a cursos</a>
        <h2 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($selectedCourse->courseTitle); ?></h2>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedCourse->courseDescription ?? ''); ?></p>
      </div>
      <div class="text-end">
        <div class="fw-semibold text-uppercase small text-muted">Avance total</div>
        <div class="display-6 fw-bold text-primary"><?php echo number_format($selectedCourse->progressPercentage, 0); ?>%</div>
        <div class="small text-muted"><?php echo $selectedCourse->completedModules; ?> de <?php echo $selectedCourse->totalModules; ?> módulos completados</div>
      </div>
    </div>

    <div class="card p-4 mb-4">
      <div class="progress" role="progressbar" aria-valuenow="<?php echo (int) $selectedCourse->progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar" style="width: <?php echo min(100, $selectedCourse->progressPercentage); ?>%;"></div>
      </div>
    </div>

    <div class="modules-grid">
      <?php foreach ($selectedCourse->modules as $module): ?>
        <article class="module-card <?php echo $module->status === 'locked' ? 'locked' : ''; ?>">
          <div class="p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="module-status">Módulo <?php echo (int) $module->orderIndex; ?></span>
              <span class="badge <?php echo $module->status === 'completed' ? 'bg-success-subtle text-success' : ($module->status === 'in_progress' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary'); ?>">
                <?php if ($module->status === 'completed'): ?>Completado<?php elseif ($module->status === 'in_progress'): ?>En curso<?php else: ?>Bloqueado<?php endif; ?>
              </span>
            </div>
            <h3 class="h5 fw-bold text-primary"><?php echo htmlspecialchars($module->title); ?></h3>
            <p class="text-muted mb-3"><?php echo htmlspecialchars($module->description ?? 'Este módulo estará disponible cuando completes el anterior.'); ?></p>
            <div class="small text-muted">
              <div><strong>Calificación requerida:</strong> <?php echo (int) $module->passScore; ?> / <?php echo (int) $module->maxScore; ?></div>
              <div><strong>Mejor calificación:</strong> <?php echo $module->bestScore !== null ? number_format($module->bestScore, 0) . ' / ' . (int) $module->maxScore : '—'; ?></div>
              <div><strong>Última calificación:</strong> <?php echo $module->lastScore !== null ? number_format($module->lastScore, 0) . ' / ' . (int) $module->maxScore : '—'; ?></div>
              <div><strong>Último intento:</strong> <?php echo $module->lastAttemptAt ? htmlspecialchars($module->lastAttemptAt) : '—'; ?></div>
              <div><strong>Intentos realizados:</strong> <?php echo (int) $module->attempts; ?></div>
            </div>
          </div>
          <div class="module-actions p-4 pt-0">
            <?php if ($module->isAvailable): ?>
              <a class="btn btn-primary w-100" href="<?php echo htmlspecialchars($module->contentUrl ?? '#'); ?>" <?php echo $module->contentUrl ? '' : 'onclick="return false;"'; ?>>Ir al módulo</a>
            <?php else: ?>
              <button class="btn btn-secondary w-100" type="button" disabled>Completa el módulo anterior</button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="mb-5">
      <h2 class="fw-bold text-primary">Tus cursos disponibles</h2>
      <p class="text-muted mb-4">Selecciona un curso para inscribirte automáticamente y comenzar tu ruta de aprendizaje secuencial.</p>
      <div class="row g-4">
        <?php foreach ($courses as $course): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <article class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="mb-3">
                  <span class="badge bg-primary-subtle text-primary mb-2">Curso</span>
                  <h3 class="h5 fw-bold text-primary"><?php echo htmlspecialchars($course->title); ?></h3>
                  <p class="text-muted small mb-0"><?php echo htmlspecialchars($course->description ?? ''); ?></p>
                </div>
                <div class="mb-3">
                  <div class="small text-muted mb-1">Avance</div>
                  <div class="progress" role="progressbar" aria-valuenow="<?php echo (int) $course->progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?php echo min(100, $course->progressPercentage); ?>%;"></div>
                  </div>
                  <div class="small text-muted mt-2"><?php echo $course->completedModules; ?> de <?php echo $course->totalModules; ?> módulos completados</div>
                </div>
                <div class="mt-auto">
                  <a class="btn btn-outline-primary w-100" href="<?php echo htmlspecialchars(url_to('cursos.php?course=' . $course->id)); ?>">Ingresar</a>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
