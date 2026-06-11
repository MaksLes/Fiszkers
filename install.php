<?php
session_start();

// --- Ścieżka do config.php ---
$config_file = __DIR__ . '/includes/config.php';

// Jeśli config istnieje i nie jest pusty → aplikacja już zainstalowana
if (file_exists($config_file) && filesize($config_file) > 0) {
    die("<h3 style='font-family:system-ui;padding:20px;'>Aplikacja jest już zainstalowana.<br>Usuń plik <code>includes/config.php</code>, aby zainstalować ponownie.</h3>");
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $host        = trim($_POST['db_host'] ?? 'localhost');
    $dbname      = trim($_POST['db_name'] ?? 'fiszkers');
    $user        = trim($_POST['db_user'] ?? 'root');
    $pass        = $_POST['db_pass'] ?? '';
    $admin_email = trim($_POST['admin_email'] ?? 'admin@admin.com');
    $admin_pass  = $_POST['admin_pass'] ?? 'admin123';

    try {
        // Połączenie z MySQL (bez wyboru bazy)
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Tworzenie bazy
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");

        // --- Tabela users ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                email VARCHAR(100) UNIQUE,
                password VARCHAR(255),
                is_active TINYINT(1) DEFAULT 0,
                is_admin TINYINT(1) DEFAULT 0,
                streak INT DEFAULT 0,
                last_activity DATE DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                activation_code VARCHAR(64) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela flashcards ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS flashcards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                pl_word VARCHAR(100),
                en_word VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela lessons ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lessons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                correct INT,
                incorrect INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela lesson_answers ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lesson_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lesson_id INT,
                pl_word VARCHAR(100),
                user_answer VARCHAR(100),
                correct_answer VARCHAR(100),
                is_correct TINYINT(1),
                FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela achievements ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS achievements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE,
                name VARCHAR(100),
                description VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela user_achievements ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_achievements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                achievement_id INT,
                unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Tabela hidden_word_history ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS hidden_word_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                word VARCHAR(100),
                is_guessed TINYINT(1),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // --- Dodanie konta administratora ---
        $admin_hashed = password_hash($admin_pass, PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin_email]);

        if (!$check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, is_active, is_admin, streak, last_activity)
                VALUES (?, ?, ?, ?, 1, 1, 0, CURDATE())
            ");
            $stmt->execute(['Admin', 'Root', $admin_email, $admin_hashed]);
        }

        // --- Dodanie przykładowych fiszek globalnych ---
        $starterCards = [
            ['kot', 'cat'], ['pies', 'dog'], ['dom', 'house'], ['szkoła', 'school'],
            ['stół', 'table'], ['krzesło', 'chair'], ['okno', 'window'], ['drzwi', 'door'],
            ['samochód', 'car'], ['rower', 'bike'], ['telefon', 'phone'], ['komputer', 'computer'],
            ['książka', 'book'], ['długopis', 'pen'], ['miasto', 'city'], ['kwiat', 'flower'],
            ['jedzenie', 'food'], ['woda', 'water'], ['dziecko', 'child'], ['człowiek', 'human'],
        ];

        $stmt = $pdo->prepare("INSERT INTO flashcards (user_id, pl_word, en_word) VALUES (NULL, ?, ?)");
        foreach ($starterCards as $c) {
            $stmt->execute([$c[0], $c[1]]);
        }

        // --- Zapis config.php ---
        $configContent = "<?php
// --- includes/config.php (generowany przez instalator) ---
define('DB_HOST', '" . addslashes($host) . "');
define('DB_NAME', '" . addslashes($dbname) . "');
define('DB_USER', '" . addslashes($user) . "');
define('DB_PASS', '" . addslashes($pass) . "');
";

        if (file_put_contents($config_file, $configContent) === false) {
            throw new Exception("Nie udało się utworzyć pliku includes/config.php. Sprawdź uprawnienia katalogu.");
        }

        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Instalator Fiszkers 2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container mt-5" style="max-width: 700px;">
    <h1 class="mb-4">Instalator Fiszkers 2.0</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Instalacja zakończona pomyślnie!<br>
            Możesz teraz przejść do logowania.
            <div class="mt-3">
                <a href="login.php" class="btn btn-light">Przejdź do logowania</a>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="mt-4">
            <h4>Dane bazy danych</h4>
            <div class="mb-3">
                <label class="form-label">Host bazy danych</label>
                <input type="text" name="db_host" class="form-control" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nazwa bazy danych</label>
                <input type="text" name="db_name" class="form-control" value="fiszkers" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Użytkownik bazy danych</label>
                <input type="text" name="db_user" class="form-control" value="root" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Hasło użytkownika bazy danych</label>
                <input type="password" name="db_pass" class="form-control" value="">
            </div>

            <hr class="border-secondary">

            <h4>Konto administratora</h4>
            <div class="mb-3">
                <label class="form-label">Email administratora</label>
                <input type="email" name="admin_email" class="form-control" value="admin@admin.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Hasło administratora</label>
                <input type="text" name="admin_pass" class="form-control" value="admin123" required>
            </div>

            <button type="submit" class="btn btn-success mt-2">Zainstaluj aplikację</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>