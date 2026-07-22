# Aplikacja "Fiszkers"

## Opis projektu

"Fiszkers" – Twoja osobista maszyna do nauki!

Zamień naukę w przygodę z Fiszkers – nowoczesną aplikacją do tworzenia,
przeglądania i utrwalania wiedzy za pomocą fiszek. Twórz własne zestawy
lub korzystaj z gotowych fiszek globalnych. Dzięki funkcji Lekcji, czyli
krótkim quizom z 10 pytaniami, sprawdzisz swoją pamięć i umiejętności
w zaledwie kilka minut. Minigrę "Ukryte słowo" (wisielec) możesz
wykorzystać do utrwalenia już nabytej wiedzy – masz maksymalnie 10 prób,
zanim słowo zostanie zmienione na nowe.

Śledź swoje postępy dzięki statystykom, wykresem i systemowi osiągnięć,
który nagradza regularną naukę i dobre wyniki. Utrzymuj streak aktywności
i odblokowuj kolejne odznaki.

Prosto, szybko, skutecznie – ucz się kiedy chcesz i jak chcesz.
Z Fiszkers nauka staje się częścią Twojego dnia, a nie obowiązkiem.

---

## Funkcjonalności

- Tworzenie i zarządzanie własnym zestawem fiszek (pary PL↔EN)
- Lekcje: quiz z 10 losowych fiszek z natychmiastową informacją zwrotną
- Historia lekcji z podglądem każdej odpowiedzi
- Minigrę "Ukryte słowo" (wisielec) – odgadywanie angielskich słów
- System osiągnięć nagradzający postępy i regularność nauki
- Licznik streak – dni ciągłej aktywności
- Wykresy postępów na stronie głównej (Chart.js)
- Panel administratora: zarządzanie użytkownikami i fiszkami
- Aktywacja konta przez e-mail

---

## Wymagania systemowe

- Apache 2.4.58 (Win64)
- PHP 8.2.12
- MySQL 10.4.32 MariaDB
- Dowolna nowoczesna przeglądarka (Chrome 100+, Firefox 100+, Edge 100+)

---

## Instalacja

**Krok 1 – Pobranie i instalacja XAMPP**

Pobierz pakiet XAMPP w wersji 8.2.x ze strony https://www.apachefriends.org
i zainstaluj go w domyślnej lokalizacji (`C:\xampp`). Podczas instalacji
upewnij się, że zaznaczone są komponenty: Apache, MySQL i PHP.

**Krok 2 – Umieszczenie plików aplikacji**

Skopiuj folder z aplikacją do katalogu `C:\xampp\htdocs\`
(nazwa folderu musi być dokładnie taka: Fiszkers - wielkość liter ma znaczenie).

**Krok 3 – Uruchomienie serwera**

Otwórz XAMPP Control Panel i kliknij **Start** przy Apache i MySQL.
Obie usługi powinny mieć status "Running" (zielone podświetlenie).

**Krok 4 – Uruchomienie instalatora**

Otwórz przeglądarkę i wpisz: http://localhost/Fiszkers/install.php
**Krok 5 – Wypełnienie formularza instalatora**

| Pole                   | Wartość                |
| ---------------------- | ---------------------- |
| Host bazy danych       | `localhost`            |
| Nazwa bazy danych      | `fiszkers`             |
| Użytkownik bazy danych | `root`                 |
| Hasło bazy danych      | _(pozostaw puste)_     |
| Email administratora   | np. `admin@admin.com`  |
| Hasło administratora   | dowolne, min. 8 znaków |

**Krok 6 – Zakończenie instalacji**

Kliknij **Zainstaluj Fiszkers**. Instalator automatycznie:

- utworzy bazę danych i 7 tabel
- doda konto administratora
- wczyta 30 startowych fiszek globalnych
- doda 11 osiągnięć do systemu
- zapisze plik `includes/config.php`

Po zakończeniu zostaniesz przekierowany na stronę logowania.

**Krok 7 – Pierwsze logowanie**

Zaloguj się na `http://localhost/Fiszkers/login.php` używając danych
podanych w instalatorze.

---

## Uwagi

> **UWAGA:** Plik `install.php` można uruchomić tylko raz. Jeśli plik
> `includes/config.php` istnieje i nie jest pusty, instalator wyświetli
> komunikat o zakończonej instalacji. Aby zainstalować ponownie, usuń
> plik `includes/config.php`.

> **UWAGA:** Plik `includes/config.php` nie jest częścią repozytorium — jest
> generowany automatycznie przez `install.php` podczas instalacji. Jeśli
> zobaczysz komunikat "Aplikacja nie jest jeszcze skonfigurowana", oznacza to,
> że instalator nie został jeszcze uruchomiony.

> **UWAGA:** Aplikacja wymaga konfiguracji SMTP do wysyłania e-maili
> aktywacyjnych. W środowisku lokalnym XAMPP funkcja ta może nie działać.

> **Zalecane:** administrator może ręcznie aktywować konta w panelu
> administracyjnym (zakładka edycji użytkownika → przełącznik "Konto aktywne").

> **ZALECANE:** Włącz akcelerację grafiki w przeglądarce.
> Chrome: Ustawienia → System → _"Używaj akceleracji grafiki, gdy jest dostępna"_ → WŁĄCZ.

## Autor

- **Maksymilian Leśniak**
- _nr albumu: 414802_

## Wykorzystane zewnętrzne biblioteki

|------------------------------------------------------------|
| Biblioteka | Wersja | Zastosowanie |
| -----------------------------------------------------------|
| Bootstrap | 5.3.0 | Framework CSS, komponenty UI |
| -----------------------------------------------------------|
| Tabler Icons | latest | Ikony interfejsu |
| -----------------------------------------------------------|
| Chart.js | latest | Wykresy postępów na dashboardzie |
|------------------------------------------------------------|
