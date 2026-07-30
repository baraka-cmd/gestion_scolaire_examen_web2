<?php
/**
 * Fichier : src/helpers/functions.php
 * Rôle    : Fonctions utilitaires globales du système
 */

defined('ACCESS') or die('Accès interdit');

// ============================================================
// FONCTIONS DE SÉCURITÉ
// ============================================================

/**
 * Échappe une chaîne pour affichage HTML (XSS)
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Génère un token CSRF
 */
function generateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION[CSRF_TOKEN_NAME] = $token;
    $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    return $token;
}

/**
 * Vérifie le token CSRF
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }

    $tokenTime = $_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0;
    if (time() - $tokenTime > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION[CSRF_TOKEN_NAME], $_SESSION[CSRF_TOKEN_NAME . '_time']);
        return false;
    }

    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Champ caché CSRF pour formulaires
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

// ============================================================
// FONCTIONS DE VALIDATION
// ============================================================

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone(string $phone): bool
{
    return (bool) preg_match('/^(\+243|0)?[0-9]{9}$/', $phone);
}

function isValidDate(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function isBetween($value, float $min, float $max): bool
{
    return is_numeric($value) && (float) $value >= $min && (float) $value <= $max;
}

function isBlank(?string $value): bool
{
    return trim($value ?? '') === '';
}

// ============================================================
// FONCTIONS DE FORMATAGE
// ============================================================

function formatDate(?string $date, string $format = 'd/m/Y'): string
{
    if (empty($date)) return '—';
    $d = new DateTime($date);
    return $d->format($format);
}

function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) return '—';
    $d = new DateTime($datetime);
    return $d->format('d/m/Y à H:i');
}

function formatMontant(?float $montant, string $devise = '$'): string
{
    if ($montant === null) return '—';
    return $devise . ' ' . number_format($montant, 2, ',', ' ');
}

function formatNom(?string $nom): string
{
    if (empty($nom)) return '—';
    return mb_convert_case(trim($nom), MB_CASE_TITLE, 'UTF-8');
}

function formatSexe(string $sexe): string
{
    return $sexe === 'M' ? 'Masculin' : 'Féminin';
}

function tronquer(?string $texte, int $longueur = 100): string
{
    if (empty($texte)) return '';
    if (mb_strlen($texte, 'UTF-8') <= $longueur) return e($texte);
    return e(mb_substr($texte, 0, $longueur, 'UTF-8')) . '...';
}

// ============================================================
// FONCTIONS DE REDIRECTION ET MESSAGES
// ============================================================

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function displayFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';

    $icons = [
        'success' => 'fa-circle-check',
        'error'   => 'fa-circle-xmark',
        'warning' => 'fa-triangle-exclamation',
        'info'    => 'fa-circle-info'
    ];

    $icon = $icons[$flash['type']] ?? 'fa-circle-info';

    return '
    <div class="alert alert-' . $flash['type'] . ' alert-dismissible" role="alert">
        <i class="fa-solid ' . $icon . '"></i>
        <span>' . e($flash['message']) . '</span>
        <button type="button" class="btn-close" aria-label="Fermer">&times;</button>
    </div>';
}

// ============================================================
// FONCTIONS D'AUTHENTIFICATION
// ============================================================

function isLoggedIn(): bool
{
    return isset($_SESSION['utilisateur_id']);
}

function hasRole(string|array $roles): bool
{
    if (!isLoggedIn()) return false;

    $userRole = $_SESSION['utilisateur_role'] ?? '';

    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }

    return $userRole === $roles;
}

function isAdmin(): bool
{
    return hasRole('Admin');
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
        redirect('/gestion_scolaire/login');
    }
}

function requireRole(string|array $roles): void
{
    requireLogin();

    if (!hasRole($roles)) {
        setFlash('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
        redirect('/gestion_scolaire/dashboard');
    }
}

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return mb_strtolower($text, 'UTF-8') ?: 'n-a';
}

function generateMatricule(int $id): string
{
    return 'EL-' . date('y') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function calculerAge(string $dateNaissance): int
{
    $naissance = new DateTime($dateNaissance);
    $aujourdhui = new DateTime();
    return $naissance->diff($aujourdhui)->y;
}

function jourEnFrancais(string $date): string
{
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $d = new DateTime($date);
    return $jours[(int) $d->format('w')];
}

function moisEnFrancais(int $mois): string
{
    $mois_arr = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
        4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
        10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $mois_arr[$mois] ?? '';
}

function badgeStatut(string $statut): string
{
    $couleurs = [
        'Actif'        => 'success',
        'Inactif'      => 'secondary',
        'Payé'         => 'success',
        'Partiel'      => 'warning',
        'Impayé'       => 'danger',
        'Présent'      => 'success',
        'Absent'       => 'danger',
        'Justifié'     => 'info',
        'Non justifié' => 'warning',
        'Passe'        => 'success',
        'Ajourné'      => 'warning',
        'Redouble'     => 'danger',
        'Exclu'        => 'dark',
        'Diplômé'      => 'primary',
        'Transféré'    => 'info',
        'Abandon'      => 'secondary'
    ];

    $couleur = $couleurs[$statut] ?? 'secondary';
    return '<span class="badge badge-' . $couleur . '">' . e($statut) . '</span>';
}

function isActive(string $url): string
{
    $current = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($current, $url) ? 'active' : '';
}

/**
 * Journalisation simplifiée
 */
