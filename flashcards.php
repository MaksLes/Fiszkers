<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/functions.php';

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

$message      = '';
$message_type = '';

if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM flashcards WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['id'], $user_id]);
    $message      = 'Fiszka została usunięta.';
    $message_type = 'danger';
}

if (isset($_POST['add'])) {
    $pl = trim($_POST['pl_word']);
    $en = trim($_POST['en_word']);
    if ($pl !== '' && $en !== '') {
        $stmt = $pdo->prepare("INSERT INTO flashcards (user_id, pl_word, en_word) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $pl, $en]);
        $message      = 'Fiszka zostala dodana.';
        $message_type = 'success';
    }
}

if (isset($_POST['generate_random'])) {
    $sample = [
        ['pies','dog'],['kot','cat'],['dom','house'],['drzewo','tree'],['samochód','car'],
        ['książka','book'],['telefon','phone'],['krzesło','chair'],['okno','window'],['dziecko','child'],
        ['woda','water'],['jedzenie','food'],['szkola','school'],['miasto','city'],['droga','road'],
        ['czas','time'],['dłoń','hand'],['noga','leg'],['słońce','sun'],['księżyc','moon'],
        ['jabłko','apple'],['kawa','coffee'],['noc','night'],['dzień','day'],['przyjaciel','friend'],
        ['mama','mom'],['tata','dad'],['miłość','love'],['pokój','room'],['rower','bike'],
    ];
    shuffle($sample);
    $inserted   = 0;
    $checkStmt  = $pdo->prepare("SELECT COUNT(*) FROM flashcards WHERE user_id = ? AND pl_word = ? AND en_word = ?");
    $insertStmt = $pdo->prepare("INSERT INTO flashcards (user_id, pl_word, en_word) VALUES (?, ?, ?)");
    foreach ($sample as [$pl, $en]) {
        if ($inserted >= 30) break;
        $checkStmt->execute([$user_id, $pl, $en]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$user_id, $pl, $en]);
            $inserted++;
        }
    }
    header('Location: flashcards.php?generated=' . $inserted);
    exit;
}

if (isset($_GET['generated'])) {
    $n            = (int)$_GET['generated'];
    $message      = $n > 0 ? 'Dodano ' . $n . ' losowych fiszek.' : 'Wszystkie losowe fiszki juz istnieja.';
    $message_type = $n > 0 ? 'success' : 'warning';
}

