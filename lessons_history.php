<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!is_logged_in()) header("Location: login.php");

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM lessons WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$lessons = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Moje lekcje</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2>Historia lekcji</h2>
  <a href="index.php" class="btn btn-secondary mb-3">← Powrót</a>

  <table class="table table-bordered table-striped">
    <thead class="table-light">
      <tr>
        <th>Data</th>
        <th>Poprawne</th>
        <th>Błędne</th>
        <th>Akcja</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($lessons as $l): ?>
        <tr>
          <td><?= $l['created_at'] ?></td>
          <td><?= $l['correct'] ?></td>
          <td><?= $l['incorrect'] ?></td>
          <td><a href="lesson_detail.php?id=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Zobacz</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
