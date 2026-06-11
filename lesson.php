<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/achievements.php';

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

// Sprawdz czy uzytkownik ma fiszki
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM flashcards WHERE user_id = ?");
$checkStmt->execute([$user_id]);
$hasFlashcards = $checkStmt->fetchColumn();

// Pobierz losowe fiszki
$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE user_id = ? ORDER BY RAND() LIMIT 10");
$stmt->execute([$user_id]);
$cards = $stmt->fetchAll();
$total = count($cards);

$step     = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$finished = false;
$answers  = $_SESSION['lesson_answers'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userAnswer = trim(strtolower($_POST['answer']));
    $correct    = trim(strtolower($_POST['correct']));
    $pl_word    = $_POST['pl'];

    $answers[] = [
        'pl'         => $pl_word,
        'user'       => $userAnswer,
        'correct'    => $correct,
        'is_correct' => $userAnswer === $correct,
    ];
    $_SESSION['lesson_answers'] = $answers;

    if ($step >= $total) {
        $finished = true;

        // Aktualizacja streaku
        $stStmt = $pdo->prepare("SELECT streak, last_activity FROM users WHERE id = ?");
        $stStmt->execute([$user_id]);
        $stUser    = $stStmt->fetch();
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($stUser['last_activity'] === $today) {
            $new_streak = $stUser['streak'] ?? 1;
        } elseif ($stUser['last_activity'] === $yesterday) {
            $new_streak = ($stUser['streak'] ?? 0) + 1;
        } else {
            $new_streak = 1;
        }

        $upStmt = $pdo->prepare("UPDATE users SET streak = ?, last_activity = ? WHERE id = ?");
        $upStmt->execute([$new_streak, $today, $user_id]);

        // Zapis wyniku
        $correctCount   = 0;
        foreach ($answers as $a) {
            if ($a['is_correct']) $correctCount++;
        }
        $incorrectCount = $total - $correctCount;

        $insStmt = $pdo->prepare("INSERT INTO lessons (user_id, correct, incorrect, created_at) VALUES (?, ?, ?, NOW())");
        $insStmt->execute([$user_id, $correctCount, $incorrectCount]);
        $lesson_id = $pdo->lastInsertId();

        $ansStmt = $pdo->prepare("INSERT INTO lesson_answers (lesson_id, pl_word, user_answer, correct_answer, is_correct) VALUES (?, ?, ?, ?, ?)");
        foreach ($answers as $a) {
            $ansStmt->execute([$lesson_id, $a['pl'], $a['user'], $a['correct'], $a['is_correct']]);
        }

       // Odblokuj za wynik
if ($correctCount == $total) {
    unlockAchievement($pdo, $user_id, 'perfect_lesson');
}

// Sprawdz wszystkie osiagniecia oparte na statystykach
checkAchievements($pdo, $user_id);

    } else {
        header("Location: lesson.php?step=" . ($step + 1));
        exit;
    }
}

