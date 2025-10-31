<?php
/**
 * modelos/db.php
 * - Conexión PDO
 * - Creación de base de datos y tabla (ensure_schema)
 * - upsert_user() para Google OAuth
 */

/* ==========================
   CONFIG MYSQL (PDO)
   ========================== */
const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'app_auth';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/**
 * Retorna un PDO listo y con el esquema garantizado.
 */
function pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Crear DB si no existe
    $dsnNoDb = "mysql:host=".DB_HOST.";charset=".DB_CHARSET;
    $pdoNoDb = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdoNoDb->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` DEFAULT CHARACTER SET ".DB_CHARSET." COLLATE utf8mb4_unicode_ci");

    // Conectar a DB
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensure_schema($pdo);
    return $pdo;
}

/**
 * Crea tablas necesarias si no existen.
 */
function ensure_schema(PDO $pdo): void {
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users_social (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider         ENUM('google') NOT NULL DEFAULT 'google',
  provider_uid     VARCHAR(128)    NOT NULL,
  email            VARCHAR(190)    NULL,
  name             VARCHAR(190)    NULL,
  picture          VARCHAR(255)    NULL,
  refresh_token    TEXT            NULL,
  created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at    DATETIME        NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_provider_uid (provider, provider_uid),
  KEY ix_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $pdo->exec($sql);
}

/**
 * Inserta/actualiza usuario de Google y retorna el registro.
 */
function upsert_user(array $profile, ?string $refreshToken = null): array {
    $pdo = pdo();

    $provider     = 'google';
    $provider_uid = $profile['sub'] ?? null;
    $email        = $profile['email'] ?? null;
    $name         = $profile['name'] ?? ($profile['given_name'] ?? null);
    $picture      = $profile['picture'] ?? null;

    if (!$provider_uid) throw new Exception('Perfil inválido: falta sub (provider_uid).');

    $stmt = $pdo->prepare("SELECT * FROM users_social WHERE provider = :provider AND provider_uid = :provider_uid LIMIT 1");
    $stmt->execute([':provider' => $provider, ':provider_uid' => $provider_uid]);
    $existing = $stmt->fetch();

    if ($existing) {
        $sql = "UPDATE users_social
                SET email = :email, name = :name, picture = :picture,
                    last_login_at = NOW(), updated_at = NOW()"
                .($refreshToken ? ", refresh_token = :refresh_token" : "")
                ." WHERE id = :id";
        $params = [
            ':email' => $email,
            ':name' => $name,
            ':picture' => $picture,
            ':id' => $existing['id'],
        ];
        if ($refreshToken) $params[':refresh_token'] = $refreshToken;
        $pdo->prepare($sql)->execute($params);

        $stmt = $pdo->prepare("SELECT * FROM users_social WHERE id = :id");
        $stmt->execute([':id' => $existing['id']]);
        return $stmt->fetch();
    } else {
        $sql = "INSERT INTO users_social (provider, provider_uid, email, name, picture, refresh_token, last_login_at, created_at, updated_at)
                VALUES (:provider, :provider_uid, :email, :name, :picture, :refresh_token, NOW(), NOW(), NOW())";
        $pdo->prepare($sql)->execute([
            ':provider' => $provider,
            ':provider_uid' => $provider_uid,
            ':email' => $email,
            ':name' => $name,
            ':picture' => $picture,
            ':refresh_token' => $refreshToken,
        ]);
        $id = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM users_social WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
