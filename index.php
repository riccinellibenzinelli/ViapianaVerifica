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
    try {
        if (isset($_POST['add_iscritto'])) {
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
    } catch (Exception $e) {
        $message = 'Errore: ' . $e->getMessage();
    }
}

try {
    $corsi = $pdo->query("SELECT * FROM Corsi ORDER BY nome_corso")->fetchAll();
    $membri = $pdo->query("SELECT * FROM Membri ORDER BY nome, cognome")->fetchAll();
    $istruttori = $pdo->query("SELECT * FROM Istruttori ORDER BY nome, cognome")->fetchAll();
} catch (Exception $e) {
    $corsi = [];
    $membri = [];
    $istruttori = [];
    if ($logged_in) {
        $message = 'Errore database: ' . $e->getMessage();
    }
}
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
                <?php
                try {
                    $stmt = $pdo->query("
                            SELECT i.nome, i.cognome, c.nome_corso, COUNT(ic.id_iscrizione) as num
                            FROM Istruttori i
                            JOIN Corsi c ON i.id_istruttore = c.id_istruttore
                            LEFT JOIN Iscrizioni_Corsi ic ON c.id_corso = ic.id_corso
                            GROUP BY c.id_corso
                            HAVING num >= 5
                            ORDER BY i.nome, num DESC
                        ");
                    $has_results = false;
                    while ($row = $stmt->fetch()):
                        if (!$has_results) {
                            echo '<table><tr><th>Istruttore</th><th>Corso</th><th>Iscritti</th></tr>';
                            $has_results = true;
                        }
                        ?>
                        <tr>
                            <td><?php echo $row['nome'] . ' ' . $row['cognome']; ?></td>
                            <td><?php echo $row['nome_corso']; ?></td>
                            <td><?php echo $row['num']; ?></td>
                        </tr>
                    <?php endwhile;
                    if ($has_results) {
                        echo '</table>';
                    } else {
                        echo '<p style="color: #666; font-style: italic;">Nessun corso con almeno 5 iscritti.</p>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">Errore: ' . $e->getMessage() . '</div>';
                }
                ?>

            <?php elseif ($action == 'list'): ?>
                <h2>Iscritti Corso</h2>
                <?php
                $corso_sel = isset($_GET['corso']) ? $_GET['corso'] : '';
                if ($corso_sel): ?>
                    <h3>Iscritti a <?php echo $corsi[array_search($corso_sel, array_column($corsi, 'id_corso'))]['nome_corso']; ?></h3>
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                                SELECT ic.id_iscrizione, m.nome, m.cognome
                                FROM Iscrizioni_Corsi ic
                                JOIN Membri m ON ic.id_membro = m.id_membro
                                WHERE ic.id_corso = ?
                                ORDER BY m.nome, m.cognome
                            ");
                        $stmt->execute([$corso_sel]);
                        $has_results = false;
                        while ($row = $stmt->fetch()):
                            if (!$has_results) {
                                echo '<table><tr><th>Nome</th><th>Cognome</th><th>Azione</th></tr>';
                                $has_results = true;
                            }
                            ?>
                            <tr>
                                <td><?php echo $row['nome']; ?></td>
                                <td><?php echo $row['cognome']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_iscrizione" value="<?php echo $row['id_iscrizione']; ?>">
                                        <select name="nuovo_corso" required>
                                            <option value="">Nuovo Corso</option>
                                            <?php foreach ($corsi as $corso): ?>
                                                <option value="<?php echo $corso['id_corso']; ?>">
                                                    <?php echo $corso['nome_corso']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="change_corso">Cambia</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile;
                        if ($has_results) {
                            echo '</table>';
                        } else {
                            echo '<p style="color: #666; font-style: italic;">Nessun iscritto per questo corso.</p>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="error">Errore: ' . $e->getMessage() . '</div>';
                    }
                    ?>
                <?php else: ?>
                    <form method="GET">
                        <input type="hidden" name="action" value="list">
                        <select name="corso" onchange="this.form.submit()">
                            <option value="">Scegli Corso</option>
                            <?php foreach ($corsi as $corso): ?>
                                <option value="<?php echo $corso['id_corso']; ?>">
                                    <?php echo $corso['nome_corso']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

            <?php elseif ($action == 'report'): ?>
                <h2>Report Completo</h2>
                <?php
                try {
                    $istruttori_corsi = $pdo->query("
                            SELECT i.id_istruttore, i.nome as i_nome, i.cognome as i_cognome, 
                                   c.id_corso, c.nome_corso, c.livello_difficolta, c.durata_minuti
                            FROM Istruttori i
                            LEFT JOIN Corsi c ON i.id_istruttore = c.id_istruttore
                            ORDER BY i.nome, i.cognome, c.nome_corso
                        ")->fetchAll(PDO::FETCH_ASSOC);

                    $iscrizioni = $pdo->query("
                            SELECT ic.id_corso, m.nome as m_nome, m.cognome as m_cognome, 
                                   ic.data_iscrizione, ic.orario_preferito
                            FROM Iscrizioni_Corsi ic
                            JOIN Membri m ON ic.id_membro = m.id_membro
                            ORDER BY ic.id_corso, m.nome, m.cognome
                        ")->fetchAll(PDO::FETCH_ASSOC);

                    $iscrizioni_per_corso = [];
                    foreach ($iscrizioni as $isc) {
                        $iscrizioni_per_corso[$isc['id_corso']][] = $isc;
                    }

                    $current_istr = '';
                    foreach ($istruttori_corsi as $row):
                        $istr = $row['i_nome'] . ' ' . $row['i_cognome'];

                        if ($istr != $current_istr):
                            if ($current_istr) echo '</div>';
                            $current_istr = $istr;
                            echo "<div class='istruttore'><h3>$istr</h3>";
                        endif;

                        if ($row['id_corso']):
                            echo "<h4>{$row['nome_corso']} (Livello: {$row['livello_difficolta']}, Durata: {$row['durata_minuti']} min)</h4>";

                            $iscritti_corso = isset($iscrizioni_per_corso[$row['id_corso']]) ? $iscrizioni_per_corso[$row['id_corso']] : [];

                            if (!empty($iscritti_corso)):
                                echo '<table><tr><th>Nome</th><th>Cognome</th><th>Data Isc.</th><th>Orario</th></tr>';
                                foreach ($iscritti_corso as $iscritto):
                                    echo "<tr><td>{$iscritto['m_nome']}</td><td>{$iscritto['m_cognome']}</td><td>{$iscritto['data_iscrizione']}</td><td>{$iscritto['orario_preferito']}</td></tr>";
                                endforeach;
                                echo '</table>';
                            else:
                                echo '<p style="color: #666; font-style: italic;">Nessun iscritto</p>';
                            endif;
                        endif;
                    endforeach;
                    if ($current_istr) echo '</div>';
                } catch (Exception $e) {
                    echo '<div class="error">Errore nel caricamento del report: ' . $e->getMessage() . '</div>';
                }
                ?>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</div>
</body>
</html>
