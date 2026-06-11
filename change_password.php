<?php
require 'includes/auth.php';
require 'includes/db.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

$userStmt = $pdo->prepare("SELECT first_name, is_admin FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user       = $userStmt->fetch();
$first_name = htmlspecialchars($user['first_name']);
$is_admin   = $user['is_admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Wszystkie pola sa wymagane.';
    } elseif (strlen($new) < 8) {
        $error = 'Nowe haslo musi miec co najmniej 8 znakow.';
    } elseif ($new !== $confirm) {
        $error = 'Nowe hasla sie nie zgadzaja.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $error = 'Obecne haslo jest nieprawidlowe.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt    = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user_id]);
            $success = 'Haslo zostalo zmienione.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zmiana hasla - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        .form-control::placeholder { color: #555; }

        .icon-wrap {
            width: 56px; height: 56px;
            background: rgba(99,102,241,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }

        .strength-bar {
            height: 4px;
            border-radius: 99px;
            background: rgba(255,255,255,.08);
            overflow: hidden;
            margin-top: 6px;
        }
        .strength-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .3s ease, background .3s ease;
            width: 0%;
        }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(15,15,25,.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,.07); position: sticky; top: 0; z-index: 100;">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <i class="ti ti-cards text-primary" style="font-size: 1.3rem;"></i>
            Fiszkers
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="flashcards.php">
                        <i class="ti ti-cards"></i> Fiszki
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="lesson.php">
                        <i class="ti ti-book"></i> Lekcja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="my_lessons.php">
                        <i class="ti ti-list-check"></i> Moje lekcje
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="hidden_word.php">
                        <i class="ti ti-puzzle"></i> Ukryte slowo
                    </a>
                </li>
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1 text-warning" href="admin/admin_panel.php">
                        <i class="ti ti-shield-check"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <a href="profile.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <div style="width:34px;height:34px;background:rgba(99,102,241,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:600;color:#a5b4fc;">
                        <?= strtoupper(mb_substr($first_name, 0, 1)) ?>
                    </div>
                    <span class="text-light small"><?= $first_name ?></span>
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="ti ti-logout"></i> Wyloguj
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- FORMULARZ -->
<div class="container d-flex justify-content-center align-items-start py-5">
    <div class="card bg-dark border-secondary shadow-lg p-4 w-100" style="max-width: 460px;">

        <div class="text-center mb-4">
            <div class="icon-wrap">
                <i class="ti ti-lock text-primary" style="font-size: 22px;"></i>
            </div>
            <h1 class="h5 fw-semibold text-light mb-1">Zmiana hasla</h1>
            <p class="text-secondary small mb-0">Wprowadz obecne haslo i ustaw nowe</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success rounded-3 small d-flex align-items-center gap-2 mb-3">
                <i class="ti ti-circle-check"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger rounded-3 small d-flex align-items-center gap-2 mb-3">
                <i class="ti ti-alert-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>

            <div class="mb-3">
                <label for="current_password" class="form-label small text-secondary mb-1">
                    Obecne haslo
                </label>
                <input type="password"
                       class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                       id="current_password"
                       name="current_password"
                       placeholder="Twoje aktualne haslo"
                       required>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label small text-secondary mb-1">
                    Nowe haslo
                </label>
                <input type="password"
                       class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                       id="new_password"
                       name="new_password"
                       placeholder="Minimum 8 znakow"
                       required>
                <div class="strength-bar mt-2">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div id="strengthLabel" class="mt-1" style="font-size:.72rem;color:rgba(255,255,255,.3);min-height:16px;"></div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label small text-secondary mb-1">
                    Powtorz nowe haslo
                </label>
                <input type="password"
                       class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                       id="confirm_password"
                       name="confirm_password"
                       placeholder="Wpisz nowe haslo ponownie"
                       required>
                <div id="matchLabel" class="mt-1" style="font-size:.72rem;min-height:16px;"></div>
            </div>

            <button type="submit"
                    class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                    style="background:#6366f1;border:none;color:white;border-radius:10px;">
                <i class="ti ti-lock-check"></i> Zmien haslo
            </button>

        </form>

        <p class="text-center small text-secondary mt-3 mb-0">
            <a href="profile.php" class="text-secondary text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left" style="font-size:.85rem;"></i> Wróc do profilu
            </a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var newInput     = document.getElementById('new_password');
var confirmInput = document.getElementById('confirm_password');
var fill         = document.getElementById('strengthFill');
var strengthLbl  = document.getElementById('strengthLabel');
var matchLbl     = document.getElementById('matchLabel');

function checkStrength(val) {
    var score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    return score;
}

newInput.addEventListener('input', function() {
    var val   = newInput.value;
    var score = checkStrength(val);

    var widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
    var colors = ['transparent', '#f87171', '#fb923c', '#fbbf24', '#4ade80', '#4ade80'];
    var labels = ['', 'Bardzo slabe', 'Slabe', 'Srednie', 'Silne', 'Bardzo silne'];
    var lColors = ['', '#f87171', '#fb923c', '#fbbf24', '#4ade80', '#4ade80'];

    fill.style.width      = val.length ? widths[score] : '0%';
    fill.style.background = colors[score];
    strengthLbl.textContent = val.length ? labels[score] : '';
    strengthLbl.style.color = lColors[score];

    checkMatch();
});

confirmInput.addEventListener('input', checkMatch);

function checkMatch() {
    var n = newInput.value;
    var c = confirmInput.value;
    if (!c.length) {
        matchLbl.textContent = '';
        return;
    }
    if (n === c) {
        matchLbl.textContent = 'Hasla sa zgodne';
        matchLbl.style.color = '#4ade80';
    } else {
        matchLbl.textContent = 'Hasla sie nie zgadzaja';
        matchLbl.style.color = '#f87171';
    }
}
</script>

</body>
</html>