<?php

// Prépare le certificat CA MySQL à partir de DB_SSL_CA_PEM puis teste la
// connexion à la base. Tout est écrit dans les logs de démarrage, préfixé par
// "DB-CHECK". Aucun mot de passe ni contenu de variable n'est affiché.
// Ne bloque jamais le démarrage (code de sortie toujours 0).

function say(string $message): void
{
    echo "DB-CHECK : $message\n";
}

$target = getenv('DB_CA_TARGET') ?: '/etc/ssl/db-ca.pem';
$caPath = getenv('MYSQL_ATTR_SSL_CA') ?: '';
$pem = getenv('DB_SSL_CA_PEM') ?: '';

if ($pem !== '' && $caPath === $target) {
    // Le copier-coller perd souvent les retours à la ligne (tout sur une ligne,
    // espaces à la place) : on reconstruit chaque certificat proprement.
    preg_match_all('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $pem, $found);

    $certificates = [];
    foreach ($found[1] as $body) {
        $base64 = preg_replace('/[^A-Za-z0-9+\/=]/', '', $body);
        if ($base64 !== '') {
            $certificates[] = "-----BEGIN CERTIFICATE-----\n".chunk_split($base64, 64, "\n")."-----END CERTIFICATE-----\n";
        }
    }

    if ($certificates === []) {
        say('ATTENTION : DB_SSL_CA_PEM ne contient aucun certificat (lignes BEGIN/END CERTIFICATE introuvables).');
    } else {
        file_put_contents($target, implode('', $certificates));
        foreach ($certificates as $index => $certificate) {
            $info = openssl_x509_parse($certificate);
            if ($info === false) {
                say('ATTENTION : certificat CA n°'.($index + 1).' illisible (contenu incomplet ou corrompu).');

                continue;
            }
            say(sprintf(
                'certificat CA n°%d lisible : sujet="%s", expire le %s',
                $index + 1,
                $info['subject']['CN'] ?? '?',
                date('Y-m-d', $info['validTo_time_t'])
            ));
        }
    }
} elseif ($caPath === '') {
    say('aucun certificat CA fourni (DB_SSL_CA_PEM vide) : connexion sans chiffrement vérifié.');
}

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