function logActivity(string $action, ?string $table = null, ?int $recordId = null, ?string $description = null): void
{
    global $db;

    if (!isset($db)) return;

    try {
        $db->query(
            "INSERT INTO logs_activites (utilisateur_id, action, table_concernee, enregistrement_id, description, adresse_ip, navigateur)
             VALUES (:uid, :action, :table_name, :record_id, :description, :ip, :navigateur)",
            [
                'uid'         => $_SESSION['utilisateur_id'] ?? null,
                'action'      => $action,
                'table_name'  => $table,
                'record_id'   => $recordId,
                'description' => $description,
                'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'navigateur'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
            ]
        );
    } catch (Exception $e) {
        error_log('Erreur logActivity: ' . $e->getMessage());
    }
}

/**
 * Réponse JSON pour les requêtes AJAX
 */
function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Récupère la valeur d'un champ POST nettoyé
 */
function post(string $key, $default = null): ?string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Récupère la valeur d'un champ GET nettoyé
 */
function get(string $key, $default = null): ?string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Vérifie si la requête est AJAX
 */
function isAjax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

/**
 * Debug (développement uniquement)
 */
function dd(...$vars): void
{
    if (ENVIRONMENT !== 'development') return;

    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;margin:15px;border-radius:10px;font-size:13px;line-height:1.5;overflow:auto;max-height:500px;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n\n";
    }
    echo '</pre>';
    exit;
}

// ============================================================
// FONCTIONS DE PERMISSIONS (RACCOURCIS)
// ============================================================

/**
 * Vérifie si l'utilisateur a une permission
 */
function can(string $permission): bool
{
    return Security::getInstance()->can($permission);
}

/**
 * Exige une permission ou bloque l'accès
 */
function requirePermission(string $permission): void
{
    Security::getInstance()->requirePermission($permission);
}

/**
 * Vérifie si l'utilisateur peut accéder à un module
 */
function canAccessModule(string $module): bool
{
    return Security::getInstance()->canAccessModule($module);
}

/**
 * Vérifie si l'utilisateur peut créer
 */
function canCreate(string $module): bool
{
    return Security::getInstance()->canCreate($module);
}

/**
 * Vérifie si l'utilisateur peut modifier
 */
function canEdit(string $module): bool
{
    return Security::getInstance()->canEdit($module);
}

/**
 * Vérifie si l'utilisateur peut supprimer
 */
function canDelete(string $module): bool
{
    return Security::getInstance()->canDelete($module);
}