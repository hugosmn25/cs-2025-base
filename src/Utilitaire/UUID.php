<?php
/**
 * Génère un UUID v4 (36 caractères) : xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
 */
function guidv4(?string $data = null): string
{
    $data = $data ?? random_bytes(16);
    if (strlen($data) !== 16) {
        throw new RuntimeException('guidv4: data must be 16 bytes');
    }

    // version 4
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    // variant RFC 4122
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Convertit UUID texte => BINARY(16) (pour stockage en BDD)
 */
function uuidToBin(string $uuid): string
{
    return hex2bin(str_replace('-', '', strtolower($uuid)));
}

/**
 * Exemple: mettre un uuid à chaque catégorie sans uuid
 * (colonne en BINARY(16))
 */
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cafe;charset=utf8mb4', 'user', 'pass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$ids = $pdo->query("SELECT idCategorie FROM CATEGORIE WHERE uuid IS NULL")->fetchAll(PDO::FETCH_COLUMN);

$upd = $pdo->prepare("UPDATE CATEGORIE SET uuid = :uuid WHERE idCategorie = :id");

foreach ($ids as $id) {
    $uuidTxt = guidv4();
    $upd->execute([
        ':uuid' => uuidToBin($uuidTxt), // BINARY(16)
        ':id'   => (int)$id,
    ]);
}