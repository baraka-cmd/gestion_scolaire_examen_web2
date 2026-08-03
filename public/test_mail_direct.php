<?php
// Force l'affichage de toutes les erreurs PHP en direct
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>--- TEST SYSTEME SMTP EMAIL ---</h2>";

// Définition de la racine du projet
define('SRC_PATH', __DIR__ . '/../src/');

try {
    $fileToRequire = SRC_PATH . 'services/EmailService.php';
    if (!file_exists($fileToRequire)) {
        throw new Exception("Le fichier EmailService.php est introuvable au chemin : " . $fileToRequire);
    }
    
    require_once $fileToRequire;
    echo "<p>1. Chargement du service d'e-mail : <strong style='color:green;'>OK</strong></p>";

    $emailService = new EmailService();

    // ⚠️ Mets une vraie adresse e-mail ici pour recevoir le test
    $destinataire = "ton_email@gmail.com"; 
    $sujet = "Test SMTP Direct PHP";
    $message = "Ceci est un test direct pour isoler le problème SMTP.";

    echo "<p>2. Tentative d'envoi à <strong>{$destinataire}</strong>...</p>";

    $resultat = $emailService->envoyerTemplate($destinataire, $sujet, $message);

    if ($resultat) {
        echo "<h3 style='color:green; background:#d4edda; padding:10px; border:1px solid green;'>✅ SUCCESS ! L'e-mail a été envoyé avec succès !</h3>";
    } else {
        echo "<h3 style='color:red; background:#f8d7da; padding:10px; border:1px solid red;'>❌ ÉCHEC ! La méthode a renvoyé FALSE (Vérifie les logs ou le mot de passe SMTP).</h3>";
    }

} catch (Throwable $e) {
    echo "<h3 style='color:red; background:#f8d7da; padding:10px;'>🚨 ERREUR REÇUE EN DIRECT :</h3>";
    echo "<pre style='background:#222; color:#fff; padding:15px; border-radius:5px;'>";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . " (Ligne " . $e->getLine() . ")\n\n";
    echo "Trace :\n" . $e->getTraceAsString();
    echo "</pre>";
}
