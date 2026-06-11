<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    header("Location: ../login.php");
    exit;
}

$stmt = $pdo->query("SELECT id, email, is_active FROM users ORDER BY id");
$users = $stmt->fetchAll();

if ($_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Panel administratora</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/theme.css">
</head>
<body>
  <body class="bg-light">
      <div class="bg-spot bg-spot-1"></div>
<div class="bg-spot bg-spot-2"></div>
<div class="container admin-container">
  <h2>Panel administratora</h2>
  <a href="../index.php" class="btn btn-secondary mb-3">← Powrót</a>

  <table class="admin-table">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Status</th>
        <th>Akcje</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= $u['is_active'] ? 'Aktywny' : 'Nieaktywny' ?></td>
          <td>
            <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm">Edytuj</a>
            <a href="user_delete.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Usunąć użytkownika?')">Usuń</a>
            <a href="user_flashcards.php?id=<?= $u['id'] ?>" class="btn btn-info btn-sm">Fiszki</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
