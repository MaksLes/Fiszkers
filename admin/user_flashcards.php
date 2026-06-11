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

// Dane wlasciciela fiszek
$userStmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$userStmt->execute([$id]);
$owner = $userStmt->fetch();

if (!$owner) {
    header("Location: admin_panel.php");
    exit;
}

// Usuwanie fiszki
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $del = $pdo->prepare("DELETE FROM flashcards WHERE id = ? AND user_id = ?");
    $del->execute([$delete_id, $id]);
    header("Location: user_flashcards.php?id=" . $id);
    exit;
}

// Pobierz fiszki
$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$fiszki = $stmt->fetchAll();
$count  = count($fiszki);

$initials = strtoupper(
    mb_substr($owner['first_name'], 0, 1) .
    mb_substr($owner['last_name'],  0, 1)
);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Fiszki użytkownika - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .owner-card {
            background: rgba(99,102,241,.07);
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .owner-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: rgba(99,102,241,.2);
            border: 2px solid rgba(99,102,241,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; font-weight: 700; color: #a5b4fc;
            flex-shrink: 0;
        }

        .count-badge {
            background: rgba(99,102,241,.15);
            color: #a5b4fc;
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 20px;
            padding: .2rem .75rem;
            font-size: .8rem;
            font-weight: 600;
        }

        .fc-row {
            display: flex; align-items: center; gap: 1rem;
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 10px;
            padding: .75rem 1rem;
            transition: border-color .2s;
        }
        .fc-row:hover { border-color: rgba(239,68,68,.3); }

        .fc-word { flex: 1; min-width: 0; font-size: .9rem;
                   white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fc-word.pl { font-weight: 500; color: #e2e8f0; }
        .fc-word.en { color: rgba(255,255,255,.5); }
        .fc-arrow    { color: rgba(99,102,241,.5); font-size: .85rem; flex-shrink: 0; }

        .fc-date { font-size: .72rem; color: rgba(255,255,255,.25);
                   flex-shrink: 0; white-space: nowrap; }

        .btn-del {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid transparent;
            background: transparent; cursor: pointer;
            color: rgba(255,255,255,.35); font-size: 1rem; padding: 0;
            transition: background .15s, border-color .15s, color .15s;
            flex-shrink: 0;
        }
        .btn-del:hover {
            background: rgba(239,68,68,.12);
            border-color: rgba(239,68,68,.4);
            color: #f87171;
        }

        #searchInput {
            background: #1e1e2e; border: 1px solid rgba(255,255,255,.1);
            color: #e2e8f0; border-radius: 10px;
        }
        #searchInput:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
        }
        #searchInput::placeholder { color: #555; }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.3); margin-bottom: .75rem;
        }
        .empty-state {
            text-align: center; padding: 3rem 1rem;
            color: rgba(255,255,255,.25);
        }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 1rem; }
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

<div class="container py-4" style="max-width: 780px;">

    <!-- NAGLOWEK -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <h1 class="h4 fw-semibold text-light mb-0">Fiszki użytkownika</h1>
        <a href="admin_panel.php"
           class="btn btn-sm d-flex align-items-center gap-1"
           style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);border-radius:8px;">
            <i class="ti ti-arrow-left"></i> Powrót do panelu
        </a>
    </div>

    <!-- INFO O WLASCICIELU -->
    <div class="owner-card mb-4">
        <div class="owner-avatar"><?= $initials ?></div>
        <div class="flex-grow-1 min-width-0">
            <div class="fw-semibold text-light" style="font-size:.9rem;">
                <?= htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name']) ?>
            </div>
            <div class="text-secondary" style="font-size:.78rem;">
                <?= htmlspecialchars($owner['email']) ?>
            </div>
        </div>
        <span class="count-badge">
            <?= $count ?> <?= $count === 1 ? 'fiszka' : ($count < 5 ? 'fiszki' : 'fiszek') ?>
        </span>
    </div>

    <?php if ($count === 0): ?>

        <div class="empty-state">
            <i class="ti ti-cards"></i>
            <p class="mb-0">Ten użytkownik nie ma żadnych fiszek.</p>
        </div>

    <?php else: ?>

        <!-- WYSZUKIWARKA -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="position-relative flex-grow-1">
                <i class="ti ti-search position-absolute"
                   style="left:.75rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);font-size:.9rem;pointer-events:none;"></i>
                <input type="text" id="searchInput"
                       class="form-control ps-4"
                       placeholder="Szukaj fiszki...">
            </div>
            <span id="searchCount" class="small text-secondary" style="white-space:nowrap;min-width:60px;text-align:right;">
                <?= $count ?> wynikow
            </span>
        </div>

        <!-- LISTA FISZEK -->
        <div class="section-label">Lista fiszek</div>
        <div id="flashcardList" class="d-flex flex-column gap-2">
            <?php foreach ($fiszki as $f): ?>
            <div class="fc-row"
                 data-pl="<?= strtolower(htmlspecialchars($f['pl_word'])) ?>"
                 data-en="<?= strtolower(htmlspecialchars($f['en_word'])) ?>">

                <span class="fc-word pl"><?= htmlspecialchars($f['pl_word']) ?></span>
                <i class="ti ti-arrow-narrow-right fc-arrow"></i>
                <span class="fc-word en"><?= htmlspecialchars($f['en_word']) ?></span>

                <span class="fc-date d-none d-md-block">
                    <?= date('d.m.Y', strtotime($f['created_at'])) ?>
                </span>

                <form method="post" action="user_flashcards.php?id=<?= $id ?>" class="d-inline">
                    <input type="hidden" name="delete_id" value="<?= $f['id'] ?>">
                    <button type="submit" class="btn-del"
                            title="Usun fiszke"
                            onclick="return confirm('Usunac fiszke: <?= htmlspecialchars($f['pl_word'], ENT_QUOTES) ?>?')">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>

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
var searchInput = document.getElementById('searchInput');
var searchCount = document.getElementById('searchCount');
var noResults   = document.getElementById('noResults');
var rows        = document.querySelectorAll('.fc-row');

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