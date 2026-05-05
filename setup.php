<?php

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'viapiana_gym';

try {
    $pdo = new PDO("mysql:host=" . $db_host, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $db_name);
    echo "✓ Database '$db_name' creato/verificato con successo.<br>";

    $pdo = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_file = __DIR__ . '/Verifica.sql';
    if (!file_exists($sql_file)) {
        die("Errore: file Verifica.sql non trovato!");
    }

    $sql_content = file_get_contents($sql_file);

    $statements = array_filter(array_map('trim', preg_split('/;(\s*\n|$)/', $sql_content)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    echo "✓ Dati importati con successo!<br>";
    echo "<br><strong>Setup completato!</strong><br>";
    echo "Puoi ora accedere all'applicazione:<br>";
    echo "<a href='login.php'>Accedi qui</a><br><br>";
    echo "<strong>Credenziali di accesso:</strong><br>";
    echo "Username: <strong>viapiana</strong><br>";
    echo "Password: <strong>verifica</strong>";

} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage();
}
?>

