<?php

const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'app_auth';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/../src/Infrastructure/Database/PdoConnection.php';

function pdo(): \PDO
{
    $pdo = \App\Infrastructure\Database\PdoConnection::getInstance();
    ensure_schema($pdo);
    return $pdo;
}

function ensure_schema(\PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        name VARCHAR(190) NULL,
        avatar_url VARCHAR(255) NULL,
        role VARCHAR(50) NOT NULL DEFAULT "student",
        token_version INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_users_email (email),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS user_oauth_providers (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        provider VARCHAR(50) NOT NULL,
        provider_uid VARCHAR(190) NOT NULL,
        refresh_token TEXT NULL,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_provider_uid (provider, provider_uid),
        CONSTRAINT fk_provider_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS courses (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(120) NOT NULL,
        title VARCHAR(190) NOT NULL,
        description TEXT NULL,
        image_url VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_courses_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS modules (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        course_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        description TEXT NULL,
        order_index INT NOT NULL,
        content_url VARCHAR(255) NULL,
        pass_score INT NOT NULL DEFAULT 80,
        max_score INT NOT NULL DEFAULT 100,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_course_order (course_id, order_index),
        CONSTRAINT fk_module_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS enrollments (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        course_id BIGINT UNSIGNED NOT NULL,
        enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_user_course (user_id, course_id),
        CONSTRAINT fk_enrollment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS module_progress (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        enrollment_id BIGINT UNSIGNED NOT NULL,
        module_id BIGINT UNSIGNED NOT NULL,
        status ENUM("locked", "in_progress", "completed") NOT NULL DEFAULT "locked",
        best_score DECIMAL(5,2) NULL,
        last_score DECIMAL(5,2) NULL,
        last_attempt_at DATETIME NULL,
        attempts INT NOT NULL DEFAULT 0,
        completed_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_enrollment_module (enrollment_id, module_id),
        CONSTRAINT fk_progress_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
        CONSTRAINT fk_progress_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS exam_attempts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        module_progress_id BIGINT UNSIGNED NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        max_score DECIMAL(5,2) NOT NULL,
        passed TINYINT(1) NOT NULL DEFAULT 0,
        taken_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ix_attempt_progress (module_progress_id),
        CONSTRAINT fk_attempt_progress FOREIGN KEY (module_progress_id) REFERENCES module_progress(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    seed_courses($pdo);
}

function seed_courses(\PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $courses = [
            ['slug' => 'induccion-logistica', 'title' => 'Plan de inducción logística', 'description' => 'Programa base para nuevos ingresos en logística farmacéutica.', 'image_url' => null],
            ['slug' => 'atencion-al-cliente', 'title' => 'Atención y conducta con clientes', 'description' => 'Refuerza las mejores prácticas de atención al cliente.', 'image_url' => null],
        ];
        $courseIds = [];
        $stmt = $pdo->prepare('INSERT INTO courses (slug, title, description, image_url, created_at, updated_at) VALUES (:slug, :title, :description, :image_url, NOW(), NOW())');
        foreach ($courses as $course) {
            $stmt->execute([
                ':slug' => $course['slug'],
                ':title' => $course['title'],
                ':description' => $course['description'],
                ':image_url' => $course['image_url'],
            ]);
            $courseIds[$course['slug']] = (int) $pdo->lastInsertId();
        }

        $moduleStmt = $pdo->prepare('INSERT INTO modules (course_id, title, description, order_index, content_url, pass_score, max_score, created_at, updated_at) VALUES (:course_id, :title, :description, :order_index, :content_url, :pass_score, :max_score, NOW(), NOW())');
        $modules = [
            ['course' => 'induccion-logistica', 'title' => 'Fundamentos de la logística farmacéutica I', 'order' => 1],
            ['course' => 'induccion-logistica', 'title' => 'Fundamentos de la logística farmacéutica II', 'order' => 2],
            ['course' => 'induccion-logistica', 'title' => 'Fundamentos de la logística farmacéutica III', 'order' => 3],
            ['course' => 'induccion-logistica', 'title' => 'Conducta y atención al cliente', 'order' => 4],
            ['course' => 'atencion-al-cliente', 'title' => 'Bases de servicio al cliente', 'order' => 1],
            ['course' => 'atencion-al-cliente', 'title' => 'Gestión de casos especiales', 'order' => 2],
        ];

        foreach ($modules as $module) {
            $moduleStmt->execute([
                ':course_id' => $courseIds[$module['course']],
                ':title' => $module['title'],
                ':description' => 'Contenido e instrucciones del módulo.',
                ':order_index' => $module['order'],
                ':content_url' => null,
                ':pass_score' => 80,
                ':max_score' => 100,
            ]);
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function upsert_user(array $profile, ?string $refreshToken = null): array
{
    $pdo = pdo();

    $provider = 'google';
    $providerUid = $profile['sub'] ?? null;
    $email = $profile['email'] ?? null;
    $name = $profile['name'] ?? ($profile['given_name'] ?? null);
    $avatar = $profile['picture'] ?? null;

    if (!$providerUid) {
        throw new \RuntimeException('Perfil inválido: falta identificador del proveedor.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT u.*, p.id AS provider_id FROM user_oauth_providers p JOIN users u ON u.id = p.user_id WHERE p.provider = :provider AND p.provider_uid = :uid LIMIT 1');
        $stmt->execute([':provider' => $provider, ':uid' => $providerUid]);
        $row = $stmt->fetch();

        if ($row) {
            $updateUser = $pdo->prepare('UPDATE users SET email = :email, name = :name, avatar_url = :avatar, updated_at = NOW() WHERE id = :id');
            $updateUser->execute([
                ':email' => $email,
                ':name' => $name,
                ':avatar' => $avatar,
                ':id' => $row['id'],
            ]);

            $updateProvider = $pdo->prepare('UPDATE user_oauth_providers SET refresh_token = COALESCE(:refresh, refresh_token), last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
            $updateProvider->execute([
                ':refresh' => $refreshToken,
                ':id' => $row['provider_id'],
            ]);

            $stmt = $pdo->prepare('SELECT u.*, p.refresh_token, p.last_login_at FROM user_oauth_providers p JOIN users u ON u.id = p.user_id WHERE p.id = :id');
            $stmt->execute([':id' => $row['provider_id']]);
            $user = $stmt->fetch();
        } else {
            $existingUserId = null;
            if ($email) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                $existingUserId = $stmt->fetchColumn() ?: null;
            }

            if ($existingUserId) {
                $userId = (int) $existingUserId;
                $updateUser = $pdo->prepare('UPDATE users SET name = :name, avatar_url = :avatar, updated_at = NOW() WHERE id = :id');
                $updateUser->execute([
                    ':name' => $name,
                    ':avatar' => $avatar,
                    ':id' => $userId,
                ]);
            } else {
                $insertUser = $pdo->prepare('INSERT INTO users (email, name, avatar_url, role, token_version, created_at, updated_at) VALUES (:email, :name, :avatar, :role, 0, NOW(), NOW())');
                $insertUser->execute([
                    ':email' => $email,
                    ':name' => $name,
                    ':avatar' => $avatar,
                    ':role' => 'student',
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            $insertProvider = $pdo->prepare('INSERT INTO user_oauth_providers (user_id, provider, provider_uid, refresh_token, last_login_at, created_at, updated_at) VALUES (:user_id, :provider, :uid, :refresh, NOW(), NOW(), NOW())');
            $insertProvider->execute([
                ':user_id' => $userId,
                ':provider' => $provider,
                ':uid' => $providerUid,
                ':refresh' => $refreshToken,
            ]);

            $stmt = $pdo->prepare('SELECT u.*, p.refresh_token, p.last_login_at FROM user_oauth_providers p JOIN users u ON u.id = p.user_id WHERE p.id = :id');
            $stmt->execute([':id' => $pdo->lastInsertId()]);
            $user = $stmt->fetch();
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    if (!$user) {
        throw new \RuntimeException('No fue posible guardar al usuario.');
    }

    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'avatar_url' => $user['avatar_url'],
        'picture' => $user['avatar_url'],
        'role' => $user['role'],
        'token_version' => (int) $user['token_version'],
        'last_login_at' => $user['last_login_at'] ?? null,
        'refresh_token' => $user['refresh_token'] ?? null,
    ];
}
