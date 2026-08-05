<?php
defined('ACCESS') or die('Accès interdit');
require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
redirect('/gestion_scolaire/comptable/rapport-financier');