// Oblicz wynik do podsumowania
$correctCount = 0;
foreach ($answers as $a) {
    if ($a['is_correct']) $correctCount++;
}
$percent = $total > 0 ? round(($correctCount / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Lekcja - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        /* Pasek postepu */
        .progress { background: rgba(255,255,255,.08); border-radius: 99px; height: 6px; }
        .progress-bar { background: #6366f1; border-radius: 99px; transition: width .4s ease; }

        /* Karta pytania */
        .question-card {
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 580px;
            width: 100%;
        }

        .pl-word-display {
            font-size: 2rem;
            font-weight: 700;
            color: #e2e8f0;
            text-align: center;
            padding: 1.5rem;
            background: rgba(99,102,241,.08);
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 14px;
            margin-bottom: 1.5rem;
            letter-spacing: .01em;
        }

        .form-control {
            background: rgba(255,255,255,.06) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: #e2e8f0 !important;
            border-radius: 12px !important;
            font-size: 1.1rem !important;
            padding: .75rem 1rem !important;
            text-align: center;
        }
        .form-control:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2) !important;
            background: rgba(255,255,255,.09) !important;
        }
        .form-control::placeholder { color: rgba(255,255,255,.25) !important; }

        /* Karta wyniku */
        .score-ring {
            width: 120px; height: 120px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column;
            margin: 0 auto 1.5rem;
            font-size: 2rem; font-weight: 700;
        }
        .score-ring.great  { background: rgba(34,197,94,.12);  border: 3px solid rgba(34,197,94,.4);  color: #4ade80; }
        .score-ring.good   { background: rgba(99,102,241,.12); border: 3px solid rgba(99,102,241,.4); color: #818cf8; }
        .score-ring.poor   { background: rgba(239,68,68,.1);   border: 3px solid rgba(239,68,68,.35); color: #f87171; }

        /* Wiersze odpowiedzi */
        .answer-row {
            display: flex; align-items: center; gap: 1rem;
            padding: .75rem 1rem; border-radius: 10px;
            margin-bottom: .5rem;
        }
        .answer-row.correct { background: rgba(34,197,94,.08);  border: 1px solid rgba(34,197,94,.2); }
        .answer-row.wrong   { background: rgba(239,68,68,.07);  border: 1px solid rgba(239,68,68,.2); }

        .answer-col { flex: 1; min-width: 0; font-size: .9rem; }
        .answer-col .label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.3); margin-bottom: 2px; }
        .answer-col .val { color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .answer-col .val.wrong-val { color: #f87171; text-decoration: line-through; opacity: .7; }
        .answer-col .val.correct-val { color: #4ade80; }

        /* Stan pusty */
        .empty-card {
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            padding: 3rem 2rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
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
                    <a class="nav-link active d-flex align-items-center gap-1" href="lesson.php">
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

<div class="container d-flex justify-content-center align-items-start py-5">

<?php if (!$hasFlashcards): ?>

    <!-- STAN PUSTY -->
    <div class="empty-card">
        <div style="width:64px;height:64px;background:rgba(99,102,241,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="ti ti-books" style="font-size:1.75rem;color:#818cf8;"></i>
        </div>
        <h2 class="h5 fw-semibold text-light mb-2">Brak fiszek</h2>
        <p class="text-secondary small mb-4">
            Nie mozesz rozpoczac lekcji, poniewaz nie masz jeszcze zadnych fiszek w swoim zestawie.
        </p>
        <a href="flashcards.php"
           class="btn fw-semibold d-inline-flex align-items-center gap-2"
           style="background:#6366f1;border:none;color:white;border-radius:10px;padding:.6rem 1.5rem;">
            <i class="ti ti-plus"></i> Dodaj pierwsze fiszki
        </a>
    </div>

<?php elseif (!$finished): ?>

    <!-- WIDOK PYTANIA -->
    <div class="question-card">

        <!-- Naglowek z postepem -->
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="small text-secondary">
                Pytanie <span class="text-light fw-semibold"><?= $step ?></span> z <?= $total ?>
            </span>
            <span class="small" style="color:#a5b4fc;font-weight:600;">
                <?= $step - 1 ?> / <?= $total ?> ukończone
            </span>
        </div>
        <div class="progress mb-4">
            <div class="progress-bar" style="width: <?= round((($step - 1) / $total) * 100) ?>%"></div>
        </div>

        <!-- Slowo do przetlumaczenia -->
        <p class="text-secondary small text-center mb-2">Przetłumacz na angielski:</p>
        <div class="pl-word-display">
            <?= htmlspecialchars($cards[$step - 1]['pl_word']) ?>
        </div>

        <!-- Formularz odpowiedzi -->
        <form method="post" autocomplete="off">
            <input type="hidden" name="correct" value="<?= htmlspecialchars($cards[$step - 1]['en_word']) ?>">
            <input type="hidden" name="pl"      value="<?= htmlspecialchars($cards[$step - 1]['pl_word']) ?>">

            <div class="mb-3">
                <input type="text"
                       name="answer"
                       class="form-control"
                       placeholder="Wpisz odpowiedź po angielsku..."
                       autofocus
                       required>
            </div>

            <button type="submit"
                    class="btn w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                    style="background:#6366f1;border:none;color:white;border-radius:10px;">
                <i class="ti ti-check"></i>
                <?= $step < $total ? 'Zatwierdź i dalej' : 'Zakończ lekcję' ?>
            </button>
        </form>

        <!-- Porzucenie lekcji -->
        <p class="text-center mt-3 mb-0">
            <a href="index.php"
               class="text-secondary small text-decoration-none"
               onclick="return confirm('Czy na pewno chcesz opuscic lekcje? Postep zostanie utracony.')">
                <i class="ti ti-x" style="font-size:.8rem;"></i> Przerwij lekcje
            </a>
        </p>

    </div>

<?php else: ?>

    <!-- PODSUMOWANIE -->
    <?php
        unset($_SESSION['lesson_answers']);
        $scoreClass = $percent >= 80 ? 'great' : ($percent >= 50 ? 'good' : 'poor');
        $scoreMsg   = $percent >= 80 ? 'Swietny wynik!' : ($percent >= 50 ? 'Dobry wynik!' : 'Cwicz dalej!');
    ?>

    <div style="max-width:620px;width:100%;">

        <!-- Wynik -->
        <div class="text-center mb-4">
            <div class="score-ring <?= $scoreClass ?>">
                <span><?= $correctCount ?>/<?= $total ?></span>
            </div>
            <h2 class="h4 fw-semibold text-light mb-1"><?= $scoreMsg ?></h2>
            <p class="text-secondary small mb-0">
                <?= $correctCount ?> poprawnych &nbsp;&middot;&nbsp; <?= $total - $correctCount ?> blednych &nbsp;&middot;&nbsp; <?= $percent ?>% skutecznosci
            </p>
        </div>

        <!-- Lista odpowiedzi -->
        <div class="mb-4">
            <?php foreach ($answers as $a): ?>
            <div class="answer-row <?= $a['is_correct'] ? 'correct' : 'wrong' ?>">

                <div class="answer-col" style="flex:0 0 auto;min-width:0;max-width:33%;">
                    <div class="label">Pytanie</div>
                    <div class="val"><?= htmlspecialchars($a['pl']) ?></div>
                </div>

                <i class="ti ti-arrow-narrow-right" style="color:rgba(255,255,255,.2);flex-shrink:0;"></i>

                <div class="answer-col">
                    <div class="label">Twoja odpowiedz</div>
                    <div class="val <?= $a['is_correct'] ? '' : 'wrong-val' ?>">
                        <?= htmlspecialchars($a['user']) !== '' ? htmlspecialchars($a['user']) : '(brak)' ?>
                    </div>
                </div>

                <?php if (!$a['is_correct']): ?>
                <div class="answer-col">
                    <div class="label">Poprawna</div>
                    <div class="val correct-val"><?= htmlspecialchars($a['correct']) ?></div>
                </div>
                <?php endif; ?>

                <div style="flex-shrink:0;">
                    <?php if ($a['is_correct']): ?>
                        <i class="ti ti-circle-check" style="color:#4ade80;font-size:1.2rem;"></i>
                    <?php else: ?>
                        <i class="ti ti-circle-x" style="color:#f87171;font-size:1.2rem;"></i>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- Przyciski akcji -->
        <div class="d-flex gap-3">
            <a href="lesson.php"
               class="btn flex-fill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
               style="background:#6366f1;border:none;color:white;border-radius:10px;">
                <i class="ti ti-refresh"></i> Nowa lekcja
            </a>
            <a href="index.php"
               class="btn flex-fill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
               style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:10px;">
                <i class="ti ti-home"></i> Powrot do menu
            </a>
        </div>

    </div>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>