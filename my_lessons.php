<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

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

$stmt = $pdo->prepare("
    SELECT id, created_at, correct, incorrect
    FROM lessons
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$lessons = $stmt->fetchAll();

// Statystyki zbiorcze
$total_lessons = count($lessons);
$best_score    = 0;
$sum_accuracy  = 0;

foreach ($lessons as $l) {
    $t = $l['correct'] + $l['incorrect'];
    $p = $t > 0 ? round(($l['correct'] / $t) * 100) : 0;
    if ($l['correct'] > $best_score) $best_score = $l['correct'];
    $sum_accuracy += $p;
}

$avg_accuracy = $total_lessons > 0 ? round($sum_accuracy / $total_lessons) : 0;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moje lekcje - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .stat-card {
            background: rgba(99,102,241,.08);
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
        }
        .stat-value { font-size: 1.9rem; font-weight: 700; color: #a5b4fc; line-height: 1; }
        .stat-label { font-size: .75rem; color: rgba(255,255,255,.4); margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }

        .lesson-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            padding: .9rem 1.1rem;
            transition: border-color .2s;
        }
        .lesson-row:hover { border-color: rgba(99,102,241,.4); }

        .score-bar-wrap {
            flex: 1;
            min-width: 0;
        }
        .score-bar-track {
            height: 6px;
            background: rgba(255,255,255,.08);
            border-radius: 99px;
            overflow: hidden;
            margin-top: 5px;
        }
        .score-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .3s ease;
        }

        .accuracy-badge {
            font-size: .8rem;
            font-weight: 600;
            padding: .25rem .65rem;
            border-radius: 20px;
            flex-shrink: 0;
        }
        .acc-great { background: rgba(34,197,94,.12);  color: #4ade80; border: 1px solid rgba(34,197,94,.25); }
        .acc-good  { background: rgba(99,102,241,.12); color: #818cf8; border: 1px solid rgba(99,102,241,.25); }
        .acc-poor  { background: rgba(239,68,68,.1);   color: #f87171; border: 1px solid rgba(239,68,68,.2); }

        .btn-details {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.1);
            background: transparent;
            color: rgba(255,255,255,.4);
            text-decoration: none;
            transition: background .15s, border-color .15s, color .15s;
            flex-shrink: 0;
        }
        .btn-details:hover {
            background: rgba(99,102,241,.15);
            border-color: rgba(99,102,241,.4);
            color: #818cf8;
        }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.3); margin-bottom: .75rem;
        }

        .empty-state { text-align: center; padding: 3.5rem 1rem; color: rgba(255,255,255,.25); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
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
                    <a class="nav-link active d-flex align-items-center gap-1" href="my_lessons.php">
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

<div class="container py-4" style="max-width: 780px;">

    <!-- NAGLOWEK -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h1 class="h4 fw-semibold text-light mb-0">Moje lekcje</h1>
        <a href="lesson.php"
           class="btn btn-sm fw-semibold d-flex align-items-center gap-2"
           style="background:#6366f1;border:none;color:white;border-radius:8px;">
            <i class="ti ti-book"></i> Nowa lekcja
        </a>
    </div>

    <?php if ($total_lessons > 0): ?>

    <!-- STATYSTYKI -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-value"><?= $total_lessons ?></div>
                <div class="stat-label">Lekcji łącznie</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-value"><?= $best_score ?><span style="font-size:1rem;color:rgba(165,180,252,.5);">/10</span></div>
                <div class="stat-label">Najlepszy wynik</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-value"><?= $avg_accuracy ?><span style="font-size:1rem;color:rgba(165,180,252,.5);">%</span></div>
                <div class="stat-label">Średnia skuteczność</div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <!-- LISTA LEKCJI -->
    <?php if (empty($lessons)): ?>

        <div class="empty-state">
            <i class="ti ti-list-check"></i>
            <p class="mb-1">Nie masz jeszcze zadnych ukonczonych lekcji.</p>
            <p class="small mb-4">Ukoncz pierwsza lekcje, aby zobaczyc tutaj swoje wyniki.</p>
            <a href="lesson.php"
               class="btn fw-semibold d-inline-flex align-items-center gap-2"
               style="background:#6366f1;border:none;color:white;border-radius:10px;padding:.6rem 1.5rem;">
                <i class="ti ti-book"></i> Rozpocznij lekcje
            </a>
        </div>

    <?php else: ?>

        <div class="section-label">Historia lekcji</div>
        <div class="d-flex flex-column gap-2">

            <?php foreach ($lessons as $lesson):
                $t       = $lesson['correct'] + $lesson['incorrect'];
                $percent = $t > 0 ? round(($lesson['correct'] / $t) * 100) : 0;
                $accClass = $percent >= 80 ? 'acc-great' : ($percent >= 50 ? 'acc-good' : 'acc-poor');
                $barColor = $percent >= 80 ? '#4ade80' : ($percent >= 50 ? '#818cf8' : '#f87171');
            ?>
            <div class="lesson-row">

                <!-- Data -->
                <div style="flex-shrink:0;min-width:110px;">
                    <div class="small text-light fw-semibold">
                        <?= date('d.m.Y', strtotime($lesson['created_at'])) ?>
                    </div>
                    <div style="font-size:.75rem;color:rgba(255,255,255,.3);">
                        <?= date('H:i', strtotime($lesson['created_at'])) ?>
                    </div>
                </div>

                <!-- Wynik z paskiem -->
                <div class="score-bar-wrap">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-light fw-semibold">
                            <?= $lesson['correct'] ?><span style="color:rgba(255,255,255,.3);">/<?= $t ?></span>
                        </span>
                        <span style="font-size:.75rem;color:rgba(255,255,255,.35);">
                            <?= $lesson['correct'] ?> popr. &middot; <?= $lesson['incorrect'] ?> bł.
                        </span>
                    </div>
                    <div class="score-bar-track">
                        <div class="score-bar-fill" style="width:<?= $percent ?>%;background:<?= $barColor ?>;"></div>
                    </div>
                </div>

                <!-- Skutecznosc badge -->
                <span class="accuracy-badge <?= $accClass ?>">
                    <?= $percent ?>%
                </span>

                <!-- Przycisk szczegoly -->
                <a href="lesson_detail.php?id=<?= $lesson['id'] ?>" class="btn-details" title="Szczegoly">
                    <i class="ti ti-chevron-right"></i>
                </a>

            </div>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>