<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Pobierz dane uzytkownika
$stmt = $pdo->prepare("SELECT first_name, last_name, email, is_admin, streak, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$is_admin   = $user['is_admin'];
$streak     = $user['streak'] ?? 0;
$member_since = date('d.m.Y', strtotime($user['created_at']));

// Obsluga formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name']);
    $lastName  = sanitize($_POST['last_name']);
    $email     = sanitize($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);

    if ($stmt->fetch()) {
        $error = 'Ten adres e-mail jest juz zajety przez inne konto.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $user_id]);
        $success = 'Dane profilu zostaly zaktualizowane.';
        $user['first_name'] = $firstName;
        $user['last_name']  = $lastName;
        $user['email']      = $email;
    }
}

$first_name  = htmlspecialchars($user['first_name']);
$last_name   = htmlspecialchars($user['last_name']);
$initials    = strtoupper(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moj profil - Fiszkers</title>
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

        /* Avatar */
        .avatar-ring {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(99,102,241,.2);
            border: 2px solid rgba(99,102,241,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.75rem; font-weight: 700; color: #a5b4fc;
            margin: 0 auto;
            flex-shrink: 0;
        }

        /* Chip statystyki */
        .stat-chip {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 10px;
            padding: .6rem .9rem;
            text-align: center;
        }
        .stat-chip .val { font-size: 1.1rem; font-weight: 700; color: #a5b4fc; }
        .stat-chip .lbl { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: rgba(255,255,255,.35); margin-top: 2px; }

        /* Divider */
        .section-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.07);
            margin: 1.25rem 0;
        }

        /* Przycisk zmiany hasla */
        .btn-change-pass {
            background: rgba(251,146,60,.08);
            border: 1px solid rgba(251,146,60,.25);
            color: #fb923c;
            border-radius: 10px;
            padding: .5rem 1rem;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            transition: background .15s, border-color .15s;
        }
        .btn-change-pass:hover {
            background: rgba(251,146,60,.15);
            border-color: rgba(251,146,60,.45);
            color: #fb923c;
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

<div class="container py-5 d-flex justify-content-center">
    <div class="w-100" style="max-width: 520px;">

        <!-- KARTA AWATARA -->
        <div class="card bg-dark border-secondary mb-3" style="border-radius: 16px;">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="avatar-ring mb-3"><?= $initials ?></div>
                    <h2 class="h5 fw-semibold text-light mb-1">
                        <?= $first_name ?> <?= $last_name ?>
                    </h2>
                    <p class="text-secondary small mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    <?php if ($is_admin): ?>
                    <span class="badge mt-2" style="background:rgba(251,191,36,.12);color:#fbbf24;border:1px solid rgba(251,191,36,.25);font-weight:500;">
                        <i class="ti ti-shield-check" style="font-size:.75rem;"></i> Administrator
                    </span>
                    <?php endif; ?>
                </div>

                <hr class="section-divider">

                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-chip">
                            <div class="val">
                                <i class="ti ti-flame" style="font-size:1rem;color:#fb923c;"></i>
                                <?= $streak ?>
                            </div>
                            <div class="lbl">Dni z rzędu</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-chip">
                            <div class="val" style="font-size:.9rem;"><?= $member_since ?></div>
                            <div class="lbl">Członek od</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KARTA FORMULARZA -->
        <div class="card bg-dark border-secondary" style="border-radius: 16px;">
            <div class="card-body p-4">

                <h3 class="h6 fw-semibold text-light mb-3 d-flex align-items-center gap-2">
                    <i class="ti ti-pencil" style="color:#6366f1;"></i>
                    Edytuj dane
                </h3>

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

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="first_name" class="form-label small text-secondary mb-1">Imię</label>
                            <input type="text"
                                   class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                                   id="first_name" name="first_name"
                                   value="<?= $first_name ?>"
                                   autocomplete="off" required>
                        </div>
                        <div class="col-6">
                            <label for="last_name" class="form-label small text-secondary mb-1">Nazwisko</label>
                            <input type="text"
                                   class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                                   id="last_name" name="last_name"
                                   value="<?= $last_name ?>"
                                   autocomplete="off" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label small text-secondary mb-1">Adres e-mail</label>
                        <input type="email"
                               class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                               id="email" name="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               required>
                    </div>

                    <button type="submit"
                            class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                            style="background:#6366f1;border:none;color:white;border-radius:10px;">
                        <i class="ti ti-device-floppy"></i> Zapisz zmiany
                    </button>

                </form>

                <hr class="section-divider">

                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-secondary">Chcesz zmienić hasło?</span>
                    <a href="change_password.php" class="btn-change-pass">
                        <i class="ti ti-lock"></i> Zmień hasło
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>