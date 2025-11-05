<?php
session_start();

require_once __DIR__ . '/modelos/db.php';
require_once __DIR__ . '/src/autoload.php';

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

if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'admin')) {
    header('Location: ' . url_to('cursos.php'));
    exit;
}

$user = $_SESSION['user'];
$name = htmlspecialchars($user['name'] ?? 'Administración');
$email = htmlspecialchars($user['email'] ?? '');
$picture = htmlspecialchars($user['avatar_url'] ?? ($user['picture'] ?? ''));
$logout = url_to('index.php?action=logout');

$pdo = pdo();
$flashMessages = consume_flash();

function fetch_courses(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, title, slug, description FROM courses ORDER BY title');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_modules(PDO $pdo): array
{
    $sql = 'SELECT m.*, c.title AS course_title FROM modules m JOIN courses c ON c.id = m.course_id ORDER BY c.title, m.order_index';
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_capsules(PDO $pdo, int $moduleId): array
{
    $stmt = $pdo->prepare('SELECT * FROM capsules WHERE module_id = :module ORDER BY order_index');
    $stmt->execute([':module' => $moduleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_questions(PDO $pdo, int $moduleId): array
{
    $stmt = $pdo->prepare('SELECT * FROM module_exam_questions WHERE module_id = :module ORDER BY order_index');
    $stmt->execute([':module' => $moduleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create_course') {
            $stmt = $pdo->prepare('INSERT INTO courses (slug, title, description, image_url, created_at, updated_at) VALUES (:slug, :title, :description, :image_url, NOW(), NOW())');
            $stmt->execute([
                ':slug' => trim($_POST['slug'] ?? ''),
                ':title' => trim($_POST['title'] ?? ''),
                ':description' => trim($_POST['description'] ?? ''),
                ':image_url' => trim($_POST['image_url'] ?? '') ?: null,
            ]);
            add_flash('success', 'Curso creado correctamente.');
        } elseif ($action === 'create_module') {
            $stmt = $pdo->prepare('INSERT INTO modules (course_id, title, description, order_index, content_url, pass_score, max_score, created_at, updated_at) VALUES (:course_id, :title, :description, :order_index, :content_url, :pass_score, :max_score, NOW(), NOW())');
            $stmt->execute([
                ':course_id' => (int) ($_POST['course_id'] ?? 0),
                ':title' => trim($_POST['title'] ?? ''),
                ':description' => trim($_POST['description'] ?? ''),
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
                ':content_url' => trim($_POST['content_url'] ?? '') ?: null,
                ':pass_score' => (int) ($_POST['pass_score'] ?? 80),
                ':max_score' => (int) ($_POST['max_score'] ?? 100),
            ]);
            add_flash('success', 'Módulo creado correctamente.');
        } elseif ($action === 'update_module') {
            $stmt = $pdo->prepare('UPDATE modules SET title = :title, description = :description, order_index = :order_index, content_url = :content_url, pass_score = :pass_score, max_score = :max_score, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':id' => (int) ($_POST['module_id'] ?? 0),
                ':title' => trim($_POST['title'] ?? ''),
                ':description' => trim($_POST['description'] ?? ''),
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
                ':content_url' => trim($_POST['content_url'] ?? '') ?: null,
                ':pass_score' => (int) ($_POST['pass_score'] ?? 80),
                ':max_score' => (int) ($_POST['max_score'] ?? 100),
            ]);
            add_flash('success', 'Módulo actualizado.');
        } elseif ($action === 'create_capsule') {
            $stmt = $pdo->prepare('INSERT INTO capsules (module_id, title, body_html, video_url, order_index, created_at, updated_at) VALUES (:module_id, :title, :body_html, :video_url, :order_index, NOW(), NOW())');
            $stmt->execute([
                ':module_id' => (int) ($_POST['module_id'] ?? 0),
                ':title' => trim($_POST['title'] ?? ''),
                ':body_html' => trim($_POST['body_html'] ?? ''),
                ':video_url' => trim($_POST['video_url'] ?? '') ?: null,
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
            ]);
            add_flash('success', 'Cápsula creada correctamente.');
        } elseif ($action === 'update_capsule') {
            $stmt = $pdo->prepare('UPDATE capsules SET title = :title, body_html = :body_html, video_url = :video_url, order_index = :order_index, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':id' => (int) ($_POST['capsule_id'] ?? 0),
                ':title' => trim($_POST['title'] ?? ''),
                ':body_html' => trim($_POST['body_html'] ?? ''),
                ':video_url' => trim($_POST['video_url'] ?? '') ?: null,
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
            ]);
            add_flash('success', 'Cápsula actualizada.');
        } elseif ($action === 'delete_capsule') {
            $stmt = $pdo->prepare('DELETE FROM capsules WHERE id = :id');
            $stmt->execute([':id' => (int) ($_POST['capsule_id'] ?? 0)]);
            add_flash('success', 'Cápsula eliminada.');
        } elseif ($action === 'create_question') {
            $stmt = $pdo->prepare('INSERT INTO module_exam_questions (module_id, question_text, explanation, correct_answer, order_index, created_at, updated_at) VALUES (:module_id, :question_text, :explanation, :correct_answer, :order_index, NOW(), NOW())');
            $stmt->execute([
                ':module_id' => (int) ($_POST['module_id'] ?? 0),
                ':question_text' => trim($_POST['question_text'] ?? ''),
                ':explanation' => trim($_POST['explanation'] ?? '') ?: null,
                ':correct_answer' => (int) ($_POST['correct_answer'] ?? 1) ? 1 : 0,
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
            ]);
            add_flash('success', 'Pregunta creada correctamente.');
        } elseif ($action === 'update_question') {
            $stmt = $pdo->prepare('UPDATE module_exam_questions SET question_text = :question_text, explanation = :explanation, correct_answer = :correct_answer, order_index = :order_index, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':id' => (int) ($_POST['question_id'] ?? 0),
                ':question_text' => trim($_POST['question_text'] ?? ''),
                ':explanation' => trim($_POST['explanation'] ?? '') ?: null,
                ':correct_answer' => (int) ($_POST['correct_answer'] ?? 1) ? 1 : 0,
                ':order_index' => (int) ($_POST['order_index'] ?? 1),
            ]);
            add_flash('success', 'Pregunta actualizada.');
        } elseif ($action === 'delete_question') {
            $stmt = $pdo->prepare('DELETE FROM module_exam_questions WHERE id = :id');
            $stmt->execute([':id' => (int) ($_POST['question_id'] ?? 0)]);
            add_flash('success', 'Pregunta eliminada.');
        }
    } catch (\Throwable $e) {
        add_flash('danger', 'Ocurrió un error: ' . $e->getMessage());
    }

    header('Location: ' . url_to('admin.php'));
    exit;
}

$courses = fetch_courses($pdo);
$modules = fetch_modules($pdo);
?>
<!doctype html>
<html lang="es" data-bs-theme="auto">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administración de cursos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: #f5f7fb;
}
.navbar {
  background: linear-gradient(135deg, #071b3b, #113c7d);
}
.navbar-brand, .navbar-nav .nav-link, .navbar-text {
  color: #fff !important;
}
.card {
  border: none;
  border-radius: 1rem;
  box-shadow: 0 10px 26px rgba(16,59,123,0.08);
}
textarea.form-control {
  min-height: 140px;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?php echo htmlspecialchars(url_to('admin.php')); ?>">Panel de contenidos</a>
    <div class="d-flex align-items-center gap-3 ms-auto">
      <span class="navbar-text d-none d-sm-inline">Hola, <?php echo $name; ?></span>
      <a class="btn btn-outline-light btn-sm" href="<?php echo htmlspecialchars(url_to('cursos.php')); ?>"><i class="bi bi-grid"></i> Ver portal</a>
      <a class="btn btn-outline-light btn-sm" href="<?php echo htmlspecialchars($logout); ?>"><i class="bi bi-box-arrow-right"></i> Salir</a>
    </div>
  </div>
</nav>

<div class="container pb-5">
  <?php foreach ($flashMessages as $flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($flash['message']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card p-4 h-100">
        <h2 class="h5 fw-bold text-primary mb-3">Nuevo curso</h2>
        <form method="post" class="vstack gap-3">
          <input type="hidden" name="action" value="create_course">
          <div>
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Descripción</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div>
            <label class="form-label">Imagen (URL)</label>
            <input type="url" name="image_url" class="form-control">
          </div>
          <button type="submit" class="btn btn-primary">Guardar curso</button>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card p-4 mb-4">
        <h2 class="h5 fw-bold text-primary mb-3">Nuevo módulo</h2>
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="create_module">
          <div class="col-md-6">
            <label class="form-label">Curso</label>
            <select name="course_id" class="form-select" required>
              <option value="">Selecciona un curso</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Orden</label>
            <input type="number" name="order_index" class="form-control" value="1" min="1" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Título</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">URL adicional</label>
            <input type="url" name="content_url" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Puntaje aprobatorio</label>
            <input type="number" name="pass_score" class="form-control" value="80" min="1" max="100">
          </div>
          <div class="col-md-6">
            <label class="form-label">Puntaje máximo</label>
            <input type="number" name="max_score" class="form-control" value="100" min="1" max="100">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary">Guardar módulo</button>
          </div>
        </form>
      </div>

      <?php foreach ($modules as $module): ?>
        <div class="card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <span class="badge bg-primary-subtle text-primary mb-2"><?php echo htmlspecialchars($module['course_title']); ?></span>
              <h3 class="h5 fw-bold text-primary mb-1">Módulo <?php echo (int) $module['order_index']; ?> · <?php echo htmlspecialchars($module['title']); ?></h3>
              <p class="text-muted mb-0">Puntaje mínimo: <?php echo (int) $module['pass_score']; ?> / Máximo: <?php echo (int) $module['max_score']; ?></p>
            </div>
          </div>
          <form method="post" class="row g-3 mb-4">
            <input type="hidden" name="action" value="update_module">
            <input type="hidden" name="module_id" value="<?php echo (int) $module['id']; ?>">
            <div class="col-md-6">
              <label class="form-label">Título</label>
              <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($module['title']); ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Orden</label>
              <input type="number" name="order_index" class="form-control" value="<?php echo (int) $module['order_index']; ?>" min="1" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">URL adicional</label>
              <input type="url" name="content_url" class="form-control" value="<?php echo htmlspecialchars($module['content_url'] ?? ''); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea name="description" class="form-control"><?php echo htmlspecialchars($module['description'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Puntaje aprobatorio</label>
              <input type="number" name="pass_score" class="form-control" value="<?php echo (int) $module['pass_score']; ?>" min="1" max="100">
            </div>
            <div class="col-md-6">
              <label class="form-label">Puntaje máximo</label>
              <input type="number" name="max_score" class="form-control" value="<?php echo (int) $module['max_score']; ?>" min="1" max="100">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-outline-primary">Actualizar módulo</button>
            </div>
          </form>

          <div class="row g-4">
            <div class="col-md-6">
              <h4 class="h6 fw-bold text-primary">Cápsulas</h4>
              <form method="post" class="vstack gap-3 mb-3">
                <input type="hidden" name="action" value="create_capsule">
                <input type="hidden" name="module_id" value="<?php echo (int) $module['id']; ?>">
                <div>
                  <label class="form-label">Título</label>
                  <input type="text" name="title" class="form-control" required>
                </div>
                <div>
                  <label class="form-label">Orden</label>
                  <input type="number" name="order_index" class="form-control" min="1" value="1" required>
                </div>
                <div>
                  <label class="form-label">Video (URL)</label>
                  <input type="url" name="video_url" class="form-control">
                </div>
                <div>
                  <label class="form-label">Contenido HTML</label>
                  <textarea name="body_html" class="form-control" placeholder="Puedes incluir texto enriquecido en HTML"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Agregar cápsula</button>
              </form>

              <?php $capsules = fetch_capsules($pdo, (int) $module['id']); ?>
              <?php foreach ($capsules as $capsule): ?>
                <details class="mb-3">
                  <summary class="fw-semibold">Cápsula <?php echo (int) $capsule['order_index']; ?> · <?php echo htmlspecialchars($capsule['title']); ?></summary>
                  <form method="post" class="vstack gap-2 mt-2">
                    <input type="hidden" name="action" value="update_capsule">
                    <input type="hidden" name="capsule_id" value="<?php echo (int) $capsule['id']; ?>">
                    <div>
                      <label class="form-label">Título</label>
                      <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($capsule['title']); ?>" required>
                    </div>
                    <div>
                      <label class="form-label">Orden</label>
                      <input type="number" name="order_index" class="form-control" value="<?php echo (int) $capsule['order_index']; ?>" min="1" required>
                    </div>
                    <div>
                      <label class="form-label">Video (URL)</label>
                      <input type="url" name="video_url" class="form-control" value="<?php echo htmlspecialchars($capsule['video_url'] ?? ''); ?>">
                    </div>
                    <div>
                      <label class="form-label">Contenido HTML</label>
                      <textarea name="body_html" class="form-control"><?php echo htmlspecialchars($capsule['body_html'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-outline-primary btn-sm">Guardar cambios</button>
                      <button type="submit" class="btn btn-outline-danger btn-sm" form="delete-capsule-<?php echo (int) $capsule['id']; ?>" onclick="return confirm('¿Eliminar esta cápsula?');">Eliminar</button>
                    </div>
                  </form>
                  <form method="post" class="d-inline" id="delete-capsule-<?php echo (int) $capsule['id']; ?>">
                    <input type="hidden" name="action" value="delete_capsule">
                    <input type="hidden" name="capsule_id" value="<?php echo (int) $capsule['id']; ?>">
                  </form>
                </details>
              <?php endforeach; ?>
            </div>
            <div class="col-md-6">
              <h4 class="h6 fw-bold text-primary">Preguntas de evaluación</h4>
              <form method="post" class="vstack gap-3 mb-3">
                <input type="hidden" name="action" value="create_question">
                <input type="hidden" name="module_id" value="<?php echo (int) $module['id']; ?>">
                <div>
                  <label class="form-label">Orden</label>
                  <input type="number" name="order_index" class="form-control" min="1" value="1" required>
                </div>
                <div>
                  <label class="form-label">Pregunta</label>
                  <textarea name="question_text" class="form-control" required></textarea>
                </div>
                <div>
                  <label class="form-label">Explicación</label>
                  <textarea name="explanation" class="form-control" placeholder="Opcional"></textarea>
                </div>
                <div>
                  <label class="form-label">Respuesta correcta</label>
                  <select name="correct_answer" class="form-select">
                    <option value="1">Verdadero</option>
                    <option value="0">Falso</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary">Agregar pregunta</button>
              </form>

              <?php $questions = fetch_questions($pdo, (int) $module['id']); ?>
              <?php foreach ($questions as $question): ?>
                <details class="mb-3">
                  <summary class="fw-semibold">Pregunta <?php echo (int) $question['order_index']; ?></summary>
                  <form method="post" class="vstack gap-2 mt-2">
                    <input type="hidden" name="action" value="update_question">
                    <input type="hidden" name="question_id" value="<?php echo (int) $question['id']; ?>">
                    <div>
                      <label class="form-label">Orden</label>
                      <input type="number" name="order_index" class="form-control" value="<?php echo (int) $question['order_index']; ?>" min="1" required>
                    </div>
                    <div>
                      <label class="form-label">Pregunta</label>
                      <textarea name="question_text" class="form-control" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                    </div>
                    <div>
                      <label class="form-label">Explicación</label>
                      <textarea name="explanation" class="form-control"><?php echo htmlspecialchars($question['explanation'] ?? ''); ?></textarea>
                    </div>
                    <div>
                      <label class="form-label">Respuesta correcta</label>
                      <select name="correct_answer" class="form-select">
                        <option value="1" <?php echo ((int) $question['correct_answer'] === 1) ? 'selected' : ''; ?>>Verdadero</option>
                        <option value="0" <?php echo ((int) $question['correct_answer'] === 0) ? 'selected' : ''; ?>>Falso</option>
                      </select>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-outline-primary btn-sm">Guardar cambios</button>
                      <button type="submit" class="btn btn-outline-danger btn-sm" form="delete-question-<?php echo (int) $question['id']; ?>" onclick="return confirm('¿Eliminar esta pregunta?');">Eliminar</button>
                    </div>
                  </form>
                  <form method="post" class="d-inline" id="delete-question-<?php echo (int) $question['id']; ?>">
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?php echo (int) $question['id']; ?>">
                  </form>
                </details>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
