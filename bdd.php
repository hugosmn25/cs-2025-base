<?php
declare(strict_types=1);

/**
 * FICHIER UNIQUE (1 page) : création BDD audit + logger + exemples d'appels.
 *
 * Objectif (RGPD / conservation):
 * - Logs "réinitialisation mot de passe" : 3 mois (~92 jours)
 * - Logs "paiement accepté" (facturation) : 1 an (365 jours)
 *
 * Cette page contient :
 * 1) createAuditDatabase() : crée la BDD + table + user (à lancer 1 fois avec un compte root)
 * 2) auditLogPasswordReset() : log en BDD (3 mois)
 * 3) auditLogPaymentAccepted() : log en BDD (1 an)
 *
 * NB: la planification (cron) n'est pas demandée.
 */

/* =========================
   CONFIG
   ========================= */

// Compte MySQL admin (pour créer la BDD / user) - uniquement pour createAuditDatabase()
const MYSQL_ROOT_DSN  = 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4';
const MYSQL_ROOT_USER = 'root';
const MYSQL_ROOT_PASS = 'CHANGE_ROOT_PASSWORD';

// BDD audit (séparée)
const AUDIT_DB_NAME = 'cafe_audit';
const AUDIT_DB_DSN  = 'mysql:host=127.0.0.1;port=3306;dbname=cafe_audit;charset=utf8mb4';

// Compte applicatif audit (droits minimaux)
const AUDIT_DB_USER = 'audit_writer';
const AUDIT_DB_PASS = 'CHANGE_ME';

// Retention légale
const RETENTION_ACCESS_DAYS  = 92;   // 3 mois ~ 92j
const RETENTION_BILLING_DAYS = 365;  // 1 an

/* =========================
   HELPERS
   ========================= */

