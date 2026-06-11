<?php
session_start();
require_once 'includes/auth.php';
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];

// Dane użytkownika
$userStmt = $pdo->prepare("SELECT first_name, is_admin, streak FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();
$first_name = htmlspecialchars($user['first_name']);
$is_admin   = $user['is_admin'];
$streak     = $user['streak'] ?? 0;

// Statystyki
$summary = $pdo->prepare("
    SELECT
        COUNT(*) AS lessons_count,
        SUM(correct) AS total_correct,
        SUM(incorrect) AS total_incorrect
    FROM lessons WHERE user_id = ?
");
$summary->execute([$user_id]);
$sum       = $summary->fetch();
$lessons   = $sum['lessons_count'];
$correct   = $sum['total_correct'] ?? 0;
$incorrect = $sum['total_incorrect'] ?? 0;
$total     = $correct + $incorrect;
$accuracy  = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

// Ostatnie 7 lekcji
$history = $pdo->prepare("
    SELECT correct, incorrect, created_at
    FROM lessons WHERE user_id = ?
    ORDER BY created_at DESC LIMIT 7
");
$history->execute([$user_id]);
$hist = array_reverse($history->fetchAll());

// Osiągnięcia
$achStmt = $pdo->prepare("
    SELECT a.name, a.description, ua.unlocked_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.id
    WHERE ua.user_id = ?
    ORDER BY ua.unlocked_at DESC
");
$achStmt->execute([$user_id]);
$achievements = $achStmt->fetchAll();

// Dane do wykresów
$barLabels   = json_encode(array_map(fn($h) => date('d.m', strtotime($h['created_at'])), $hist));
$barCorrect  = json_encode(array_map(fn($h) => (int)$h['correct'],   $hist));
$barWrong    = json_encode(array_map(fn($h) => (int)$h['incorrect'], $hist));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel główny – Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover { color: #fff !important; }
        .nav-link.active { color: #fff !important; }

        .stat-card { background: rgba(99,102,241,.08); border: 1px solid rgba(99,102,241,.25); border-radius: 12px; padding: 1.25rem 1.5rem; }
        .stat-value { font-size: 2rem; font-weight: 600; color: #a5b4fc; line-height: 1; }
        .stat-label { font-size: .8rem; color: rgba(255,255,255,.5); margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }

        .tile { background: #1e1e2e; border: 1px solid rgba(255,255,255,.08); border-radius: 14px;
                padding: 1.5rem; text-decoration: none; color: #e2e8f0; display: flex;
                align-items: center; gap: 1rem; transition: border-color .2s, transform .15s, background .2s; }
        .tile:hover { border-color: rgba(99,102,241,.6); background: #25253a; transform: translateY(-2px); color: #fff; }
        .tile-icon { width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center;
                     justify-content: center; font-size: 1.25rem; flex-shrink: 0; }

        .achievement-card { background: #1e1e2e; border: 1px solid rgba(255,255,255,.08); border-radius: 12px;
                            padding: 1rem; transition: border-color .2s; }
        .achievement-card:hover { border-color: rgba(99,102,241,.5); }
        .ach-icon { width: 40px; height: 40px; background: rgba(99,102,241,.15); border-radius: 50%;
                    display: flex; align-items: center; justify-content: center; margin-bottom: .75rem; }

        .streak-badge { background: rgba(251,146,60,.12); border: 1px solid rgba(251,146,60,.3);
                        color: #fb923c; border-radius: 20px; padding: .3rem .9rem; font-size: .9rem;
                        font-weight: 600; display: inline-flex; align-items: center; gap: .4rem; }

        .section-label { font-size: .7rem; font-weight: 600; text-transform: uppercase;
                         letter-spacing: .08em; color: rgba(255,255,255,.35); margin-bottom: .75rem; }
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
                        <i class="ti ti-puzzle"></i> Ukryte słowo
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

<!-- GŁÓWNA TREŚĆ -->
<div class="container py-4">

    <!-- POWITANIE + STREAK -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-semibold text-light mb-1">Cześć, <?= $first_name ?>!</h1>
            <p class="text-secondary mb-0" style="font-size:.9rem;">Co dzisiaj chcesz powtórzyć?</p>
        </div>
        <div class="streak-badge">
            <i class="ti ti-flame"></i>
            <?= $streak ?> <?= $streak === 1 ? 'dzień' : 'dni' ?> z rzędu
        </div>
    </div>

    <!-- STATYSTYKI -->
    <div class="section-label">Twój postęp</div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $lessons ?></div>
                <div class="stat-label">Lekcji ukończonych</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $correct ?></div>
                <div class="stat-label">Poprawnych odpowiedzi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $incorrect ?></div>
                <div class="stat-label">Błędnych odpowiedzi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $accuracy ?>%</div>
                <div class="stat-label">Skuteczność</div>
            </div>
        </div>
    </div>

    <!-- KAFLE NAWIGACYJNE -->
    <div class="section-label">Szybki dostęp</div>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4">
            <a href="lesson.php" class="tile">
                <div class="tile-icon" style="background:rgba(99,102,241,.15);">
                    <i class="ti ti-book" style="color:#818cf8;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Rozpocznij lekcję</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">10 losowych fiszek</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="flashcards.php" class="tile">
                <div class="tile-icon" style="background:rgba(34,197,94,.12);">
                    <i class="ti ti-cards" style="color:#4ade80;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Moje fiszki</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">Zarządzaj słowami</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="hidden_word.php" class="tile">
                <div class="tile-icon" style="background:rgba(251,146,60,.12);">
                    <i class="ti ti-puzzle" style="color:#fb923c;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Ukryte słowo</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">Minigra słowna</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="my_lessons.php" class="tile">
                <div class="tile-icon" style="background:rgba(56,189,248,.12);">
                    <i class="ti ti-list-check" style="color:#38bdf8;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Moje lekcje</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">Historia wyników</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="profile.php" class="tile">
                <div class="tile-icon" style="background:rgba(168,85,247,.12);">
                    <i class="ti ti-user" style="color:#c084fc;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Mój profil</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">Ustawienia konta</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <?php if ($is_admin): ?>
        <div class="col-12 col-sm-6 col-md-4">
            <a href="admin/admin_panel.php" class="tile" style="border-color:rgba(251,191,36,.2);">
                <div class="tile-icon" style="background:rgba(251,191,36,.1);">
                    <i class="ti ti-shield-check" style="color:#fbbf24;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">Panel administratora</div>
                    <div class="small" style="color:rgba(255,255,255,.4);">Zarządzaj aplikacją</div>
                </div>
                <i class="ti ti-arrow-right ms-auto" style="color:rgba(255,255,255,.25);"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- WYKRESY -->
    <div class="section-label">Analiza wyników</div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <p class="small text-secondary mb-3">Poprawność odpowiedzi</p>
                    <canvas id="pieChart" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <p class="small text-secondary mb-3">Ostatnie <?= count($hist) ?> lekcji</p>
                    <canvas id="barChart" style="max-height: 220px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- OSIĄGNIĘCIA -->
    <div class="section-label">Osiągnięcia</div>
    <?php if (count($achievements) === 0): ?>
        <div class="card bg-dark border-secondary">
            <div class="card-body text-center py-4 text-secondary">
                <i class="ti ti-trophy-off" style="font-size:2rem; opacity:.3;"></i>
                <p class="mt-2 mb-0 small">Nie odblokowałeś jeszcze żadnych osiągnięć.<br>Ukończ lekcję, żeby zdobyć pierwsze!</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($achievements as $a): ?>
            <div class="col-6 col-md-3">
                <div class="achievement-card h-100">
                    <div class="ach-icon">
                        <i class="ti ti-trophy" style="color:#fbbf24; font-size:1.1rem;"></i>
                    </div>
                    <div class="fw-semibold small text-light"><?= htmlspecialchars($a['name']) ?></div>
                    <div class="text-secondary mt-1" style="font-size:.78rem;"><?= htmlspecialchars($a['description']) ?></div>
                    <div class="mt-2" style="font-size:.72rem; color:rgba(255,255,255,.25);">
                        <?= date('d.m.Y', strtotime($a['unlocked_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.color = 'rgba(255,255,255,0.5)';
Chart.defaults.borderColor = 'rgba(255,255,255,0.07)';

new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Poprawne', 'Błędne'],
        datasets: [{
            data: [<?= $correct ?>, <?= $incorrect ?>],
            backgroundColor: ['rgba(99,102,241,0.8)', 'rgba(239,68,68,0.6)'],
            borderColor: ['#6366f1', '#ef4444'],
            borderWidth: 1
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } },
        cutout: '65%'
    }
});

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?= $barLabels ?>,
        datasets: [
            {
                label: 'Poprawne',
                data: <?= $barCorrect ?>,
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderRadius: 6
            },
            {
                label: 'Błędne',
                data: <?= $barWrong ?>,
                backgroundColor: 'rgba(239,68,68,0.5)',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } },
        scales: {
            x: { stacked: false, grid: { display: false } },
            y: { beginAtZero: true, max: 10, ticks: { stepSize: 2 } }
        }
    }
});
</script>

</body>
</html>