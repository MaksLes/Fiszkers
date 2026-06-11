<?php
require 'includes/auth.php';
require 'includes/db.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT first_name, is_admin FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user       = $userStmt->fetch();
$first_name = htmlspecialchars($user['first_name']);
$is_admin   = $user['is_admin'];

$id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$id) {
    header("Location: flashcards.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$card = $stmt->fetch();

if (!$card) {
    header("Location: flashcards.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $pl = trim($_POST['pl_word']);
    $en = trim($_POST['en_word']);
    if ($pl !== '' && $en !== '') {
        $stmt = $pdo->prepare("UPDATE flashcards SET pl_word = ?, en_word = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$pl, $en, $id, $user_id]);
    }
    header("Location: flashcards.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj fiszkę - Fiszkers</title>
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
            width: 52px;
            height: 52px;
            background: rgba(99,102,241,.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
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
                    <a class="nav-link active d-flex align-items-center gap-1" href="flashcards.php">
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
<div class="container d-flex justify-content-center align-items-center py-5">
    <div class="card bg-dark border-secondary shadow-lg p-4 w-100" style="max-width: 500px;">

        <div class="text-center mb-4">
            <div class="icon-wrap">
                <i class="ti ti-pencil text-primary" style="font-size: 22px;"></i>
            </div>
            <h1 class="h5 fw-semibold text-light mb-1">Edytuj fiszkę</h1>
            <p class="text-secondary small mb-0">
                Zmien treść fiszki i kliknij zapisz
            </p>
        </div>

        <form method="post">
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6">
                    <label for="pl_word" class="form-label small text-secondary mb-1">
                        Słowo po polsku
                    </label>
                    <input
                        type="text"
                        class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                        id="pl_word"
                        name="pl_word"
                        value="<?= htmlspecialchars($card['pl_word']) ?>"
                        autocomplete="off"
                        required>
                </div>
                <div class="col-12 col-sm-6">
                    <label for="en_word" class="form-label small text-secondary mb-1">
                        Słowo po angielsku
                    </label>
                    <input
                        type="text"
                        class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                        id="en_word"
                        name="en_word"
                        value="<?= htmlspecialchars($card['en_word']) ?>"
                        autocomplete="off"
                        required>
                </div>
            </div>

            <button type="submit" name="save"
                    class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                    style="background:#6366f1;border:none;color:white;border-radius:10px;">
                <i class="ti ti-device-floppy"></i> Zapisz zmiany
            </button>
        </form>

        <p class="text-center small text-secondary mt-3 mb-0">
            <a href="flashcards.php" class="text-secondary text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left" style="font-size:.85rem;"></i> Wróc do fiszek
            </a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>