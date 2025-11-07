<?php
session_start();

require_once __DIR__ . '/modelos/db.php';
require_once __DIR__ . '/src/autoload.php';

use App\Application\UseCase\CompleteCapsuleUseCase;
use App\Application\UseCase\GetCourseModulesUseCase;
use App\Application\UseCase\GetModuleDetailUseCase;
use App\Application\UseCase\ListCoursesUseCase;
use App\Application\UseCase\RegisterExamAttemptUseCase;
use App\Infrastructure\Persistence\Repository\PdoCapsuleProgressRepository;
use App\Infrastructure\Persistence\Repository\PdoCapsuleRepository;
use App\Infrastructure\Persistence\Repository\PdoCourseRepository;
use App\Infrastructure\Persistence\Repository\PdoEnrollmentRepository;
use App\Infrastructure\Persistence\Repository\PdoExamAttemptRepository;
use App\Infrastructure\Persistence\Repository\PdoModuleExamQuestionRepository;
use App\Infrastructure\Persistence\Repository\PdoModuleProgressRepository;
use App\Infrastructure\Persistence\Repository\PdoModuleRepository;

function scheme_host(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . $host;
}

function app_dir(): string
{
    return rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
}

function url_to(string $file): string
{
    return scheme_host() . app_dir() . '/' . ltrim($file, '/');
}

function add_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    $messages = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $messages;
}

if (empty($_SESSION['user'])) {
    header('Location: ' . url_to('index.php'));
    exit;
}

$user = $_SESSION['user'];
$userId = (int) ($user['id'] ?? 0);
$name = htmlspecialchars($user['name'] ?? 'Usuario');
$email = htmlspecialchars($user['email'] ?? '');
$picture = htmlspecialchars($user['avatar_url'] ?? ($user['picture'] ?? ''));
$logout = url_to('index.php?action=logout');
$isAdmin = ($user['role'] ?? '') === 'admin';

$pdo = pdo();
$courseRepo = new PdoCourseRepository($pdo);
$moduleRepo = new PdoModuleRepository($pdo);
$enrollmentRepo = new PdoEnrollmentRepository($pdo);
$moduleProgressRepo = new PdoModuleProgressRepository($pdo);
$capsuleRepo = new PdoCapsuleRepository($pdo);
$capsuleProgressRepo = new PdoCapsuleProgressRepository($pdo);
$examQuestionRepo = new PdoModuleExamQuestionRepository($pdo);
$examAttemptRepo = new PdoExamAttemptRepository($pdo);

$listCoursesUseCase = new ListCoursesUseCase($courseRepo, $moduleRepo, $enrollmentRepo, $moduleProgressRepo);
$getModulesUseCase = new GetCourseModulesUseCase(
    $courseRepo,
    $moduleRepo,
    $enrollmentRepo,
    $moduleProgressRepo,
    $capsuleRepo,
    $capsuleProgressRepo,
    $examQuestionRepo
);
$getModuleDetailUseCase = new GetModuleDetailUseCase(
    $courseRepo,
    $moduleRepo,
    $enrollmentRepo,
    $moduleProgressRepo,
    $capsuleRepo,
    $capsuleProgressRepo,
    $examQuestionRepo,
    $getModulesUseCase
);
$completeCapsuleUseCase = new CompleteCapsuleUseCase(
    $moduleRepo,
    $capsuleRepo,
    $enrollmentRepo,
    $moduleProgressRepo,
    $capsuleProgressRepo
);
$registerExamAttemptUseCase = new RegisterExamAttemptUseCase(
    $moduleRepo,
    $enrollmentRepo,
    $moduleProgressRepo,
    $examAttemptRepo,
    $capsuleRepo,
    $capsuleProgressRepo,
    $examQuestionRepo
);

$view = 'courses';
$selectedCourse = null;
$selectedModule = null;
$error = null;
$flashMessages = consume_flash();

