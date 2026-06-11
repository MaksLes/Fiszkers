<?php
// Upewnij się, że sesja jest uruchomiona
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprawdzenie, czy użytkownik jest zalogowany
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

// Sprawdzenie, czy użytkownik jest administratorem
function is_admin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Opcjonalnie: zwróć ID użytkownika
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

// Opcjonalnie: wylogowanie
function logout(): void {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
