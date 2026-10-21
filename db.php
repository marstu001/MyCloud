<?php declare(strict_types=1);
date_default_timezone_set('Europe/Warsaw');

const DB_HOST = 'localhost';
const DB_USER = 'srv126134_z5';
const DB_PASS = 'Zadanie5Test2026';
const DB_NAME = 'srv126134_z5';

const DYSK_KATALOG       = __DIR__ . '/dysk';
const DYSK_MAX_GLEBOKOSC = 4;
const LIMIT_BLEDNYCH     = 3;
const BLOKADA_SEKUND     = 60;
const ADMIN_LOGIN        = 'admin';

const DOZWOLONE_KRAJE = ['PL'];
const ZABLOKOWANE_IP  = [];

const TAJNY_KLUCZ = 'f285239340b37dd58da6b4866a52cf628450e2a2f695af2e';

const MONITOR_INTERWAL_S = 10;
const MONITOR_TIMEOUT_S  = 3;
const AWARIA_PULS_S      = 20;
const AWARIA_DZWIEK_S    = 40;

function db_polacz(): ?mysqli
{
	mysqli_report(MYSQLI_REPORT_OFF);
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
		return null;
	}
	mysqli_set_charset($link, 'utf8mb4');
	mysqli_query($link, "SET time_zone = '" . date('P') . "'");
	return $link;
}

function e(?string $s): string
{
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
