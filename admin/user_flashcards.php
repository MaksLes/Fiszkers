<?php
require '../includes/auth.php';
require '../includes/db.php';

if (!is_logged_in() || !is_admin()) exit;

$id = $_GET['id'] ?? null;
if (!$id) header("Location: admin_panel.php");

$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE user_id = ?");
$stmt->execute([$id]);
$fiszki = $stmt->fetchAll();

// Usuwanie fiszki
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    $del = $pdo->prepare("DELETE FROM flashcards WHERE id = ? AND user_id = ?");
    $del->execute([$delete_id, $id]);

    header("Location: user_flashcards.php?id=" . $id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Fiszki użytkownika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/theme.css">
</head>
<body>
<div class="container mt-5">
  <h2>Fiszki użytkownika #<?= $id ?></h2>
  <a href="admin_panel.php" class="btn btn-secondary mb-3">← Powrót</a>

  <table class="admin-table">
    <thead class="table-light">
  <tr>
    <th>PL</th>
    <th>EN</th>
    <th>Akcje</th>
  </tr>
</thead>
    <tbody>
     <?php foreach ($fiszki as $f): ?>
<tr>
  <td><?= htmlspecialchars($f['pl_word']) ?></td>
  <td><?= htmlspecialchars($f['en_word']) ?></td>
  <td>
    <form method="post" action="user_flashcards.php?id=<?= $id ?>" class="d-inline">
        <input type="hidden" name="delete_id" value="<?= $f['id'] ?>">
        <button class="btn btn-danger btn-sm" onclick="return confirm('Usunąć tę fiszkę?')">Usuń</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
