# MyCloud – dysk sieciowy (zadanie 5)

Aplikacja PHP 8 + MariaDB/MySQL do przechowywania prywatnych plików na serwerze hostingowym (wzorowana na Dysku Google / OneDrive / iCloud).

## Funkcje
- rejestracja i logowanie (sesje), hasła w tabeli `users` jako skróty `password_hash`
- rejestr prób logowania (udanych i błędnych) w tabeli `goscieportalu`
- blokada konta na 1 minutę po 3 błędnych logowaniach, wpis w `break_ins`, czerwone ostrzeżenie po kolejnym udanym logowaniu
- katalog macierzysty o nazwie takiej jak login, lista plików i podkatalogów, tworzenie i usuwanie, wchodzenie i wychodzenie (level up)
- upload i download plików dowolnego typu (bez ograniczeń typu i rozmiaru poza limitami PHP), obrazy w oknie aplikacji, odtwarzacze audio i wideo bez autostartu
- opcje dodatkowe: widok listy i miniatur, data i rozmiar plików, podkatalogi do 4 poziomów, udostępnianie plików i katalogów innym użytkownikom, lista loginów dla administratora

## Instalacja
1. Utwórz bazę i użytkownika w panelu hostingu, wpisz dane w `db.php`.
2. Wgraj pliki na serwer (płaska struktura, bez podkatalogów).
3. Uruchom raz `utworz_tabele.php` (tworzy tabele, konto `admin` / `admin` i katalog `dysk/`), a następnie usuń ten plik z serwera.

## Bezpieczeństwo
- pliki użytkowników leżą w `dysk/` z blokadą dostępu przez `.htaccess` i są wydawane wyłącznie przez `pobierz.php` po sprawdzeniu sesji
- zapytania SQL przez prepared statements, formularze zmieniające dane chronione tokenem CSRF
- ścieżki walidowane (brak `..`, maksymalnie 4 poziomy), brak wykonywania przesłanych plików
