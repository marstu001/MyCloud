<?php declare(strict_types=1);
// Wspólne ustawienia zadania 5 (MyCloud) -- dane wpisane w jednym miejscu.
// Jeśli zmienisz nazwę bazy / użytkownika / hasło w DirectAdminie, popraw je tylko tutaj.
date_default_timezone_set('Europe/Warsaw');

const DB_HOST = 'localhost';
const DB_USER = 'srv126134_z5';
const DB_PASS = 'ZMIEN_HASLO';                                           // w repozytorium nie trzymamy prawdziwego hasła -- wpisz je tylko na serwerze
const DB_NAME = 'srv126134_z5';

const DYSK_KATALOG       = __DIR__ . '/dysk';                                  // tu leżą katalogi macierzyste użytkowników (login = nazwa katalogu); dostęp z przeglądarki zablokowany przez .htaccess
const DYSK_MAX_GLEBOKOSC = 4;                                                  // opcja dla chętnych 8b: maksymalnie 4 poziomy podkatalogów w katalogu macierzystym
const LIMIT_BLEDNYCH     = 3;                                                  // opcja dla chętnych 6b.iv: po tylu błędnych logowaniach z rzędu konto jest blokowane
const BLOKADA_SEKUND     = 60;                                                 // ...na tyle sekund
const ADMIN_LOGIN        = 'admin';                                            // konto administratora (widzi tabelę break_ins i wszystkie logowania)

function db_polacz(): ?mysqli
{
	mysqli_report(MYSQLI_REPORT_OFF);                                          // PHP 8.1+ domyślnie rzuca wyjątki przy błędach mysqli -- przywracamy klasyczne zachowanie
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
		return null;
	}
	mysqli_set_charset($link, 'utf8mb4');
	mysqli_query($link, "SET time_zone = '" . date('P') . "'");                // czas bazy zgodny z czasem PHP (Europe/Warsaw)
	return $link;
}

/** Skrót htmlspecialchars do wypisywania danych w HTML. */
function e(?string $s): string
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
