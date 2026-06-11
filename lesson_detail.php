<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$lesson_id) {
    header("Location: my_lessons.php");
    exit;
}

// Weryfikacja ze lekcja nalezy do tego uzytkownika
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? AND user_id = ?");
$stmt->execute([$lesson_id, $user_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header("Location: my_lessons.php");
    exit;
}

// Pobierz odpowiedzi
$stmt = $pdo->prepare("SELECT * FROM lesson_answers WHERE lesson_id = ?");
$stmt->execute([$lesson_id]);
$answers = $stmt->fetchAll();

// Dane uzytkownika do nawigacji
$userStmt = $pdo->prepare("SELECT first_name, is_admin FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user       = $userStmt->fetch();
$first_name = htmlspecialchars($user['first_name']);
$is_admin   = $user['is_admin'];

// Oblicz statystyki
$total    = $lesson['correct'] + $lesson['incorrect'];
$percent  = $total > 0 ? round(($lesson['correct'] / $total) * 100) : 0;
$scoreClass = $percent >= 80 ? 'great' : ($percent >= 50 ? 'good' : 'poor');
$scoreMsg   = $percent >= 80 ? 'Swietny wynik!' : ($percent >= 50 ? 'Dobry wynik!' : 'Cwicz dalej!');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegoly lekcji - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .score-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column;
            margin: 0 auto 1rem;
            font-size: 1.6rem; font-weight: 700;
        }
        .score-ring.great { background: rgba(34,197,94,.12);  border: 3px solid rgba(34,197,94,.4);  color: #4ade80; }
        .score-ring.good  { background: rgba(99,102,241,.12); border: 3px solid rgba(99,102,241,.4); color: #818cf8; }
        .score-ring.poor  { background: rgba(239,68,68,.1);   border: 3px solid rgba(239,68,68,.35); color: #f87171; }

        .meta-chip {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: .25rem .75rem;
            font-size: .8rem;
            color: rgba(255,255,255,.5);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .answer-row {
            display: flex; align-items: center; gap: 1rem;
            padding: .75rem 1rem; border-radius: 10px;
            margin-bottom: .4rem;
        }
        .answer-row.correct { background: rgba(34,197,94,.07);  border: 1px solid rgba(34,197,94,.18); }
        .answer-row.wrong   { background: rgba(239,68,68,.06);  border: 1px solid rgba(239,68,68,.18); }

        .answer-col        { flex: 1; min-width: 0; font-size: .88rem; }
        .answer-col .lbl   { font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.28); margin-bottom: 2px; }
        .answer-col .val   { color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .answer-col .wrong-val   { color: #f87171; text-decoration: line-through; opacity: .65; }
        .answer-col .correct-val { color: #4ade80; }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.3); margin-bottom: .75rem;
        }

        .progress { background: rgba(255,255,255,.08); border-radius: 99px; height: 6px; }
        .progress-bar { border-radius: 99px; }
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

<div class="container py-4" style="max-width: 680px;">

    <!-- NAGLOWEK Z WYNIKIEM -->
    <div class="text-center mb-4">
        <div class="score-ring <?= $scoreClass ?>">
            <span><?= $lesson['correct'] ?>/<?= $total ?></span>
        </div>
        <h1 class="h4 fw-semibold text-light mb-2"><?= $scoreMsg ?></h1>
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <span class="meta-chip">
                <i class="ti ti-calendar" style="font-size:.85rem;"></i>
                <?= date('d.m.Y', strtotime($lesson['created_at'])) ?>
            </span>
            <span class="meta-chip">
                <i class="ti ti-clock" style="font-size:.85rem;"></i>
                <?= date('H:i', strtotime($lesson['created_at'])) ?>
            </span>
            <span class="meta-chip">
                <i class="ti ti-chart-bar" style="font-size:.85rem;"></i>
                <?= $percent ?>% skutecznosci
            </span>
        </div>
    </div>

    <!-- PASEK POSTEPU -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="progress flex-grow-1">
            <div class="progress-bar"
                 style="width:<?= $percent ?>%;background:<?= $percent >= 80 ? '#4ade80' : ($percent >= 50 ? '#818cf8' : '#f87171') ?>;">
            </div>
        </div>
        <span class="small" style="color:rgba(255,255,255,.4);white-space:nowrap;">
            <?= $lesson['correct'] ?> / <?= $total ?>
        </span>
    </div>

    <!-- LISTA ODPOWIEDZI -->
    <div class="section-label">Szczegoly odpowiedzi</div>
    <div class="mb-4">
        <?php foreach ($answers as $i => $a): ?>
        <div class="answer-row <?= $a['is_correct'] ? 'correct' : 'wrong' ?>">

            <div style="flex-shrink:0;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:600;background:rgba(255,255,255,.06);color:rgba(255,255,255,.3);">
                <?= $i + 1 ?>
            </div>

            <div class="answer-col" style="flex:0 0 auto;max-width:30%;">
                <div class="lbl">Pytanie</div>
                <div class="val"><?= htmlspecialchars($a['pl_word']) ?></div>
            </div>

            <i class="ti ti-arrow-narrow-right" style="color:rgba(255,255,255,.18);flex-shrink:0;"></i>

            <div class="answer-col">
                <div class="lbl">Twoja odpowiedz</div>
                <div class="val <?= $a['is_correct'] ? '' : 'wrong-val' ?>">
                    <?= $a['user_answer'] !== '' ? htmlspecialchars($a['user_answer']) : '(brak)' ?>
                </div>
            </div>

            <?php if (!$a['is_correct']): ?>
            <div class="answer-col">
                <div class="lbl">Poprawna</div>
                <div class="val correct-val"><?= htmlspecialchars($a['correct_answer']) ?></div>
            </div>
            <?php endif; ?>

            <div style="flex-shrink:0;">
                <?php if ($a['is_correct']): ?>
                    <i class="ti ti-circle-check" style="color:#4ade80;font-size:1.1rem;"></i>
                <?php else: ?>
                    <i class="ti ti-circle-x" style="color:#f87171;font-size:1.1rem;"></i>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- PRZYCISKI -->
    <div class="d-flex gap-3">
        <a href="my_lessons.php"
           class="btn flex-fill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
           style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:10px;">
            <i class="ti ti-arrow-left"></i> Moje lekcje
        </a>
        <a href="lesson.php"
           class="btn flex-fill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
           style="background:#6366f1;border:none;color:white;border-radius:10px;">
            <i class="ti ti-refresh"></i> Nowa lekcja
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>