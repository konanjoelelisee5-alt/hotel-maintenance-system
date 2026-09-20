<?php

// Teste la connexion à la base avec exactement les réglages SSL que Laravel
// utilisera (variables MYSQL_ATTR_SSL_*), et écrit le résultat dans les logs de
// démarrage, préfixé par "DB-CHECK". Aucun mot de passe n'est affiché.
// Ne bloque jamais le démarrage (code de sortie toujours 0).

function say(string $message): void
{
    echo "DB-CHECK : $message\n";
}

$caPath = getenv('MYSQL_ATTR_SSL_CA') ?: '';

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15];
if ($caPath !== '' && is_file($caPath)) {
    $options[Pdo\Mysql::ATTR_SSL_CA] = $caPath;
}
if (filter_var(getenv('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ?: 'true', FILTER_VALIDATE_BOOLEAN) === false) {
    $options[Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

// Les avertissements PHP contiennent la vraie raison d'un échec TLS
$warnings = [];
set_error_handler(function (int $number, string $message) use (&$warnings) {
    $warnings[] = $message;

    return true;
});

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE')),
        getenv('DB_USERNAME') ?: '',
        getenv('DB_PASSWORD') ?: '',
        $options
    );
    $cipher = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM)[1] ?? '';
    say('connexion MySQL OK'.($cipher !== '' ? " (chiffrée : $cipher)" : ' (NON chiffrée)'));
} catch (Throwable $e) {
    say('ÉCHEC de connexion : '.$e->getMessage());
    foreach ($warnings as $warning) {
        say('détail PHP : '.$warning);
    }
}

exit(0);
