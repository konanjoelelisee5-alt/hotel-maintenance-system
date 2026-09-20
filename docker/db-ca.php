<?php

// Reconstruit le certificat CA MySQL à partir de DB_SSL_CA_PEM et l'écrit dans
// le fichier indiqué (DB_CA_TARGET, par défaut /etc/ssl/db-ca.pem).
// Le fichier n'est écrit QUE si au moins un certificat est lisible : l'appelant
// s'en sert pour savoir s'il peut faire confiance au certificat fourni.
// Messages préfixés "DB-CHECK" dans les logs. Aucun contenu de la variable
// n'est affiché, seulement sa taille et l'état des certificats.

function say(string $message): void
{
    echo "DB-CHECK : $message\n";
}

$target = getenv('DB_CA_TARGET') ?: '/etc/ssl/db-ca.pem';
$pem = getenv('DB_SSL_CA_PEM') ?: '';

@unlink($target);

say(sprintf(
    'DB_SSL_CA_PEM reçu : %d caractères, %d ligne(s), BEGIN %s, END %s',
    strlen($pem),
    substr_count(trim($pem), "\n") + 1,
    str_contains($pem, '-----BEGIN CERTIFICATE-----') ? 'présent' : 'ABSENT',
    str_contains($pem, '-----END CERTIFICATE-----') ? 'présent' : 'ABSENT'
));

// Le copier-coller perd souvent les retours à la ligne (tout sur une ligne,
// espaces à la place) : on reconstruit chaque certificat proprement.
preg_match_all('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $pem, $found);
$bodies = $found[1];

// Cas fréquent : seul le corps du certificat a été collé, sans les lignes BEGIN/END
if ($bodies === []) {
    $bare = preg_replace('/[^A-Za-z0-9+\/=]/', '', $pem);
    if (strlen($bare) >= 400) {
        $bodies = [$bare];
        say('aucune ligne BEGIN/END : le contenu est traité comme le corps d\'un certificat.');
    }
}

$readable = [];
foreach ($bodies as $index => $body) {
    $base64 = preg_replace('/[^A-Za-z0-9+\/=]/', '', $body);
    $certificate = "-----BEGIN CERTIFICATE-----\n".chunk_split($base64, 64, "\n")."-----END CERTIFICATE-----\n";
    $info = $base64 === '' ? false : openssl_x509_parse($certificate);

    if ($info === false) {
        say('ATTENTION : certificat CA n°'.($index + 1).' illisible (contenu incomplet ou corrompu).');

        continue;
    }

    $readable[] = $certificate;
    say(sprintf(
        'certificat CA n°%d lisible : sujet="%s", expire le %s',
        $index + 1,
        $info['subject']['CN'] ?? '?',
        date('Y-m-d', $info['validTo_time_t'])
    ));
}

if ($readable === []) {
    say('ATTENTION : aucun certificat CA utilisable dans DB_SSL_CA_PEM. Recopier le fichier ca.pem en entier, lignes BEGIN et END comprises.');
} else {
    file_put_contents($target, implode('', $readable));
}

exit(0);
