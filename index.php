<?php
global $pdo;
session_start();
require 'config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === 'viapiana' && $password === 'verifica') {
        $_SESSION['user'] = $username;
    } else {
        $error = 'Credenziali errate!';
    }
}

$logged_in = isset($_SESSION['user']);
$action = isset($_GET['action']) ? $_GET['action'] : 'home';

$message = '';
if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_iscritto'])) {
        // Aggiungi iscritto
        $id_corso = $_POST['id_corso'];
        $id_membro = $_POST['id_membro'];
        $orario = $_POST['orario'];

        $stmt = $pdo->prepare("INSERT INTO Iscrizioni_Corsi (id_corso, id_membro, data_iscrizione, orario_preferito) VALUES (?, ?, CURDATE(), ?)");
        $stmt->execute([$id_corso, $id_membro, $orario]);
        $message = 'Iscritto aggiunto!';

    } elseif (isset($_POST['change_corso'])) {
        $id_iscrizione = $_POST['id_iscrizione'];
        $nuovo_corso = $_POST['nuovo_corso'];

        $stmt = $pdo->prepare("UPDATE Iscrizioni_Corsi SET id_corso = ? WHERE id_iscrizione = ?");
        $stmt->execute([$nuovo_corso, $id_iscrizione]);
        $message = 'Corso cambiato!';
    }
}

$corsi = $pdo->query("SELECT * FROM Corsi ORDER BY nome_corso")->fetchAll();
$membri = $pdo->query("SELECT * FROM Membri ORDER BY nome, cognome")->fetchAll();
$istruttori = $pdo->query("SELECT * FROM Istruttori ORDER BY nome, cognome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Palestra</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php if (!$logged_in): ?>
        <!-- LOGIN FORM -->
        <div class="login-box">
            <h1>Gestione Palestra</h1>
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Accedi</button>
            </form>
            <p>Username: viapiana<br>Password: verifica</p>
        </div>
    <?php else: ?>
        <!-- DASHBOARD -->
        <header>
            <h1>Gestione Palestra</h1>
            <p>Benvenuto, <?php echo $_SESSION['user']; ?>!</p>
            <a href="?logout" class="btn">Esci</a>
        </header>

        <nav>
            <a href="?action=home" class="<?php echo $action=='home'?'active':''; ?>">Home</a>
            <a href="?action=add" class="<?php echo $action=='add'?'active':''; ?>">Aggiungi Iscritto</a>
            <a href="?action=top" class="<?php echo $action=='top'?'active':''; ?>">Corsi Top</a>
            <a href="?action=list" class="<?php echo $action=='list'?'active':''; ?>">Iscritti Corso</a>
            <a href="?action=report" class="<?php echo $action=='report'?'active':''; ?>">Report</a>
        </nav>

        <main>
            <?php if ($message): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($action == 'home'): ?>
                <h2>Home</h2>
                <p>Scegli una funzionalità dal menu sopra.</p>

            <?php elseif ($action == 'add'): ?>
                <h2>Aggiungi Iscritto</h2>
                <form method="POST">
                    <select name="id_corso" required>
                        <option value="">Scegli Corso</option>
                        <?php foreach ($corsi as $corso): ?>
                            <option value="<?php echo $corso['id_corso']; ?>">
                                <?php echo $corso['nome_corso']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="id_membro" required>
                        <option value="">Scegli Membro</option>
                        <?php foreach ($membri as $membro): ?>
                            <option value="<?php echo $membro['id_membro']; ?>">
                                <?php echo $membro['nome'] . ' ' . $membro['cognome']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="time" name="orario" required>
                    <button type="submit" name="add_iscritto">Aggiungi</button>
                </form>

            <?php elseif ($action == 'top'): ?>
                <h2>Corsi con Più Iscritti (min 5)</h2>
                <table>
                    <tr><th>Istruttore</th><th>Corso</th><th>Iscritti</th></tr>
                    <?php
                    $stmt = $pdo->query("
                            SELECT i.nome, i.cognome, c.nome_corso, COUNT(ic.id_iscrizione) as num
                            FROM Istruttori i
                            JOIN Corsi c ON i.id_istruttore = c.id_istruttore
                            LEFT JOIN Iscrizioni_Corsi ic ON c.id_corso = ic.id_corso
                            GROUP BY c.id_corso
                            HAVING num >= 5
                            ORDER BY i.nome, num DESC
                        ");
                    while ($row = $stmt->fetch()): ?>
                        <tr>
                            <td><?php echo $row['nome'] . ' ' . $row['cognome']; ?></td>
                            <td><?php echo $row['nome_corso']; ?></td>
                            <td><?php echo $row['num']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>

            <?php elseif ($action == 'list'): ?>
                <h2>Iscritti Corso</h2>
                <?php
                $corso_sel = isset($_GET['corso']) ? $_GET['corso'] : '';
                if ($corso_sel): ?>
                    <h3>Iscritti a <?php echo $corsi[array_search($corso_sel, array_column($corsi, 'id_corso'))]['nome_corso']; ?></h3>
                    <table>
                        <tr><th>Nome</th><th>Cognome</th><th>Azione</th></tr>

    <?php endif; ?>
</div>
</body>
</html>