$courseId = isset($_GET['course']) ? (int) $_GET['course'] : null;
$moduleId = isset($_GET['module']) ? (int) $_GET['module'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'complete_capsule') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $capsuleId = (int) ($_POST['capsule_id'] ?? 0);
        try {
            $completeCapsuleUseCase->execute($userId, $moduleId, $capsuleId);
            add_flash('success', 'Cápsula completada. Continúa con la siguiente.');
        } catch (\Throwable $e) {
            add_flash('danger', 'No fue posible marcar la cápsula: ' . $e->getMessage());
        }
        header('Location: ' . url_to('cursos.php?course=' . $courseId . '&module=' . $moduleId));
        exit;
    }

    if ($action === 'submit_exam') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $answersPost = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];
        $answers = [];
        foreach ($answersPost as $questionId => $value) {
            $answers[(int) $questionId] = $value === '1';
        }
        try {
            $progressDto = $registerExamAttemptUseCase->execute($userId, $moduleId, $answers);
            if ($progressDto->examPassed) {
                add_flash('success', '¡Felicidades! Acreditaste el módulo con una calificación de ' . number_format((float) $progressDto->lastScore, 2) . ' puntos.');
            } else {
                add_flash('warning', 'Tu calificación fue de ' . number_format((float) $progressDto->lastScore, 2) . ' puntos. Revisa el contenido y vuelve a intentar.');
            }
        } catch (\Throwable $e) {
            add_flash('danger', 'No fue posible registrar la evaluación: ' . $e->getMessage());
        }
        header('Location: ' . url_to('cursos.php?course=' . $courseId . '&module=' . $moduleId));
        exit;
    }
}

