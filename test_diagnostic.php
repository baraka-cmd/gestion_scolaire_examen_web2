<?php
/**
 * Script de diagnostic d'envoi d'email SMTP / PHPMailer
 */

// Si le fichier est dans public/test_diagnostic.php :
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../src/config/app.php')) {
    require_once __DIR__ . '/../src/config/app.php';
}

// 1. Inclusions des fichiers de configuration et PHPMailer
// Ajuste le chemin selon la structure de ton projet (Autoload Composer ou inclusion manuelle)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/src/config/app.php')) {
    require_once __DIR__ . '/src/config/app.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "<h2>🔍 Diagnostic de l'envoi SMTP</h2>";

// 2. ADRESSE DE TEST
$emailTest = 'barakantwali06@gmail.com';
echo "Target Email: <strong>{$emailTest}</strong><br><br>";

// 3. Configuration de PHPMailer
$mail = new PHPMailer(true);

try {
    // --- MODE DEBUG SMTP MAXIMAL ---
    // Affiche toute la conversation en direct avec le serveur SMTP
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; 
    
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='color: #222; font-family: monospace; background: #f8f9fa; border-left: 3px solid #2563eb; padding: 4px 8px; margin: 2px 0; font-size: 13px;'>[SMTP DEBUG] {$str}</div>";
    };

    // Paramètres SMTP (Vérifie que tes constantes sont bien définies dans app.php)
    $mail->isSMTP();
    $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = defined('SMTP_USER') ? SMTP_USER : 'ton_email_expediteur@gmail.com';
    $mail->Password   = defined('SMTP_PASS') ? SMTP_PASS : 'ton_mot_de_passe_app';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou PHPMailer::ENCRYPTION_SMTPS
    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $mail->CharSet    = 'UTF-8';

    // Expéditeur & Destinataire
    $expediteur = defined('SMTP_USER') ? SMTP_USER : 'ton_email_expediteur@gmail.com';
    $mail->setFrom($expediteur, 'École LA RÉFÉRENCE');
    $mail->addAddress($emailTest);
    $mail->addReplyTo($expediteur, 'École LA RÉFÉRENCE');

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test de diagnostic SMTP - ' . date('H:i:s');
    $mail->Body    = '<b>Ceci est un mail de test de diagnostic direct.</b>';
    $mail->AltBody = 'Ceci est un mail de test de diagnostic direct.';

    echo "<h3>1. Connexion au serveur SMTP en cours...</h3>";
    
    $mail->send();
    
    echo "<h3 style='color: #16a34a; margin-top: 20px;'>✅ SUCCÈS : Le serveur SMTP a ACCEPTÉ le mail !</h3>";
    echo "<p>Si <strong>{$emailTest}</strong> ne le voit pas dans la boîte de réception :</p>";
    echo "<ul>";
    echo "<li>Vérifie le dossier <strong>SPAM / Indésirables</strong>.</li>";
    echo "<li>Vérifie que l'adresse d'expédition n'a pas été restreinte par Gmail.</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h3 style='color: #dc2626; margin-top: 20px;'>❌ ÉCHEC : L'envoi a échoué !</h3>";
    echo "<strong>Erreur PHPMailer :</strong> " . $mail->ErrorInfo . "<br>";
    echo "<strong>Exception :</strong> " . $e->getMessage();
}
