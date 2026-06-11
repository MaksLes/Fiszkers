<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    header("Location: ../login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$meStmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
$meStmt->execute([$my_id]);
$me         = $meStmt->fetch();
$first_name = htmlspecialchars($me['first_name']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$id) {
    header("Location: admin_panel.php");
    exit;
}

// Pobierz edytowanego uzytkownika
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: admin_panel.php");
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email']      ?? '');
    $first      = trim($_POST['first_name'] ?? '');
    $last       = trim($_POST['last_name']  ?? '');
    $active     = isset($_POST['is_active']) ? 1 : 0;
    $admin_flag = isset($_POST['is_admin'])  ? 1 : 0;

    // Zabezpieczenie przed odebraniem sobie uprawnien admina
    if ($id === $my_id && !$admin_flag) {
        $error = 'Nie mozesz odebrac sobie uprawnien administratora.';
    } else {
        // Sprawdz unikalnosc emaila
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            $error = 'Ten adres e-mail jest juz zajety przez inne konto.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET email = ?, first_name = ?, last_name = ?, is_active = ?, is_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([$email, $first, $last, $active, $admin_flag, $id]);

            // Odswiez dane uzytkownika
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user    = $stmt->fetch();
            $success = 'Dane uzytkownika zostaly zaktualizowane.';
        }
    }
}

$initials = strtoupper(
    mb_substr($user['first_name'], 0, 1) .
    mb_substr($user['last_name'],  0, 1)
);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj uzytkownika - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        .form-control::placeholder { color: #555; }

        /* Avatar edytowanego uzytkownika */
        .edit-avatar {
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(99,102,241,.2);
            border: 2px solid rgba(99,102,241,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; font-weight: 700; color: #a5b4fc;
            margin: 0 auto;
        }
        .edit-avatar.is-admin {
            background: rgba(251,191,36,.15);
            border-color: rgba(251,191,36,.4);
            color: #fbbf24;
        }

        /* Toggle switch */
        .toggle-row {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: .85rem 1rem;
        }
        .toggle-info .tlabel { font-size: .9rem; font-weight: 500; color: #e2e8f0; }
        .toggle-info .tdesc  { font-size: .75rem; color: rgba(255,255,255,.35); margin-top: 1px; }

        .form-switch .form-check-input {
            width: 2.5em; height: 1.35em; cursor: pointer;
            background-color: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.2);
        }
        .form-switch .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }
        .form-switch .form-check-input[data-type="admin"]:checked {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }

        .section-divider {
            border: none; border-top: 1px solid rgba(255,255,255,.07); margin: 1.25rem 0;
        }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(15,15,25,.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,.07); position: sticky; top: 0; z-index: 100;">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="../index.php">
            <i class="ti ti-cards text-primary" style="font-size: 1.3rem;"></i>
            Fiszkers
        </a>
        <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="../flashcards.php">
                        <i class="ti ti-cards"></i> Fiszki
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="../lesson.php">
                        <i class="ti ti-book"></i> Lekcja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="../my_lessons.php">
                        <i class="ti ti-list-check"></i> Moje lekcje
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="../hidden_word.php">
                        <i class="ti ti-puzzle"></i> Ukryte slowo
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active d-flex align-items-center gap-1 text-warning" href="admin_panel.php">
                        <i class="ti ti-shield-check"></i> Admin
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <a href="../profile.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <div style="width:34px;height:34px;background:rgba(251,191,36,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:600;color:#fbbf24;">
                        <?= strtoupper(mb_substr($first_name, 0, 1)) ?>
                    </div>
                    <span class="text-light small"><?= $first_name ?></span>
                </a>
                <a href="../logout.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="ti ti-logout"></i> Wyloguj
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container d-flex justify-content-center py-5">
<div class="w-100" style="max-width: 500px;">

    <!-- KARTA UZYTKOWNIKA -->
    <div class="card bg-dark border-secondary mb-3" style="border-radius:16px;">
        <div class="card-body p-4 text-center">
            <div class="edit-avatar <?= $user['is_admin'] ? 'is-admin' : '' ?> mb-3">
                <?= $initials ?>
            </div>
            <h2 class="h6 fw-semibold text-light mb-1">
                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            </h2>
            <p class="text-secondary small mb-0"><?= htmlspecialchars($user['email']) ?></p>
            <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                <?php if ($user['is_admin']): ?>
                    <span class="badge" style="background:rgba(251,191,36,.12);color:#fbbf24;border:1px solid rgba(251,191,36,.25);">
                        <i class="ti ti-shield-check" style="font-size:.7rem;"></i> Administrator
                    </span>
                <?php endif; ?>
                <span class="badge <?= $user['is_active'] ? '' : '' ?>"
                      style="background:rgba(<?= $user['is_active'] ? '34,197,94' : '239,68,68' ?>,.1);
                             color:<?= $user['is_active'] ? '#4ade80' : '#f87171' ?>;
                             border:1px solid rgba(<?= $user['is_active'] ? '34,197,94' : '239,68,68' ?>,.25);">
                    <?= $user['is_active'] ? 'Aktywny' : 'Nieaktywny' ?>
                </span>
                <span class="text-secondary" style="font-size:.72rem;">
                    ID: <?= $user['id'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- FORMULARZ EDYCJI -->
    <div class="card bg-dark border-secondary" style="border-radius:16px;">
        <div class="card-body p-4">

            <h3 class="h6 fw-semibold text-light mb-3 d-flex align-items-center gap-2">
                <i class="ti ti-pencil" style="color:#6366f1;"></i>
                Edytuj dane
            </h3>

            <?php if ($success): ?>
                <div class="alert alert-success rounded-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-circle-check"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger rounded-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>

                <!-- Imie + Nazwisko -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Imię</label>
                        <input type="text" name="first_name"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($user['first_name']) ?>"
                               autocomplete="off" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary mb-1">Nazwisko</label>
                        <input type="text" name="last_name"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               value="<?= htmlspecialchars($user['last_name']) ?>"
                               autocomplete="off" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label small text-secondary mb-1">Adres e-mail</label>
                    <input type="email" name="email"
                           class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                           value="<?= htmlspecialchars($user['email']) ?>"
                           required>
                </div>

                <hr class="section-divider">

                <!-- Uprawnienia -->
                <p class="small text-secondary mb-2 fw-semibold">Uprawnienia i status</p>
                <div class="d-flex flex-column gap-2 mb-4">

                    <div class="toggle-row">
                        <div class="toggle-info">
                            <div class="tlabel">Konto aktywne</div>
                            <div class="tdesc">Użytkownik może się logować</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox"
                                   name="is_active" role="switch"
                                   <?= $user['is_active'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="toggle-row">
                        <div class="toggle-info">
                            <div class="tlabel">Administrator</div>
                            <div class="tdesc">Dostęp do panelu administratora</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox"
                                   name="is_admin" role="switch"
                                   data-type="admin"
                                   <?= $user['is_admin'] ? 'checked' : '' ?>
                                   <?= ($id === $my_id) ? 'disabled title="Nie mozesz odebrac sobie uprawnien"' : '' ?>>
                        </div>
                    </div>

                </div>

                <!-- Przyciski -->
                <button type="submit"
                        class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mb-2"
                        style="background:#6366f1;border:none;color:white;border-radius:10px;">
                    <i class="ti ti-device-floppy"></i> Zapisz zmiany
                </button>

                <a href="admin_panel.php"
                   class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                   style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);border-radius:10px;">
                    <i class="ti ti-arrow-left"></i> Wróc do panelu
                </a>

            </form>
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>