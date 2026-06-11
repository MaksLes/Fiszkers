<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Nieprawidłowe dane logowania lub konto nieaktywne.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie – Fiszkers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .form-control::placeholder {
            color: #555;
        }
        .icon-wrap {
            width: 56px;
            height: 56px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .btn-register {
            background: #6366f1;
            border: none;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-register:hover {
            background: #5254cc;
        }
        .btn-register:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="card bg-dark border-secondary shadow-lg p-4 w-100" style="max-width: 420px;">

        <div class="text-center mb-4">
            <div class="icon-wrap">
                <i class="ti ti-login text-primary" style="font-size: 24px;"></i>
            </div>
            <h1 class="h4 fw-semibold mb-1 text-light">Witaj z powrotem</h1>
            <p class="text-secondary small mb-0">Zaloguj się na swoje konto Fiszkers</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>

            <div class="mb-3">
                <label for="email" class="form-label small text-secondary mb-1">Adres e-mail</label>
                <input
                    type="email"
                    class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                    id="email"
                    name="email"
                    placeholder="twojemail@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small text-secondary mb-1">Hasło</label>
                <input
                    type="password"
                    class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                    id="password"
                    name="password"
                    placeholder="Twoje hasło"
                    required>
            </div>

            <button type="submit" class="btn btn-register btn-primary w-100 py-2 fw-semibold">
                Zaloguj się
            </button>

        </form>

        <p class="text-center small text-secondary mt-3 mb-0">
            Nie masz konta?
            <a href="register.php" class="text-primary text-decoration-none fw-semibold">Zarejestruj się</a>
        </p>

    </div>
</div>

</body>
</html>