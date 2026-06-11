# Aplikacja "Fiszkers"

Opis projektu:

"Fiszkers" – Twoja osobista maszyna do nauki!
Zamień naukę w przygodę z Fiszkers – nowoczesną aplikacją do tworzenia, przeglądania i utrwalania wiedzy za pomocą fiszek. Twórz własne zestawy lub korzystaj z gotowych. Dzięki funkcji Lekcji, czyli krótkim testom, sprawdzisz swoją pamięć i umiejętności w zaledwie kilka minut, natomiast dzięki mini-grze "Ukryte słowo" możesz utrwalić swoją wiedzę, którą już nabyłeś/aś. Każda lekcja to 10 wybranych przez system fiszek, które pomogą Ci skutecznie powtórzyć materiał i śledzić postępy. Gdy wybierasz jednak naukę poprzez grę w wisielca, możesz popełnić tylko 10 błędów w innym wypadku słowo zostanie zresetowane i zmienione na inne.
Prosto, szybko, skutecznie – ucz się kiedy chcesz i jak chcesz. Z Fiszkers nauka staje się częścią Twojego dnia, a nie obowiązkiem.

## Wymagania systemowe

- wersja apache'a - 2.4.58 (Win64)
- wersja PHP'a - 8.2.12
- wersja MySQL - 10.4.32 MariaDB

## Instalacja

Krok 1 -Pobranie i instalacja XAMPP
Pobierz pakiet XAMPP w wersji 8.2.x ze strony https://www.apachefriends.org i zainstaluj go w domyślnej lokalizacji (C:\xampp). Podczas instalacji upewnij się, że zaznaczone są co najmniej komponenty: Apache, MySQL i PHP.

Krok 2 -Umieszczenie plików aplikacji
Skopiuj lub wypakuj folder z aplikacją Fiszkers do katalogu C:\xampp\htdocs\Fiszkers\ (nazwa folderu musi być dokładnie taka -"Fiszkers" z wielką literą -ponieważ adresy URL są wrażliwe na wielkość liter).

Krok 3 -Uruchomienie serwera
Otwórz XAMPP Control Panel (skrót na pulpicie lub C:\xampp\xampp-control.exe). Kliknij przycisk Start przy Apache, a następnie Start przy MySQL. Obie usługi powinny zaświecić się na zielono ze statusem "Running".

Krok 4 -Uruchomienie instalatora
Otwórz przeglądarkę internetową i wpisz w pasku adresu:
http://localhost/Fiszkers/install.php
Wyświetli się strona instalatora z formularzem konfiguracyjnym.

Krok 5 -Wypełnienie formularza instalatora
Na stronie instalatora uzupełnij następujące pola:
• Host bazy danych: localhost (domyślna wartość -pozostaw bez zmian).
• Nazwa bazy danych: fiszkers (zostanie utworzona automatycznie).
• Użytkownik bazy danych: root (domyślny użytkownik XAMPP bez hasła).
• Hasło: pozostaw puste (domyślna konfiguracja XAMPP).
• Email administratora: podaj adres e-mail konta administratora (np. admin@admin.com).
• Hasło administratora: ustal silne hasło dla konta administracyjnego.

Krok 6 -Zakończenie instalacji
Kliknij przycisk Zainstaluj aplikację. Instalator automatycznie: utworzy bazę danych fiszkers, wygeneruje wszystkie tabele, doda konto administratora, wczyta 20 przykładowych fiszek globalnych oraz zapisze plik includes/config.php z danymi połączenia. Po zakończeniu zostaniesz przekierowany na stronę logowania.

Krok 7 -Pierwsze logowanie
Zaloguj się na stronie http://localhost/Fiszkers/login.php używając podanego w instalatorze adresu e-mail i hasła administratora. Aplikacja jest gotowa do użycia.

UWAGA: Plik install.php można uruchomić tylko jeden raz. Jeśli plik includes/config.php istnieje i nie jest pusty, instalator wyświetli komunikat o zakończonej instalacji. Aby zainstalować ponownie (np. przy testach), należy ręcznie usunąć plik includes/config.php.
UWAGA: Aplikacja wymaga konfiguracji serwera SMTP do wysyłania e-maili aktywacyjnych.

W środowisku lokalnym XAMPP funkcja ta może nie działać bez dodatkowej konfiguracji sendmail.
[ZALECANE] - W takim przypadku administrator może ręcznie aktywować konta użytkowników w panelu administracyjnym poprzez ustawienie flagi is_active = 1 bezpośrednio przez phpMyAdmin.
[ZALECANE] - Zalecane jest również włączenie akceleracji grafiki w przeglądarce internetowej. W przypadku Google Chrome: USTAWIENIA -> SYSTEM -> „Używaj akceleracji grafiki, gdy jest dostępna” - WŁĄCZ. Zapewni to komfort podczas korzystania z aplikacji.

## Autor

- **Maksymilian Leśniak**
- _nr albumu: 414802_

## Wykorzystane zewnętrzne biblioteki

- bootstrap (5.3.0)
- Chart.js (wykresy postępów)
- Font Awesome (ikony)