if ($courseId) {
    try {
        if ($moduleId) {
            $selectedModule = $getModuleDetailUseCase->execute($userId, $courseId, $moduleId);
            $view = 'module';
        } else {
            $selectedCourse = $getModulesUseCase->execute($userId, $courseId);
            $view = 'modules';
        }
    } catch (\Throwable $e) {
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
.modules-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}
.module-card {
  border-radius: 1.25rem;
  background: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(16,59,123,0.08);
  transition: transform .2s ease;
}
.module-card:hover {
  transform: translateY(-4px);
}
.module-card.locked {
  opacity: .6;
  pointer-events: none;
}
.module-card .badge {
  font-weight: 600;
}
.progress {
  height: .65rem;
  border-radius: 999px;
  background-color: rgba(16,59,123,0.12);
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
.module-actions .btn {
  border-radius: 999px;
  font-weight: 600;
}
.module-actions .btn.disabled,
.module-actions .btn:disabled {
  background: #adb5bd;
  border-color: #adb5bd;
}
.sidebar {
  min-width: 220px;
}
.sidebar .list-group-item {
  border: none;
  border-radius: .85rem;
  margin-bottom: .5rem;
  font-weight: 600;
}
.sidebar .list-group-item.active {
  background: linear-gradient(135deg, #113c7d, #1e58b3);
  border: none;
}
.capsule-card {
  border-radius: 1rem;
  background: #fff;
  padding: 1.75rem;
  box-shadow: 0 12px 30px rgba(16,59,123,0.08);
  margin-bottom: 1.5rem;
}
.exam-card {
  border-radius: 1rem;
  background: #fff;
  padding: 1.75rem;
  box-shadow: 0 12px 30px rgba(208,70,149,0.12);
}
.exam-card legend {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--fd-blue);
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
        <?php if ($isAdmin): ?>
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo htmlspecialchars(url_to('admin.php')); ?>"><i class="bi bi-gear"></i> Administrar contenido</a></li>
        <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
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
  <?php foreach ($flashMessages as $flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($flash['message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger">No fue posible cargar la información solicitada. Detalles: <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if ($view === 'courses'): ?>
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div>
        <h2 class="fw-bold text-primary mb-1">Tus cursos activos</h2>
        <p class="text-muted mb-0">Selecciona un curso para consultar el avance de sus módulos.</p>
      </div>
    </div>
    <div class="modules-grid">
      <?php foreach ($courses as $course): ?>
        <div class="module-card p-4">
          <div>
            <span class="badge bg-primary-subtle text-primary mb-2">Curso</span>
            <h3 class="h4 fw-bold text-primary"><?php echo htmlspecialchars($course->courseTitle); ?></h3>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($course->courseDescription ?? ''); ?></p>
          </div>
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-uppercase text-muted fw-semibold">Avance</small>
              <span class="fw-bold text-primary"><?php echo number_format($course->progressPercentage, 1); ?>%</span>
            </div>
            <div class="progress mb-3">
              <div class="progress-bar" role="progressbar" style="width: <?php echo (float) $course->progressPercentage; ?>%;" aria-valuenow="<?php echo (float) $course->progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <a class="btn btn-primary w-100" href="<?php echo htmlspecialchars(url_to('cursos.php?course=' . $course->courseId)); ?>">Ver módulos</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($view === 'modules' && $selectedCourse): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <a class="btn btn-link text-decoration-none px-0" href="<?php echo htmlspecialchars(url_to('cursos.php')); ?>"><i class="bi bi-arrow-left"></i> Volver a cursos</a>
        <h2 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($selectedCourse->courseTitle); ?></h2>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedCourse->courseDescription ?? ''); ?></p>
      </div>
      <div class="text-end">
        <div class="text-muted small text-uppercase fw-semibold">Avance del curso</div>
        <div class="display-6 fw-bold text-primary"><?php echo number_format($selectedCourse->progressPercentage, 1); ?>%</div>
        <div class="small text-muted"><?php echo (int) $selectedCourse->completedModules; ?> de <?php echo (int) $selectedCourse->totalModules; ?> módulos completados</div>
      </div>
    </div>

    <div class="modules-grid">
      <?php foreach ($selectedCourse->modules as $module): ?>
        <div class="module-card p-4 <?php echo $module->isAvailable ? '' : 'locked'; ?>">
          <div>
            <span class="badge bg-<?php echo $module->examPassed ? 'success' : ($module->isAvailable ? 'primary-subtle text-primary' : 'secondary'); ?> mb-2">
              <?php if ($module->examPassed): ?>Completado<?php elseif ($module->isAvailable): ?>Disponible<?php else: ?>Bloqueado<?php endif; ?>
            </span>
            <h3 class="h5 fw-bold text-primary mb-1">Módulo <?php echo (int) $module->orderIndex; ?> · <?php echo htmlspecialchars($module->title); ?></h3>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($module->description ?? ''); ?></p>
          </div>
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-uppercase text-muted fw-semibold">Avance</small>
              <span class="fw-bold text-primary"><?php echo number_format($module->progressPercentage, 1); ?>%</span>
            </div>
            <div class="progress mb-3">
              <div class="progress-bar" role="progressbar" style="width: <?php echo (float) $module->progressPercentage; ?>%;" aria-valuenow="<?php echo (float) $module->progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
              <span><?php echo (int) $module->capsulesCompleted; ?>/<?php echo (int) $module->capsulesTotal; ?> cápsulas</span>
              <span><?php echo (int) $module->attempts; ?> intentos de evaluación</span>
            </div>
            <?php if ($module->isAvailable): ?>
              <a class="btn btn-primary w-100" href="<?php echo htmlspecialchars(url_to('cursos.php?course=' . $selectedCourse->courseId . '&module=' . $module->moduleId)); ?>">
                <?php echo $module->examPassed ? 'Ver resultados' : ($module->progressPercentage > 0 ? 'Continuar módulo' : 'Iniciar módulo'); ?>
              </a>
            <?php else: ?>
              <button class="btn btn-secondary w-100" disabled>Completa el módulo anterior</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($view === 'module' && $selectedModule): ?>
    <div class="row g-4">
      <aside class="col-lg-3">
        <div class="sidebar">
          <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 mb-3" href="<?php echo htmlspecialchars(url_to('cursos.php?course=' . $selectedModule->courseId)); ?>">
            <i class="bi bi-arrow-left"></i> Regresar a módulos
          </a>
          <div class="list-group">
            <?php foreach ($selectedModule->capsules as $capsule): ?>
              <a class="list-group-item list-group-item-action <?php echo $capsule->status === 'completed' ? 'active' : ''; ?>" href="#capsule-<?php echo (int) $capsule->id; ?>">
                Cápsula <?php echo (int) $capsule->orderIndex; ?>
                <?php if ($capsule->status === 'completed'): ?>
                  <i class="bi bi-check-circle-fill ms-2"></i>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
            <a class="list-group-item list-group-item-action <?php echo $selectedModule->examPassed ? 'active' : ''; ?>" href="#module-exam">
              Evaluación final
            </a>
          </div>
        </div>
      </aside>
      <section class="col-lg-9">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
          <div>
            <span class="badge bg-primary-subtle text-primary mb-2">Curso: <?php echo htmlspecialchars($selectedModule->courseTitle); ?></span>
            <h2 class="fw-bold text-primary mb-1">Módulo <?php echo (int) $selectedModule->orderIndex; ?> · <?php echo htmlspecialchars($selectedModule->moduleTitle); ?></h2>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedModule->moduleDescription ?? ''); ?></p>
          </div>
          <div class="text-end">
            <div class="text-muted small text-uppercase fw-semibold">Avance del módulo</div>
            <div class="display-6 fw-bold text-primary"><?php echo number_format($selectedModule->progressPercentage, 1); ?>%</div>
            <div class="small text-muted"><?php echo (int) $selectedModule->capsulesCompleted; ?> de <?php echo (int) $selectedModule->capsulesTotal; ?> cápsulas</div>
          </div>
        </div>

        <?php foreach ($selectedModule->capsules as $capsule): ?>
          <article class="capsule-card" id="capsule-<?php echo (int) $capsule->id; ?>">
            <header class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h3 class="h4 fw-bold text-primary mb-1">Cápsula <?php echo (int) $capsule->orderIndex; ?> · <?php echo htmlspecialchars($capsule->title); ?></h3>
                <span class="badge bg-<?php echo $capsule->status === 'completed' ? 'success' : 'secondary'; ?>">
                  <?php echo $capsule->status === 'completed' ? 'Completada' : 'Pendiente'; ?>
                </span>
              </div>
              <?php if ($capsule->status !== 'completed'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="complete_capsule">
                  <input type="hidden" name="course_id" value="<?php echo (int) $selectedModule->courseId; ?>">
                  <input type="hidden" name="module_id" value="<?php echo (int) $selectedModule->moduleId; ?>">
                  <input type="hidden" name="capsule_id" value="<?php echo (int) $capsule->id; ?>">
                  <button type="submit" class="btn btn-outline-primary btn-sm">
                    Marcar como completada
                  </button>
                </form>
              <?php endif; ?>
            </header>
            <div class="capsule-body">
              <?php echo $capsule->bodyHtml ?? '<p class="text-muted">Contenido pendiente de configurar.</p>'; ?>
            </div>
          </article>
        <?php endforeach; ?>

        <section class="exam-card mt-4" id="module-exam">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <legend>Evaluación del módulo</legend>
              <p class="text-muted mb-0">Responde la evaluación para acreditar el módulo una vez que hayas completado todas las cápsulas.</p>
            </div>
            <div class="text-end">
              <span class="badge bg-<?php echo $selectedModule->examPassed ? 'success' : 'secondary'; ?> fw-semibold">
                <?php echo $selectedModule->examPassed ? 'Acreditado' : 'Pendiente'; ?>
              </span>
              <?php if ($selectedModule->lastAttemptAt): ?>
                <div class="small text-muted">Último intento: <?php echo htmlspecialchars($selectedModule->lastAttemptAt); ?></div>
              <?php endif; ?>
              <?php if ($selectedModule->bestScore !== null): ?>
                <div class="small text-muted">Mejor calificación: <?php echo number_format((float) $selectedModule->bestScore, 2); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!$selectedModule->examAvailable): ?>
            <div class="alert alert-info mb-0">Completa todas las cápsulas para habilitar la evaluación.</div>
          <?php else: ?>
            <form method="post" class="vstack gap-4">
              <input type="hidden" name="action" value="submit_exam">
              <input type="hidden" name="course_id" value="<?php echo (int) $selectedModule->courseId; ?>">
              <input type="hidden" name="module_id" value="<?php echo (int) $selectedModule->moduleId; ?>">
              <?php foreach ($selectedModule->questions as $question): ?>
                <fieldset class="border-bottom pb-3">
                  <legend class="h6 fw-semibold text-primary mb-3">Pregunta <?php echo (int) $question->id; ?></legend>
                  <p class="mb-3"><?php echo htmlspecialchars($question->questionText); ?></p>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[<?php echo (int) $question->id; ?>]" id="q<?php echo (int) $question->id; ?>-true" value="1" required>
                    <label class="form-check-label" for="q<?php echo (int) $question->id; ?>-true">Verdadero</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="answers[<?php echo (int) $question->id; ?>]" id="q<?php echo (int) $question->id; ?>-false" value="0">
                    <label class="form-check-label" for="q<?php echo (int) $question->id; ?>-false">Falso</label>
                  </div>
                  <?php if ($question->explanation): ?>
                    <p class="text-muted small mt-2 mb-0">Tip: <?php echo htmlspecialchars($question->explanation); ?></p>
                  <?php endif; ?>
                </fieldset>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary btn-lg align-self-start">Enviar evaluación</button>
            </form>
          <?php endif; ?>
        </section>
      </section>
    </div>
  <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
