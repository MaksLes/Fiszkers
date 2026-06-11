<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/achievements.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id      = $_SESSION['user_id'];
$MAX_MISTAKES = 10;

$userStmt = $pdo->prepare("SELECT first_name, is_admin FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user       = $userStmt->fetch();
$first_name = htmlspecialchars($user['first_name']);
$is_admin   = $user['is_admin'];

// Nowa gra
if (!isset($_POST['word'])) {
    $stmt = $pdo->query("SELECT * FROM flashcards ORDER BY RAND() LIMIT 1");
    $card = $stmt->fetch();
    $word     = mb_strtoupper($card['en_word'], 'UTF-8');
    $guessed  = array_fill(0, mb_strlen($word, 'UTF-8'), '_');
    $used     = [];
    $mistakes = 0;
} else {
    $word     = $_POST['word'];
    $guessed  = explode(',', $_POST['guessed']);
    $used     = explode(',', $_POST['used']);
    if ($used[0] === '') $used = [];
    $mistakes = intval($_POST['mistakes']);

    if (isset($_POST['letter'])) {
        $letter = mb_strtoupper($_POST['letter'], 'UTF-8');
        if (!in_array($letter, $used)) {
            $used[] = $letter;
            $found  = false;
            for ($i = 0; $i < mb_strlen($word, 'UTF-8'); $i++) {
                if (mb_substr($word, $i, 1, 'UTF-8') === $letter) {
                    $guessed[$i] = $letter;
                    $found = true;
                }
            }
            if (!$found) $mistakes++;
        }
    }
}

$won  = implode('', $guessed) === $word;
$lost = $mistakes >= $MAX_MISTAKES;

// Streak tylko przy wygranej
if ($won) {
    $stmt = $pdo->prepare("SELECT streak, last_activity FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stUser    = $stmt->fetch();
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($stUser['last_activity'] === $today) {
        $new_streak = $stUser['streak'];
    } elseif ($stUser['last_activity'] === $yesterday) {
        $new_streak = ($stUser['streak'] ?? 0) + 1;
    } else {
        $new_streak = 1;
    }

    $stmt = $pdo->prepare("UPDATE users SET streak = ?, last_activity = ? WHERE id = ?");
    $stmt->execute([$new_streak, $today, $user_id]);

    // Osiagniecia
    unlockAchievement($pdo, $user_id, 'win_hidden_word');
    checkAchievements($pdo, $user_id);
}

// Kolorowanie klawiatury
$correct_used = [];
$wrong_used   = [];
foreach ($used as $l) {
    if (in_array($l, $guessed)) {
        $correct_used[] = $l;
    } else {
        $wrong_used[] = $l;
    }
}

$alphabet = mb_str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Ukryte słowo - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .game-card {
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            padding: 2rem;
            max-width: 620px;
            width: 100%;
        }

        .letter-tiles {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 1.5rem 0;
        }
        .letter-tile {
            width: 44px; height: 52px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 700;
            border-bottom: 3px solid;
        }
        .letter-tile.revealed {
            background: rgba(99,102,241,.15);
            border-color: #6366f1;
            color: #e2e8f0;
        }
        .letter-tile.empty {
            background: rgba(255,255,255,.04);
            border-color: rgba(255,255,255,.2);
            color: transparent;
        }

        .mistakes-dots {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .mistake-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
        }
        .mistake-dot.used  { background: #f87171; }
        .mistake-dot.empty { background: rgba(255,255,255,.12); }

        .keyboard {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-top: 1.25rem;
        }
        .key-btn {
            width: 40px; height: 40px;
            border-radius: 8px;
            font-size: .85rem; font-weight: 600;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.07);
            color: #e2e8f0;
            cursor: pointer;
            transition: background .15s, transform .1s;
            padding: 0;
        }
        .key-btn:hover:not([disabled]) {
            background: rgba(99,102,241,.25);
            border-color: rgba(99,102,241,.5);
            transform: translateY(-1px);
        }
        .key-btn.correct {
            background: rgba(34,197,94,.15);
            border-color: rgba(34,197,94,.4);
            color: #4ade80;
            cursor: default;
        }
        .key-btn.wrong {
            background: rgba(239,68,68,.08);
            border-color: rgba(239,68,68,.15);
            color: rgba(255,255,255,.2);
            cursor: default;
        }

        .result-card {
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .result-card.won  { background: rgba(34,197,94,.08);  border: 1px solid rgba(34,197,94,.25); }
        .result-card.lost { background: rgba(239,68,68,.07);  border: 1px solid rgba(239,68,68,.2); }
        .result-word {
            font-size: 1.75rem; font-weight: 700; letter-spacing: 6px; margin: .5rem 0;
        }
        .result-card.won  .result-word { color: #4ade80; }
        .result-card.lost .result-word { color: #f87171; }

        .popup-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.65);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .popup-box {
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 18px;
            padding: 2rem;
            max-width: 420px;
            width: 90%;
            text-align: center;
        }
        .popup-icon {
            width: 56px; height: 56px;
            background: rgba(99,102,241,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

<!-- DEBUG: haslo widoczne w konsoli przegladarki -->
<script>console.log('DEV WORD: <?= $word ?>');</script>

<!-- POPUP INSTRUKCJI -->
<div id="instruction-popup" class="popup-overlay">
    <div class="popup-box">
        <div class="popup-icon">
            <i class="ti ti-puzzle text-primary" style="font-size: 1.4rem;"></i>
        </div>
        <h3 class="h5 fw-semibold text-light mb-2">Jak grać?</h3>
        <p class="text-secondary small mb-3">
            Odgadnij ukryte angielskie słowo klikając litery alfabetu.
            Jeśli litera występuje w słowie - zostanie odsłonięta.
            Masz maksymalnie <strong class="text-light"><?= $MAX_MISTAKES ?> błędów</strong>.
            Powodzenia!
        </p>
        <button id="close-popup"
                class="btn fw-semibold px-4"
                style="background:#6366f1;border:none;color:white;border-radius:10px;">
            Rozumiem, zaczynamy!
        </button>
    </div>
</div>

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
                    <a class="nav-link active d-flex align-items-center gap-1" href="hidden_word.php">
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

<!-- GRA -->
<div class="container d-flex justify-content-center py-4">
    <div class="game-card">

        <!-- Naglowek -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h5 fw-semibold text-light mb-0 d-flex align-items-center gap-2">
                <i class="ti ti-puzzle" style="color:#6366f1;"></i>
                Ukryte słowo
            </h1>
            <button id="help-btn"
                    class="btn btn-sm d-flex align-items-center gap-1"
                    style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);border-radius:8px;">
                <i class="ti ti-help"></i> Jak grać?
            </button>
        </div>

        <?php if ($won): ?>

            <!-- WYGRANA -->
            <div class="result-card won">
                <i class="ti ti-trophy" style="font-size:2rem;color:#fbbf24;"></i>
                <p class="text-secondary small mt-2 mb-1">Odgadles slowo!</p>
                <div class="result-word"><?= htmlspecialchars($word) ?></div>
                <p class="text-secondary small mb-0">
                    Bledy: <?= $mistakes ?> / <?= $MAX_MISTAKES ?>
                </p>
            </div>
            <form method="post">
                <button type="submit"
                        class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                        style="background:#6366f1;border:none;color:white;border-radius:10px;">
                    <i class="ti ti-refresh"></i> Zagraj ponownie
                </button>
            </form>

        <?php elseif ($lost): ?>

            <!-- PRZEGRANA -->
            <div class="result-card lost">
                <i class="ti ti-mood-sad" style="font-size:2rem;color:#f87171;"></i>
                <p class="text-secondary small mt-2 mb-1">Nie udalo sie! Poprawne slowo to:</p>
                <div class="result-word"><?= htmlspecialchars($word) ?></div>
                <p class="text-secondary small mb-0">
                    Wykorzystano <?= $mistakes ?> / <?= $MAX_MISTAKES ?> bledow
                </p>
            </div>
            <form method="post">
                <button type="submit"
                        class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                        style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:10px;">
                    <i class="ti ti-refresh"></i> Sprobuj ponownie
                </button>
            </form>

        <?php else: ?>

            <!-- KAFELKI LITER -->
            <div class="letter-tiles">
                <?php foreach ($guessed as $tile): ?>
                    <div class="letter-tile <?= $tile !== '_' ? 'revealed' : 'empty' ?>">
                        <?= $tile !== '_' ? htmlspecialchars($tile) : '&nbsp;' ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- LICZNIK BLEDOW -->
            <div class="text-center mb-1">
                <span class="small text-secondary">
                    Błędy: <span class="<?= $mistakes > 6 ? 'text-danger' : 'text-light' ?> fw-semibold"><?= $mistakes ?></span> / <?= $MAX_MISTAKES ?>
                </span>
            </div>
            <div class="mistakes-dots mb-3">
                <?php for ($i = 0; $i < $MAX_MISTAKES; $i++): ?>
                    <div class="mistake-dot <?= $i < $mistakes ? 'used' : 'empty' ?>"></div>
                <?php endfor; ?>
            </div>

            <!-- KLAWIATURA -->
            <form method="post">
                <input type="hidden" name="word"     value="<?= htmlspecialchars($word) ?>">
                <input type="hidden" name="guessed"  value="<?= implode(',', $guessed) ?>">
                <input type="hidden" name="used"     value="<?= implode(',', $used) ?>">
                <input type="hidden" name="mistakes" value="<?= $mistakes ?>">

                <div class="keyboard">
                    <?php foreach ($alphabet as $letter): ?>
                        <?php
                            $cls = '';
                            if (in_array($letter, $correct_used)) $cls = 'correct';
                            elseif (in_array($letter, $wrong_used)) $cls = 'wrong';
                            $disabled = $cls !== '' ? 'disabled' : '';
                        ?>
                        <button type="submit"
                                name="letter"
                                value="<?= $letter ?>"
                                class="key-btn <?= $cls ?>"
                                <?= $disabled ?>>
                            <?= $letter ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>

        <?php endif; ?>

        <!-- Powrot -->
        <p class="text-center mt-3 mb-0">
            <a href="index.php" class="text-secondary small text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left" style="font-size:.85rem;"></i> Powrot do menu
            </a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var popup    = document.getElementById('instruction-popup');
var closeBtn = document.getElementById('close-popup');
var helpBtn  = document.getElementById('help-btn');

if (!localStorage.getItem('hiddenWordSeen')) {
    popup.style.display = 'flex';
}

closeBtn.addEventListener('click', function() {
    popup.style.display = 'none';
    localStorage.setItem('hiddenWordSeen', '1');
});

helpBtn.addEventListener('click', function() {
    popup.style.display = 'flex';
});
</script>

</body>
</html>