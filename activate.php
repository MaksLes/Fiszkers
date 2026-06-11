<?php
require 'includes/db.php';

$code = $_GET['code'] ?? '';
if ($code) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1, activation_code = NULL WHERE activation_code = ?");
    $stmt->execute([$code]);
    $success = $stmt->rowCount() > 0;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Aktywacja konta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <?php if (!empty($success)): ?>
    <div class="alert alert-success">Twoje konto zostało aktywowane! <a href="login.php">Zaloguj się</a></div>
  <?php else: ?>
    <div class="alert alert-danger">Nieprawidłowy lub wygasły kod aktywacyjny.</div>
  <?php endif; ?>
</div>
</body>
</html>
