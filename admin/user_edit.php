<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!is_logged_in() || !is_admin()) exit;

$id = $_GET['id'] ?? null;
if (!$id) header("Location: admin_panel.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    $admin = isset($_POST['is_admin']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE users SET email = ?, first_name = ?, last_name = ?, is_active = ?, is_admin = ? WHERE id = ?");
    $stmt->execute([$email, $first_name, $last_name, $active, $admin, $id]);

    header("Location: admin_panel.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Edytuj użytkownika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/theme.css">
</head>
<body>
  <body class="bg-light">
      <div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>
<div class="container mt-5">
  <h2>Edytuj użytkownika</h2>
  <form method="post">
    <div class="mb-3">
      <label>Email:</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Imię:</label>
      <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Nazwisko:</label>
      <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
      <label class="form-check-label">Aktywny</label>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>>
      <label class="form-check-label">Administrator</label>
    </div>
    <button type="submit" class="btn btn-success">Zapisz</button>
    <a href="admin_panel.php" class="btn btn-secondary">Anuluj</a>
  </form>
</div>
</body>
</html>
