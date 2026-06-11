<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name']);
    $lastName  = sanitize($_POST['last_name']);
    $email     = sanitize($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Hasła nie są takie same.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Użytkownik z takim adresem e-mail już istnieje.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $code = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, activation_code)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firstName, $lastName, $email, $hash, $code]);
            send_activation_email($email, $code);
            $success = "Rejestracja zakończona. Sprawdź e-mail i aktywuj konto.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rejestracja – Fiszkers</title>
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
    <div class="card bg-dark border-secondary shadow-lg p-4 w-100" style="max-width: 480px;">

        <div class="text-center mb-4">
            <div class="icon-wrap">
                <i class="ti ti-user-plus text-primary" style="font-size: 24px;"></i>
            </div>
            <h1 class="h4 fw-semibold mb-1 text-light">Utwórz konto</h1>
            <p class="text-secondary small mb-0">Wypełnij formularz, aby dołączyć do Fiszkers</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 small"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success rounded-3 small"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="first_name" class="form-label small text-secondary mb-1">Imię</label>
                    <input
                        type="text"
                        class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                        id="first_name"
                        name="first_name"
                        placeholder="np. Jan"
                        autocomplete="off"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                        required>
                </div>
                <div class="col-6">
                    <label for="last_name" class="form-label small text-secondary mb-1">Nazwisko</label>
                    <input
                        type="text"
                        class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                        id="last_name"
                        name="last_name"
                        placeholder="np. Kowalski"
                        autocomplete="off"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                        required>
                </div>
            </div>

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

            <div class="mb-3">
                <label for="password" class="form-label small text-secondary mb-1">Hasło</label>
                <input
                    type="password"
                    class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                    id="password"
                    name="password"
                    placeholder="Minimum 8 znaków"
                    required>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label small text-secondary mb-1">Powtórz hasło</label>
                <input
                    type="password"
                    class="form-control form-control-lg bg-dark-subtle border-secondary text-light"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Wpisz hasło ponownie"
                    required>
            </div>

            <button type="submit" class="btn btn-register btn-primary w-100 py-2 fw-semibold">
                Zarejestruj się
            </button>

        </form>

        <p class="text-center small text-secondary mt-3 mb-0">
            Masz już konto?
            <a href="login.php" class="text-primary text-decoration-none fw-semibold">Zaloguj się</a>
        </p>

    </div>
</div>

</body>
</html>