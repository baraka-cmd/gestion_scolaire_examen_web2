<?php
/**
 * Fichier : src/services/GoogleAuthService.php
 * Rôle    : Gestion des requêtes REST vers Google OAuth 2.0
 */

defined('ACCESS') or die('Accès interdit');

class GoogleAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId     = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri  = GOOGLE_REDIRECT_URI;
    }

    /**
     * Génère l'URL d'autorisation Google avec protection CSRF (state token)
     */
        /**
     * Génère l'URL d'autorisation Google OAuth 2.0 avec protection CSRF
     */
    public function getAuthUrl(): string
    {
        // 1. Démarrer la session si ce n'est pas encore fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Générer le token anti-CSRF s'il n'existe pas
        if (empty($_SESSION['oauth2state'])) {
            $_SESSION['oauth2state'] = bin2hex(random_bytes(32));
        }

        // 3. Préparer les paramètres (avec trim pour éviter les espaces invisibles dans le client_id)
        $params = [
            'client_id'     => trim($this->clientId),
            'redirect_uri'  => trim($this->redirectUri),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state'         => $_SESSION['oauth2state'],
            'prompt'        => 'select_account'
        ];

        // 4. Construire l'URL propre et encodée
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }


    /**
     * Échange le code d'autorisation contre un Access Token
     */
    public function getAccessToken(string $code): ?string
    {
        $url = 'https://oauth2.googleapis.com/token';

        $params = [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Désactivé uniquement si environnement local WAMP/XAMPP sans CACert
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('Google OAuth cURL Error (Token) : ' . $error);
            return null;
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Récupère les informations du profil utilisateur via le token d'accès
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('Google OAuth cURL Error (UserInfo) : ' . $error);
            return null;
        }

        return json_decode($response, true);
    }
}
