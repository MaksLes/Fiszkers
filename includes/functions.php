<?php
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(trim($data));
    }
}

if (!function_exists('assign_default_flashcards')) {
    function assign_default_flashcards($pdo, $user_id) {
        $defaults = [
            ['kot', 'cat'], ['pies', 'dog'], ['dom', 'house'], ['woda', 'water'],
            ['chleb', 'bread'], ['dzień', 'day'], ['noc', 'night'], ['szkoła', 'school'],
            ['książka', 'book'], ['komputer', 'computer'], ['okno', 'window'], ['drzwi', 'door'],
            ['jabłko', 'apple'], ['samochód', 'car'], ['miasto', 'city'], ['ulica', 'street'],
            ['czas', 'time'], ['nauczyciel', 'teacher'], ['uczeń', 'student'], ['język', 'language']
        ];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM flashcards WHERE user_id = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO flashcards (user_id, pl_word, en_word) VALUES (?, ?, ?)");
        $stmt->execute([$user_id]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            foreach ($defaults as $pair) {
                $stmtInsert->execute([$user_id, $pair[0], $pair[1]]);
            }
        }
    }
}
?>