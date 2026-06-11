<?php

// Wszystkie osiagniecia aplikacji
// Kod => [nazwa, opis]
function getAllAchievements() {
    return [
        'first_lesson'     => ['Pierwszy krok',        'Ukon pierwsza lekcje'],
        'perfect_lesson'   => ['Perfekcja',             'Uzyskaj wynik 10/10 w lekcji'],
        'lessons_5'        => ['Zapracowany',           'Ukon lacznie 5 lekcji'],
        'lessons_10'       => ['Weteran',               'Ukon lacznie 10 lekcji'],
        'lessons_25'       => ['Mistrz lekcji',         'Ukon lacznie 25 lekcji'],
        'streak_3'         => ['Regularny',             'Bądz aktywny przez 3 dni z rzedu'],
        'streak_7'         => ['Tygodniowy wojownik',   'Bądz aktywny przez 7 dni z rzedu'],
        'streak_14'        => ['Nieustepliwy',          'Bądz aktywny przez 14 dni z rzedu'],
        'first_flashcard'  => ['Zbieracz slow',         'Dodaj swoja pierwsza fiszke'],
        'flashcards_20'    => ['Kolekcjoner',           'Miej co najmniej 20 fiszek'],
        'win_hidden_word'  => ['Detektyw slow',         'Odgadnij slowo w minigre Ukryte slowo'],
    ];
}

// Odblokuj osiagniecie po kodzie (jesli juz odblokowane - nic nie robi)
function unlockAchievement($pdo, $user_id, $code) {
    // Znajdz osiagniecie w bazie
    $stmt = $pdo->prepare("SELECT id FROM achievements WHERE code = ?");
    $stmt->execute([$code]);
    $achievement = $stmt->fetch();

    // Jesli nie ma w bazie - wstaw je automatycznie i sprobuj ponownie
    if (!$achievement) {
        $all = getAllAchievements();
        if (!isset($all[$code])) return false;

        $ins = $pdo->prepare("INSERT IGNORE INTO achievements (code, name, description) VALUES (?, ?, ?)");
        $ins->execute([$code, $all[$code][0], $all[$code][1]]);

        $stmt->execute([$code]);
        $achievement = $stmt->fetch();
        if (!$achievement) return false;
    }

    // Sprawdz czy juz odblokowane
    $check = $pdo->prepare("
        SELECT id FROM user_achievements
        WHERE user_id = ? AND achievement_id = ?
    ");
    $check->execute([$user_id, $achievement['id']]);
    if ($check->fetch()) return false;

    // Odblokuj
    $insert = $pdo->prepare("
        INSERT INTO user_achievements (user_id, achievement_id, unlocked_at)
        VALUES (?, ?, NOW())
    ");
    $insert->execute([$user_id, $achievement['id']]);
    return true;
}

// Sprawdz i odblokuj wszystkie osiagniecia oparte na statystykach uzytkownika
function checkAchievements($pdo, $user_id) {

    // Dane uzytkownika
    $stmt = $pdo->prepare("SELECT streak FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Streak
    if ($user['streak'] >= 3)  unlockAchievement($pdo, $user_id, 'streak_3');
    if ($user['streak'] >= 7)  unlockAchievement($pdo, $user_id, 'streak_7');
    if ($user['streak'] >= 14) unlockAchievement($pdo, $user_id, 'streak_14');

    // Liczba lekcji
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lessons_count = (int)$stmt->fetchColumn();

    if ($lessons_count >= 1)  unlockAchievement($pdo, $user_id, 'first_lesson');
    if ($lessons_count >= 5)  unlockAchievement($pdo, $user_id, 'lessons_5');
    if ($lessons_count >= 10) unlockAchievement($pdo, $user_id, 'lessons_10');
    if ($lessons_count >= 25) unlockAchievement($pdo, $user_id, 'lessons_25');

    // Liczba fiszek
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM flashcards WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cards_count = (int)$stmt->fetchColumn();

    if ($cards_count >= 1)  unlockAchievement($pdo, $user_id, 'first_flashcard');
    if ($cards_count >= 20) unlockAchievement($pdo, $user_id, 'flashcards_20');
}