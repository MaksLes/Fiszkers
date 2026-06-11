<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$meStmt = $pdo->prepare("SELECT first_name, is_admin FROM users WHERE id = ?");
$meStmt->execute([$user_id]);
$me         = $meStmt->fetch();
$first_name = htmlspecialchars($me['first_name']);

// Pobierz wszystkich uzytkownikow z pelnych danych
$stmt  = $pdo->query("
    SELECT id, first_name, last_name, email, is_active, is_admin, created_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

// Statystyki
$total    = count($users);
$active   = count(array_filter($users, function($u) { return $u['is_active']; }));
$inactive = $total - $active;
$admins   = count(array_filter($users, function($u) { return $u['is_admin']; }));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel administratora - Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../css/theme.css">
    <style>
        .nav-link { color: rgba(255,255,255,.65) !important; transition: color .15s; }
        .nav-link:hover, .nav-link.active { color: #fff !important; }

        .stat-card {
            background: rgba(99,102,241,.08);
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 12px;
            padding: 1rem 1.25rem;
        }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #a5b4fc; line-height: 1; }
        .stat-label { font-size: .72rem; color: rgba(255,255,255,.4); margin-top: .2rem;
                      text-transform: uppercase; letter-spacing: .05em; }

        /* Wiersz uzytkownika */
        .user-row {
            display: flex; align-items: center; gap: 1rem;
            background: #1e1e2e;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            padding: .85rem 1rem;
            transition: border-color .2s;
        }
        .user-row:hover { border-color: rgba(99,102,241,.35); }

        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(99,102,241,.2);
            border: 1px solid rgba(99,102,241,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; font-weight: 700; color: #a5b4fc;
            flex-shrink: 0;
        }
        .user-avatar.admin-avatar {
            background: rgba(251,191,36,.12);
            border-color: rgba(251,191,36,.35);
            color: #fbbf24;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: .9rem; font-weight: 500; color: #e2e8f0;
                     white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: .78rem; color: rgba(255,255,255,.35);
                      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .status-badge {
            font-size: .72rem; font-weight: 600; padding: .2rem .6rem;
            border-radius: 20px; flex-shrink: 0;
        }
        .status-active   { background: rgba(34,197,94,.12);  color: #4ade80; border: 1px solid rgba(34,197,94,.25); }
        .status-inactive { background: rgba(239,68,68,.1);   color: #f87171; border: 1px solid rgba(239,68,68,.2); }
        .status-admin    { background: rgba(251,191,36,.1);  color: #fbbf24; border: 1px solid rgba(251,191,36,.2); }

        .btn-icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid transparent;
            background: transparent; cursor: pointer;
            transition: background .15s, border-color .15s;
            color: rgba(255,255,255,.4);
            font-size: .95rem; text-decoration: none;
        }
        .btn-icon.edit:hover    { background: rgba(99,102,241,.15); border-color: rgba(99,102,241,.4); color: #818cf8; }
        .btn-icon.cards:hover   { background: rgba(56,189,248,.12); border-color: rgba(56,189,248,.4); color: #38bdf8; }
        .btn-icon.del:hover     { background: rgba(239,68,68,.12);  border-color: rgba(239,68,68,.4);  color: #f87171; }

        /* Filtry */
        .filter-btn {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.5);
            border-radius: 8px; padding: .3rem .85rem;
            font-size: .8rem; cursor: pointer; transition: all .15s;
        }
        .filter-btn.active, .filter-btn:hover {
            background: rgba(99,102,241,.15);
            border-color: rgba(99,102,241,.4);
            color: #a5b4fc;
        }

        .section-label {
            font-size: .7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: .08em; color: rgba(255,255,255,.3); margin-bottom: .75rem;
        }

        .join-date { font-size: .72rem; color: rgba(255,255,255,.25); flex-shrink: 0; }

        .empty-state { text-align: center; padding: 3rem; color: rgba(255,255,255,.25); }
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

<div class="container py-4" style="max-width: 900px;">

    <!-- NAGLOWEK -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 fw-semibold text-light mb-1 d-flex align-items-center gap-2">
                <i class="ti ti-shield-check" style="color:#fbbf24;"></i>
                Panel administratora
            </h1>
            <p class="text-secondary small mb-0">Zarzadzaj kontami uzytkownikow aplikacji</p>
        </div>
        <a href="../index.php"
           class="btn btn-sm d-flex align-items-center gap-1"
           style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);border-radius:8px;">
            <i class="ti ti-arrow-left"></i> Powrot
        </a>
    </div>

    <!-- STATYSTYKI -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Wszystkich</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:rgba(34,197,94,.07);border-color:rgba(34,197,94,.2);">
                <div class="stat-value" style="color:#4ade80;"><?= $active ?></div>
                <div class="stat-label">Aktywnych</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.2);">
                <div class="stat-value" style="color:#f87171;"><?= $inactive ?></div>
                <div class="stat-label">Nieaktywnych</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:rgba(251,191,36,.07);border-color:rgba(251,191,36,.2);">
                <div class="stat-value" style="color:#fbbf24;"><?= $admins ?></div>
                <div class="stat-label">Adminów</div>
            </div>
        </div>
    </div>

    <!-- FILTRY -->
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button class="filter-btn active" onclick="filterUsers('all', this)">
            Wszyscy (<?= $total ?>)
        </button>
        <button class="filter-btn" onclick="filterUsers('active', this)">
            Aktywni (<?= $active ?>)
        </button>
        <button class="filter-btn" onclick="filterUsers('inactive', this)">
            Nieaktywni (<?= $inactive ?>)
        </button>
        <button class="filter-btn" onclick="filterUsers('admin', this)">
            Adminowie (<?= $admins ?>)
        </button>
    </div>

    <!-- LISTA UZYTKOWNIKOW -->
    <div class="section-label">Lista uzytkownikow</div>
    <div id="userList" class="d-flex flex-column gap-2">

        <?php foreach ($users as $u):
            $initials   = strtoupper(mb_substr($u['first_name'], 0, 1) . mb_substr($u['last_name'], 0, 1));
            $isAdmin    = (bool)$u['is_admin'];
            $isActive   = (bool)$u['is_active'];
            $dataStatus = $isAdmin ? 'admin' : ($isActive ? 'active' : 'inactive');
        ?>
        <div class="user-row" data-status="<?= $dataStatus ?>">

            <!-- Avatar -->
            <div class="user-avatar <?= $isAdmin ? 'admin-avatar' : '' ?>">
                <?= $initials ?>
            </div>

            <!-- Dane -->
            <div class="user-info">
                <div class="user-name">
                    <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                </div>
                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
            </div>

            <!-- Odznaki -->
            <div class="d-flex gap-1 flex-shrink-0">
                <?php if ($isAdmin): ?>
                    <span class="status-badge status-admin">
                        <i class="ti ti-shield-check" style="font-size:.7rem;"></i> Admin
                    </span>
                <?php endif; ?>
                <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                    <?= $isActive ? 'Aktywny' : 'Nieaktywny' ?>
                </span>
            </div>

            <!-- Data dolaczenia -->
            <div class="join-date d-none d-md-block">
                <?= date('d.m.Y', strtotime($u['created_at'])) ?>
            </div>

            <!-- Akcje -->
            <div class="d-flex gap-1 flex-shrink-0">
                <a href="user_edit.php?id=<?= $u['id'] ?>"
                   class="btn-icon edit" title="Edytuj uzytkownika">
                    <i class="ti ti-pencil"></i>
                </a>
                <a href="user_flashcards.php?id=<?= $u['id'] ?>"
                   class="btn-icon cards" title="Fiszki uzytkownika">
                    <i class="ti ti-cards"></i>
                </a>
                <a href="user_delete.php?id=<?= $u['id'] ?>"
                   class="btn-icon del" title="Usun uzytkownika"
                   onclick="return confirm('Usunac uzytkownika <?= htmlspecialchars($u['email'], ENT_QUOTES) ?>? Tej operacji nie mozna cofnac.')">
                    <i class="ti ti-trash"></i>
                </a>
            </div>

        </div>
        <?php endforeach; ?>

    </div>

    <p id="noResults" class="empty-state" style="display:none;">
        <i class="ti ti-users-minus" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
        Brak uzytkownikow spelniajacych kryteria filtra.
    </p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterUsers(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    btn.classList.add('active');

    var rows    = document.querySelectorAll('.user-row');
    var visible = 0;

    rows.forEach(function(row) {
        var show = status === 'all' || row.dataset.status === status;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}
</script>

</body>
</html>