function pdo_audit(): PDO
{
    $pdo = new PDO(AUDIT_DB_DSN, AUDIT_DB_USER, AUDIT_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function client_ip(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function request_id(): string
{
    // Si ton app a déjà un req_id global, tu peux remplacer par $GLOBALS['req_id']
    return $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(16));
}

function retention_until_utc_date(int $days): string
{
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    return $now->add(new DateInterval('P' . $days . 'D'))->format('Y-m-d');
}

/* =========================
   1) CREATION BDD AUDIT (à lancer 1 fois)
   ========================= */

function createAuditDatabase(): void
{
    $pdo = new PDO(MYSQL_ROOT_DSN, MYSQL_ROOT_USER, MYSQL_ROOT_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // BDD + user + droits minimaux
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . AUDIT_DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("CREATE USER IF NOT EXISTS '" . AUDIT_DB_USER . "'@'%' IDENTIFIED BY '" . AUDIT_DB_PASS . "';");
    $pdo->exec("GRANT INSERT, SELECT ON " . AUDIT_DB_NAME . ".* TO '" . AUDIT_DB_USER . "'@'%';");
    $pdo->exec("FLUSH PRIVILEGES;");

    $pdo->exec("USE " . AUDIT_DB_NAME . ";");

    // Table d'audit (relationnelle)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_event (
          id_event BIGINT PRIMARY KEY AUTO_INCREMENT,
          ts_utc DATETIME NOT NULL,

          event_code VARCHAR(80) NOT NULL,
          event_label VARCHAR(255) NOT NULL,

          actor_user_id INT NULL,
          actor_role VARCHAR(32) NOT NULL,
          target_user_id INT NULL,

          result VARCHAR(16) NOT NULL,          -- SUCCESS | FAIL

          ip VARCHAR(45) NOT NULL,
          user_agent VARCHAR(255) NULL,
          request_id CHAR(32) NULL,

          context_json JSON NOT NULL,

          retention_until DATE NOT NULL,

          INDEX idx_ts (ts_utc),
          INDEX idx_event (event_code, ts_utc),
          INDEX idx_actor (actor_user_id, ts_utc),
          INDEX idx_target (target_user_id, ts_utc),
          INDEX idx_retention (retention_until)
        ) ENGINE=InnoDB;
    ");
}

/* =========================
   2) LOGGER (fonctions à appeler depuis tes contrôleurs)
   ========================= */

function audit_insert_event(PDO $pdo, array $row): void
{
    $stmt = $pdo->prepare("
        INSERT INTO audit_event (
          ts_utc, event_code, event_label,
          actor_user_id, actor_role,
          target_user_id,
          result,
          ip, user_agent, request_id,
          context_json,
          retention_until
        ) VALUES (
          :ts_utc, :event_code, :event_label,
          :actor_user_id, :actor_role,
          :target_user_id,
          :result,
          :ip, :user_agent, :request_id,
          :context_json,
          :retention_until
        )
    ");
    $stmt->execute($row);
}

/**
 * LOG: réinitialisation du mot de passe (commercial ou gestionnaire catalogue)
 * -> conservation 3 mois
 */
function auditLogPasswordReset(int $actorUserId, string $actorRole, int $targetUserId, string $result = 'SUCCESS'): void
{
    $pdo = pdo_audit();
    $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    audit_insert_event($pdo, [
        ':ts_utc'          => $nowUtc->format('Y-m-d H:i:s'),
        ':event_code'      => 'auth.password_reset',
        ':event_label'     => 'Réinitialisation du mot de passe',
        ':actor_user_id'   => $actorUserId ?: null,
        ':actor_role'      => $actorRole,
        ':target_user_id'  => $targetUserId,
        ':result'          => $result,
        ':ip'              => client_ip(),
        ':user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ':request_id'      => request_id(),
        ':context_json'    => json_encode([
            'action' => 'password_reset',
            'target_user_id' => $targetUserId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':retention_until' => retention_until_utc_date(RETENTION_ACCESS_DAYS),
    ]);
}

/**
 * LOG: déclaration d'un paiement comme accepté (commercial)
 * -> conservation 1 an (obligation comptable)
 */
function auditLogPaymentAccepted(int $actorUserId, string $actorRole, int $commandeId, string $result = 'SUCCESS'): void
{
    $pdo = pdo_audit();
    $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    audit_insert_event($pdo, [
        ':ts_utc'          => $nowUtc->format('Y-m-d H:i:s'),
        ':event_code'      => 'billing.payment_accepted',
        ':event_label'     => 'Paiement déclaré accepté',
        ':actor_user_id'   => $actorUserId ?: null,
        ':actor_role'      => $actorRole,
        ':target_user_id'  => null,
        ':result'          => $result,
        ':ip'              => client_ip(),
        ':user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ':request_id'      => request_id(),
        ':context_json'    => json_encode([
            'action' => 'payment_accepted',
            'commande_id' => $commandeId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':retention_until' => retention_until_utc_date(RETENTION_BILLING_DAYS),
    ]);
}

/* =========================
   3) EXEMPLES D'UTILISATION (à copier dans tes contrôleurs)
   ========================= */

/*
 // A) Quand un admin/commercial/gestionnaire catalogue réinitialise le MDP d'un utilisateur:
 auditLogPasswordReset(
     actorUserId: (int)($_SESSION['idUtilisateur'] ?? 0),
     actorRole: (string)($_SESSION['idCategorie_utilisateur'] ?? '0'),
     targetUserId: (int)$utilisateurCibleId,
     result: 'SUCCESS'
 );

 // B) Quand un commercial déclare un paiement "accepté" sur une commande:
 auditLogPaymentAccepted(
     actorUserId: (int)($_SESSION['idUtilisateur'] ?? 0),
     actorRole: (string)($_SESSION['idCategorie_utilisateur'] ?? '0'),
     commandeId: (int)$args['idCommande'],
     result: 'SUCCESS'
 );
*/

/* =========================
   OPTION: exécution en CLI
   ========================= */

if (PHP_SAPI === 'cli') {
    $cmd = $argv[1] ?? '';
    if ($cmd === 'init-db') {
        createAuditDatabase();
        echo "OK: BDD audit créée et table audit_event prête.\n";
        exit(0);
    }
    echo "Usage:\n";
    echo "  php audit_task_and_logger.php init-db   # crée la BDD audit + table + user\n";
    exit(0);
}