$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$cards = $stmt->fetchAll();
$count = count($cards);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Moje fiszki - Fiszkers</title>
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

        .fc-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 10px;
            padding: .75rem 1rem;
            transition: border-color .2s;
        }
        .fc-row:hover { border-color: rgba(99,102,241,.4); }

        .fc-word {
            flex: 1;
            min-width: 0;
            font-size: .95rem;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fc-word.pl { font-weight: 500; }
        .fc-word.en { color: rgba(255,255,255,.55); }

        .fc-arrow { color: rgba(99,102,241,.6); font-size: .85rem; flex-shrink: 0; }

        .fc-actions { display: flex; gap: .4rem; flex-shrink: 0; }

        .btn-icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid transparent;
            background: transparent; cursor: pointer;
            transition: background .15s, border-color .15s;
            color: rgba(255,255,255,.4);
            font-size: 1rem;
            padding: 0;
        }
        .btn-icon.edit:hover { background: rgba(99,102,241,.15); border-color: rgba(99,102,241,.4); color: #818cf8; }
        .btn-icon.del:hover  { background: rgba(239,68,68,.12);  border-color: rgba(239,68,68,.4);  color: #f87171; }

        #searchInput {
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.1);
            color: #e2e8f0;
            border-radius: 10px;
        }
        #searchInput:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
        #searchInput::placeholder { color: #555; }

        .count-badge {
            background: rgba(99,102,241,.15);
            color: #a5b4fc;
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 20px;
            padding: .2rem .75rem;
            font-size: .8rem;
            font-weight: 600;
        }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.35); margin-bottom: .75rem;
        }

        .empty-state { text-align: center; padding: 3rem 1rem; color: rgba(255,255,255,.25); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

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

<div class="container py-4" style="max-width: 860px;">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <h1 class="h4 fw-semibold text-light mb-0">Moje fiszki</h1>
            <span class="count-badge">
                <?= $count ?> <?= $count === 1 ? 'fiszka' : ($count < 5 ? 'fiszki' : 'fiszek') ?>
            </span>
        </div>
        <form method="post">
            <button type="submit" name="generate_random"
                    class="btn btn-sm d-flex align-items-center gap-2"
                    style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);color:#a5b4fc;border-radius:8px;">
                <i class="ti ti-sparkles"></i> Dodaj 30 losowych
            </button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> rounded-3 small d-flex align-items-center gap-2 mb-4" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="section-label">Dodaj nową fiszkę</div>
    <div class="card bg-dark border-secondary mb-4" style="border-radius:14px;">
        <div class="card-body p-3">
            <form method="post" class="d-flex gap-3 align-items-end flex-wrap">
                <div style="flex:1;min-width:160px;">
                    <label class="form-label small text-secondary mb-1">Słowo po polsku</label>
                    <input type="text"
                           name="pl_word"
                           class="form-control bg-dark-subtle border-secondary text-light"
                           placeholder="np. jabłko"
                           autocomplete="off"
                           required>
                </div>
                <div class="pb-1" style="color:rgba(99,102,241,.5);font-size:1.2rem;flex-shrink:0;">
                    <i class="ti ti-arrows-right"></i>
                </div>
                <div style="flex:1;min-width:160px;">
                    <label class="form-label small text-secondary mb-1">Słowo po angielsku</label>
                    <input type="text"
                           name="en_word"
                           class="form-control bg-dark-subtle border-secondary text-light"
                           placeholder="np. apple"
                           autocomplete="off"
                           required>
                </div>
                <div style="flex-shrink:0;">
                    <button type="submit" name="add"
                            class="btn fw-semibold d-flex align-items-center gap-2"
                            style="background:#6366f1;border:none;color:white;border-radius:10px;padding:.5rem 1.25rem;white-space:nowrap;">
                        <i class="ti ti-plus"></i> Dodaj fiszkę
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($count === 0): ?>
        <div class="empty-state">
            <i class="ti ti-cards"></i>
            <p class="mb-1">Nie masz jeszcze zadnych fiszek.</p>
            <p class="small">Dodaj pierwsza powyzej lub wygeneruj losowy zestaw.</p>
        </div>
    <?php else: ?>

        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="position-relative flex-grow-1">
                <i class="ti ti-search position-absolute"
                   style="left:.75rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);font-size:.95rem;pointer-events:none;"></i>
                <input type="text"
                       id="searchInput"
                       class="form-control ps-4"
                       placeholder="Szukaj fiszki...">
            </div>
            <span id="searchCount" class="small text-secondary" style="white-space:nowrap;min-width:60px;text-align:right;">
                <?= $count ?> wynikow
            </span>
        </div>

        <div class="section-label">Twoje fiszki</div>
        <div id="flashcardList" class="d-flex flex-column gap-2">
            <?php foreach ($cards as $c): ?>
            <div class="fc-row"
                 data-pl="<?= strtolower(htmlspecialchars($c['pl_word'])) ?>"
                 data-en="<?= strtolower(htmlspecialchars($c['en_word'])) ?>">

                <span class="fc-word pl"><?= htmlspecialchars($c['pl_word']) ?></span>
                <i class="ti ti-arrow-narrow-right fc-arrow"></i>
                <span class="fc-word en"><?= htmlspecialchars($c['en_word']) ?></span>

                <div class="fc-actions">
                    <form method="post" action="edit_flashcard.php" class="d-inline">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-icon edit" title="Edytuj">
                            <i class="ti ti-pencil"></i>
                        </button>
                    </form>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" name="delete" class="btn-icon del" title="Usun"
                                onclick="return confirm('Usunąć fiszkę: <?= htmlspecialchars($c['pl_word'], ENT_QUOTES) ?>?')">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <p id="noResults" class="text-center text-secondary small mt-4" style="display:none;">
            Brak fiszek pasujacych do wyszukiwania.
        </p>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
const searchCount = document.getElementById('searchCount');
const noResults   = document.getElementById('noResults');
const rows        = document.querySelectorAll('.fc-row');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        var q = searchInput.value.toLowerCase().trim();
        var visible = 0;
        rows.forEach(function(row) {
            var match = row.dataset.pl.includes(q) || row.dataset.en.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        searchCount.textContent = visible + ' wynikow';
        noResults.style.display = visible === 0 ? 'block' : 'none';
    });
}
</script>

</body>
</html>