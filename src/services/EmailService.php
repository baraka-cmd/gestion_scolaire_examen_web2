<?php
/**
 * Fichier : src/services/EmailService.php
 * Rôle    : Service d'envoi d'emails via SMTP avec PHPMailer
 * Système : École primaire congolaise
 * Version : 1.1 (Corrigée pour utiliser app.php)
 */

defined('ACCESS') or die('Accès interdit');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Chargement sécurisé des classes PHPMailer
$phpMailerDir = SRC_PATH . 'PHPMailer' . DIRECTORY_SEPARATOR . 'PHPMailer-master' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists($phpMailerDir . 'Exception.php')) require_once $phpMailerDir . 'Exception.php';
    if (file_exists($phpMailerDir . 'PHPMailer.php')) require_once $phpMailerDir . 'PHPMailer.php';
    if (file_exists($phpMailerDir . 'SMTP.php'))      require_once $phpMailerDir . 'SMTP.php';
}

class EmailService
{
    private array $config;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Crée et configure une instance PHPMailer avec fallback sur les constantes app.php
     */
    private function createMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);

        // Récupération des identifiants (BBD d'abord, sinon constantes app.php)
        $smtpHost = !empty($this->config['smtp_host']) ? $this->config['smtp_host'] : (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com');
        $smtpUser = !empty($this->config['smtp_username']) ? $this->config['smtp_username'] : (defined('SMTP_USER') ? SMTP_USER : '');
        $smtpPass = !empty($this->config['smtp_password']) ? $this->config['smtp_password'] : (defined('SMTP_PASS') ? SMTP_PASS : '');
        $smtpPort = !empty($this->config['smtp_port']) ? (int)$this->config['smtp_port'] : (defined('SMTP_PORT') ? (int)SMTP_PORT : 587);

        $mailer->isSMTP();
        $mailer->Host       = $smtpHost;
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $smtpUser;
        $mailer->Password   = $smtpPass;
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port       = $smtpPort;
        $mailer->CharSet    = 'UTF-8';
        $mailer->setLanguage('fr');
        $mailer->Timeout    = 15;
        
        // Mode silencieux strict pour AJAX
        $mailer->SMTPDebug  = SMTP::DEBUG_OFF;

        // Contournement SSL local (XAMPP / Wamp)
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $httpHost   = $_SERVER['HTTP_HOST'] ?? '';

        $isLocal = in_array($remoteAddr, ['127.0.0.1', '::1'], true) 
                   || str_contains($httpHost, 'localhost')
                   || str_contains($httpHost, '127.0.0.1');

        if ($isLocal) {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        $emailSender = !empty($this->config['email']) ? $this->config['email'] : $smtpUser;
        $nomEcole    = defined('ECOLE_NOM') ? ECOLE_NOM : ($this->config['nom_ecole'] ?? 'École LA RÉFÉRENCE');
        
        $mailer->setFrom($emailSender, $nomEcole);

        return $mailer;
    }

    /**
     * Charge la configuration
     */
    private function loadConfig(): void
    {
        try {
            if (class_exists('Database')) {
                $db = Database::getInstance();
                $params = $db->fetchOne("SELECT * FROM parametres_ecole WHERE id = 1");
                if ($params) {
                    $this->config = $params;
                    return;
                }
            }
        } catch (Throwable $e) {
            error_log("Erreur chargement config email : " . $e->getMessage());
        }

        $this->config = [];
    }

    /**
     * Envoie un email simple
     */
    public function envoyer(string $destinataire, string $sujet, string $messageHtml, ?string $messageTexte = null): bool
    {
        try {
            $mailer = $this->createMailer();
            $mailer->addAddress($destinataire);
            $mailer->Subject = $sujet;
            $mailer->isHTML(true);
            $mailer->Body = $messageHtml;

            if ($messageTexte) {
                $mailer->AltBody = $messageTexte;
            }

            return $mailer->send();
        } catch (Throwable $e) {
            error_log("Erreur envoi email à {$destinataire}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email avec le template HTML de l'école
     */
    /**
 * Envoie un email avec le template HTML de l'école
 */
public function envoyerTemplate(string $destinataire, string $sujet, string $contenu): bool
{
    $nomEcole = defined('ECOLE_NOM') ? ECOLE_NOM : ($this->config['nom_ecole'] ?? 'École LA RÉFÉRENCE');
    $devise   = defined('ECOLE_DEVIS') ? ECOLE_DEVIS : 'Discipline - Travail - Excellence';
    $emailEcole = !empty($this->config['email']) ? $this->config['email'] : (defined('SMTP_USER') ? SMTP_USER : '');
    $telephoneEcole = $this->config['telephone'] ?? '';
    $adresseEcole = $this->config['adresse'] ?? '';

    // Déterminer la couleur selon le type de notification
    $couleurPrincipale = '#2c3e90'; // Bleu par défaut
    $iconeNotification = '📢';
    
    // Si le contenu contient un type de notification, on adapte les couleurs
    if (stripos($sujet, 'Urgence') !== false || stripos($sujet, 'urgent') !== false) {
        $couleurPrincipale = '#dc2626'; // Rouge
        $iconeNotification = '🚨';
    } elseif (stripos($sujet, 'Alerte') !== false || stripos($sujet, 'alert') !== false) {
        $couleurPrincipale = '#f59e0b'; // Orange
        $iconeNotification = '⚠️';
    } elseif (stripos($sujet, 'Rappel') !== false || stripos($sujet, 'rappel') !== false) {
        $couleurPrincipale = '#3b82f6'; // Bleu clair
        $iconeNotification = '🔔';
    } elseif (stripos($sujet, 'Succès') !== false || stripos($sujet, 'succes') !== false) {
        $couleurPrincipale = '#10b981'; // Vert
        $iconeNotification = '✅';
    } elseif (stripos($sujet, 'Information') !== false || stripos($sujet, 'info') !== false) {
        $couleurPrincipale = '#6366f1'; // Indigo
        $iconeNotification = '📌';
    }

    $dateEnvoi = date('d/m/Y à H:i');
    $anneeActuelle = date('Y');

    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($sujet) . '</title>
        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background-color: #f1f5f9;
                margin: 0;
                padding: 0;
            }
            .email-wrapper {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            }
            .email-header {
                background: linear-gradient(135deg, ' . $couleurPrincipale . ' 0%, ' . $this->ajusterCouleur($couleurPrincipale, 20) . ' 100%);
                padding: 35px 30px;
                text-align: center;
                color: #ffffff;
            }
            .email-header .ecole-nom {
                margin: 0 0 8px 0;
                font-size: 1.6rem;
                font-weight: 700;
                letter-spacing: 0.5px;
            }
            .email-header .ecole-devise {
                margin: 0 0 15px 0;
                font-size: 0.85rem;
                opacity: 0.9;
                font-style: italic;
            }
            .email-header .notification-icon {
                font-size: 3rem;
                margin-bottom: 10px;
                display: block;
            }
            .email-body {
                padding: 30px;
                color: #334155;
            }
            .email-subject {
                font-size: 1.3rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0 0 5px 0;
                padding-bottom: 15px;
                border-bottom: 2px solid #e2e8f0;
            }
            .email-date {
                font-size: 0.8rem;
                color: #94a3b8;
                margin: 5px 0 20px 0;
            }
            .email-content {
                font-size: 0.95rem;
                line-height: 1.8;
                color: #475569;
                margin: 20px 0;
            }
            .email-content p {
                margin: 0 0 15px 0;
            }
            .email-content ul, .email-content ol {
                margin: 10px 0;
                padding-left: 20px;
            }
            .email-content li {
                margin-bottom: 8px;
            }
            .email-divider {
                height: 1px;
                background-color: #e2e8f0;
                margin: 25px 0;
            }
            .email-important {
                background-color: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px 20px;
                margin: 20px 0;
                border-radius: 8px;
                font-size: 0.9rem;
                color: #92400e;
            }
            .email-important strong {
                color: #d97706;
            }
            .email-footer {
                background-color: #f8fafc;
                padding: 25px 30px;
                text-align: center;
                border-top: 1px solid #e2e8f0;
            }
            .email-footer .ecole-info {
                font-size: 0.85rem;
                color: #64748b;
                margin-bottom: 10px;
            }
            .email-footer .ecole-info strong {
                color: #334155;
            }
            .email-footer .copyright {
                font-size: 0.75rem;
                color: #94a3b8;
                margin-top: 15px;
            }
            .email-contact {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin: 15px 0;
                font-size: 0.85rem;
                color: #64748b;
            }
            .email-contact span {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            @media only screen and (max-width: 480px) {
                .email-wrapper {
                    margin: 10px;
                    border-radius: 12px;
                }
                .email-header {
                    padding: 25px 20px;
                }
                .email-body {
                    padding: 20px;
                }
                .email-footer {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-wrapper">
            <!-- HEADER -->
            <div class="email-header">
                <span class="notification-icon">' . $iconeNotification . '</span>
                <h1 class="ecole-nom">' . htmlspecialchars($nomEcole) . '</h1>
                <p class="ecole-devise">' . htmlspecialchars($devise) . '</p>
            </div>

            <!-- BODY -->
            <div class="email-body">
                <h2 class="email-subject">' . htmlspecialchars($sujet) . '</h2>
                <p class="email-date">📅 Envoyé le ' . $dateEnvoi . '</p>
                
                <div class="email-content">
                    ' . $contenu . '
                </div>
            </div>

            <!-- FOOTER -->
            <div class="email-footer">
                <div class="ecole-info">
                    <strong>' . htmlspecialchars($nomEcole) . '</strong>
                </div>';
                
    if (!empty($telephoneEcole) || !empty($emailEcole) || !empty($adresseEcole)) {
        $html .= '
                <div class="email-contact">';
        if (!empty($telephoneEcole)) {
            $html .= '
                    <span>📞 ' . htmlspecialchars($telephoneEcole) . '</span>';
        }
        if (!empty($emailEcole)) {
            $html .= '
                    <span>✉️ ' . htmlspecialchars($emailEcole) . '</span>';
        }
        if (!empty($adresseEcole)) {
            $html .= '
                    <span>📍 ' . htmlspecialchars($adresseEcole) . '</span>';
        }
        $html .= '
                </div>';
    }
    
    $html .= '
                <p style="font-size:0.85rem;color:#64748b;">
                    Ce message a été envoyé automatiquement par le système de gestion scolaire.<br>
                    Merci de ne pas répondre à cet email.
                </p>
                <p class="copyright">
                    &copy; ' . $anneeActuelle . ' ' . htmlspecialchars($nomEcole) . ' — Tous droits réservés.
                </p>
            </div>
        </div>
    </body>
    </html>';

    return $this->envoyer($destinataire, $sujet, $html);
}

/**
 * Ajuste une couleur hexadécimale (ajoute de la luminosité)
 */
private function ajusterCouleur(string $hex, int $pourcentage): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = min(255, $r + $pourcentage);
    $g = min(255, $g + $pourcentage);
    $b = min(255, $b + $pourcentage);
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) 
               . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) 
               . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
}
