<?php
session_start();

$config_file = __DIR__ . '/includes/config.php';

// Juz zainstalowane
$already_installed = file_exists($config_file) && filesize($config_file) > 0;

$error   = '';
$success = false;
$log     = [];

if (!$already_installed && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $host        = trim($_POST['db_host']      ?? 'localhost');
    $dbname      = trim($_POST['db_name']      ?? 'fiszkers');
    $db_user     = trim($_POST['db_user']      ?? 'root');
    $db_pass     =      $_POST['db_pass']      ?? '';
    $admin_email = trim($_POST['admin_email']  ?? '');
    $admin_pass  =      $_POST['admin_pass']   ?? '';

    try {
        // Polaczenie z MySQL
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Baza danych
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        $log[] = ['ok', 'Baza danych "' . $dbname . '" gotowa'];

        // ── TABELE ────────────────────────────────────────────────────────────

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                first_name      VARCHAR(50)  NOT NULL,
                last_name       VARCHAR(50)  NOT NULL,
                email           VARCHAR(100) NOT NULL UNIQUE,
                password        VARCHAR(255) NOT NULL,
                is_active       TINYINT(1)   NOT NULL DEFAULT 0,
                is_admin        TINYINT(1)   NOT NULL DEFAULT 0,
                streak          INT          NOT NULL DEFAULT 0,
                last_activity   DATE                  DEFAULT NULL,
                created_at      DATETIME              DEFAULT CURRENT_TIMESTAMP,
                activation_code VARCHAR(64)           DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS flashcards (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                user_id    INT          NULL,
                pl_word    VARCHAR(100) NOT NULL,
                en_word    VARCHAR(100) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lessons (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                user_id    INT NOT NULL,
                correct    INT NOT NULL DEFAULT 0,
                incorrect  INT NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lesson_answers (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                lesson_id      INT          NOT NULL,
                pl_word        VARCHAR(100) NOT NULL,
                user_answer    VARCHAR(100) NOT NULL DEFAULT '',
                correct_answer VARCHAR(100) NOT NULL,
                is_correct     TINYINT(1)   NOT NULL DEFAULT 0,
                FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS achievements (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                code        VARCHAR(50)  NOT NULL UNIQUE,
                name        VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL DEFAULT '',
                created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_achievements (
                id             INT AUTO_INCREMENT PRIMARY KEY,
                user_id        INT NOT NULL,
                achievement_id INT NOT NULL,
                unlocked_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
                FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS hidden_word_history (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                user_id    INT NULL,
                word       VARCHAR(100) NOT NULL,
                is_guessed TINYINT(1)   NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $log[] = ['ok', 'Utworzono 7 tabel bazy danych'];

        // ── KONTO ADMINA ──────────────────────────────────────────────────────

        $admin_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$admin_email]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, is_active, is_admin, streak, last_activity)
                VALUES ('Admin', 'Root', ?, ?, 1, 1, 0, CURDATE())
            ");
            $stmt->execute([$admin_email, $admin_hash]);
            $log[] = ['ok', 'Konto administratora utworzone (' . $admin_email . ')'];
        } else {
            $log[] = ['info', 'Konto administratora juz istnieje — pominieto'];
        }

        // ── STARTER FISZKI ────────────────────────────────────────────────────

        $starterCards = [
            ['kot',      'cat'],     ['pies',     'dog'],      ['dom',      'house'],
            ['szkoła',   'school'],  ['stół',     'table'],    ['krzesło',  'chair'],
            ['okno',     'window'],  ['drzwi',    'door'],     ['samochód', 'car'],
            ['rower',    'bike'],    ['telefon',  'phone'],    ['komputer', 'computer'],
            ['książka',  'book'],    ['długopis', 'pen'],      ['miasto',   'city'],
            ['kwiat',    'flower'],  ['jedzenie', 'food'],     ['woda',     'water'],
            ['dziecko',  'child'],   ['człowiek', 'human'],    ['słońce',   'sun'],
            ['księżyc',  'moon'],    ['gwiazda',  'star'],     ['niebo',    'sky'],
            ['rzeka',    'river'],   ['las',      'forest'],   ['góra',     'mountain'],
            ['morze',    'sea'],     ['ptak',     'bird'],     ['ryba',     'fish'],
        ];

        $stmt = $pdo->prepare("INSERT INTO flashcards (user_id, pl_word, en_word) VALUES (NULL, ?, ?)");
        foreach ($starterCards as $c) {
            $stmt->execute([$c[0], $c[1]]);
        }
        $log[] = ['ok', 'Dodano ' . count($starterCards) . ' startowych fiszek globalnych'];

        // ── OSIAGNIECIA ───────────────────────────────────────────────────────

        $achievements = [
            ['first_lesson',    'Pierwszy krok',        'Ukończ pierwszą lekcję'],
            ['perfect_lesson',  'Perfekcja',             'Uzyskaj wynik 10/10 w lekcji'],
            ['lessons_5',       'Zapracowany',           'Ukończ łącznie 5 lekcji'],
            ['lessons_10',      'Weteran',               'Ukończ łącznie 10 lekcji'],
            ['lessons_25',      'Mistrz lekcji',         'Ukończ łącznie 25 lekcji'],
            ['streak_3',        'Regularny',             'Bądź aktywny przez 3 dni z rzędu'],
            ['streak_7',        'Tygodniowy wojownik',   'Bądź aktywny przez 7 dni z rzędu'],
            ['streak_14',       'Nieustępliwy',          'Bądź aktywny przez 14 dni z rzędu'],
            ['first_flashcard', 'Zbieracz słów',         'Dodaj swoją pierwszą fiszkę'],
            ['flashcards_20',   'Kolekcjoner',           'Miej co najmniej 20 fiszek'],
            ['win_hidden_word', 'Detektyw słów',         'Odgadnij słowo w minigrze Ukryte słowo'],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO achievements (code, name, description) VALUES (?, ?, ?)");
        foreach ($achievements as $a) {
            $stmt->execute($a);
        }
        $log[] = ['ok', 'Dodano ' . count($achievements) . ' osiagnięć do systemu'];

        // ── CONFIG.PHP ────────────────────────────────────────────────────────

        $configContent = "<?php\n"
            . "// includes/config.php - wygenerowany przez instalator\n"
            . "define('DB_HOST', '" . addslashes($host)    . "');\n"
            . "define('DB_NAME', '" . addslashes($dbname)  . "');\n"
            . "define('DB_USER', '" . addslashes($db_user) . "');\n"
            . "define('DB_PASS', '" . addslashes($db_pass) . "');\n";

        if (file_put_contents($config_file, $configContent) === false) {
            throw new Exception('Nie można zapisać includes/config.php — sprawdź uprawnienia katalogu.');
        }
        $log[] = ['ok', 'Zapisano plik includes/config.php'];

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
    <title>Instalator – Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        .form-control::placeholder { color: #555; }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.35);
            margin-bottom: .75rem; margin-top: 1.5rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.07);
        }

        .log-item {
            display: flex; align-items: center; gap: .6rem;
            padding: .5rem .75rem;
            border-radius: 8px;
            font-size: .875rem;
            margin-bottom: .3rem;
        }
        .log-item.ok   { background: rgba(34,197,94,.08);  color: #86efac; }
        .log-item.info { background: rgba(99,102,241,.08); color: #a5b4fc; }
        .log-item.err  { background: rgba(239,68,68,.1);   color: #fca5a5; }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

<div class="container d-flex justify-content-center align-items-start py-5">
<div class="w-100" style="max-width: 640px;">

    <!-- NAGLOWEK -->
    <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:rgba(99,102,241,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="ti ti-cards text-primary" style="font-size:1.75rem;"></i>
        </div>
        <h1 class="h4 fw-bold text-light mb-1">Instalator Fiszkers</h1>
        <p class="text-secondary small mb-0">Skonfiguruj bazę danych i konto administratora</p>
    </div>

    <?php if ($already_installed): ?>

        <!-- JUZ ZAINSTALOWANE -->
        <div class="card bg-dark border-secondary p-4 text-center" style="border-radius:16px;">
            <div style="width:56px;height:56px;background:rgba(251,191,36,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="ti ti-shield-check" style="font-size:1.4rem;color:#fbbf24;"></i>
            </div>
            <h2 class="h5 fw-semibold text-light mb-2">Aplikacja jest już zainstalowana</h2>
            <p class="text-secondary small mb-4">
                Plik <code class="text-primary">includes/config.php</code> już istnieje.<br>
                Aby zainstalować ponownie, usuń ten plik recznie.
            </p>
            <a href="login.php"
               class="btn fw-semibold d-inline-flex align-items-center gap-2 mx-auto"
               style="background:#6366f1;border:none;color:white;border-radius:10px;padding:.6rem 1.5rem;">
                <i class="ti ti-login"></i> Przejdź do logowania
            </a>
        </div>

    <?php elseif ($success): ?>

        <!-- SUKCES -->
        <div class="card bg-dark border-secondary p-4" style="border-radius:16px;">
            <div class="text-center mb-4">
                <div style="width:64px;height:64px;background:rgba(34,197,94,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <i class="ti ti-circle-check" style="font-size:1.75rem;color:#4ade80;"></i>
                </div>
                <h2 class="h5 fw-semibold text-light mb-1">Instalacja zakończona!</h2>
                <p class="text-secondary small mb-0">Wszystkie komponenty zostały skonfigurowane poprawnie.</p>
            </div>

            <div class="mb-4">
                <?php foreach ($log as $entry): ?>
                    <div class="log-item <?= $entry[0] ?>">
                        <i class="ti ti-<?= $entry[0] === 'ok' ? 'circle-check' : 'info-circle' ?>"></i>
                        <?= htmlspecialchars($entry[1]) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="login.php"
               class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
               style="background:#6366f1;border:none;color:white;border-radius:10px;">
                <i class="ti ti-login"></i> Przejdź do logowania
            </a>
        </div>

    <?php else: ?>

        <!-- FORMULARZ -->
        <div class="card bg-dark border-secondary p-4" style="border-radius:16px;">

            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-alert-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>

                <!-- BAZA DANYCH -->
                <div class="section-label">
                    <i class="ti ti-database"></i> Baza danych
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Host</label>
                        <input type="text" name="db_host"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
                               placeholder="localhost" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Nazwa bazy</label>
                        <input type="text" name="db_name"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($_POST['db_name'] ?? 'fiszkers') ?>"
                               placeholder="fiszkers" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Użytkownik</label>
                        <input type="text" name="db_user"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"
                               placeholder="root" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Hasło bazy</label>
                        <input type="password" name="db_pass"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               placeholder="Pozostaw puste dla XAMPP">
                    </div>
                </div>

                <!-- KONTO ADMINA -->
                <div class="section-label">
                    <i class="ti ti-shield-check"></i> Konto administratora
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1">Adres e-mail</label>
                        <input type="email" name="admin_email"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($_POST['admin_email'] ?? 'admin@admin.com') ?>"
                               placeholder="admin@admin.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary mb-1">Hasło administratora</label>
                        <input type="password" name="admin_pass"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               placeholder="Min. 8 znakow" required>
                    </div>
                </div>

                <!-- CO ZOSTANIE ZROBIONE -->
                <div class="rounded-3 p-3 mb-4"
                     style="background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.15);">
                    <p class="small text-secondary mb-2 fw-semibold">Instalator wykona automatycznie:</p>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ([
                            'Utworzy baze danych i 7 tabel',
                            'Doda konto administratora',
                            'Wgra 30 startowych fiszek globalnych',
                            'Doda 11 osiagniec do systemu',
                            'Zapisze plik includes/config.php',
                        ] as $item): ?>
                        <div class="d-flex align-items-center gap-2 small text-secondary">
                            <i class="ti ti-point-filled" style="color:#6366f1;font-size:.6rem;"></i>
                            <?= $item ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit"
                        class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                        style="background:#6366f1;border:none;color:white;border-radius:10px;">
                    <i class="ti ti-rocket"></i> Zainstaluj Fiszkers
                </button>

            </form>
        </div>

    <?php endif; ?>

</div>
</div>

</body>
</html>