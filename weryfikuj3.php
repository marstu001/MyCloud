<?php declare(strict_types=1);
session_start();
require_once 'lib.php';
require_once 'avatar.php';
require_once 'geo.php';

$wroc = function (string $komunikat): void {
	header('Location: index3.php?blad=' . urlencode($komunikat));
	exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $wroc('Wypełnij formularz logowania.'); }

$user = (string)($_POST['user'] ?? '');
$pass = (string)($_POST['pass'] ?? '');
$ip   = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
$ua   = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$wpis = mb_substr($user, 0, 50);

$link = db_polacz();
if (!$link) { $wroc('Błąd połączenia z bazą danych.'); }
avatar_upewnij_kolumne($link);

$ostatni_wpis = 0;
$zapisz = function (string $wynik) use ($link, $ip, $wpis, $ua, &$ostatni_wpis): void {
	$q = mysqli_prepare($link, 'INSERT INTO goscieportalu (ipaddress, login, wynik, przegladarka) VALUES (?, ?, ?, ?)');
	mysqli_stmt_bind_param($q, 'ssss', $ip, $wpis, $wynik, $ua);
	mysqli_stmt_execute($q);
	$ostatni_wpis = (int)mysqli_insert_id($link);
	mysqli_stmt_close($q);
};

[$dostep, $kraj, $powod] = sprawdz_dostep_ip($ip);
if (!$dostep)
{
	$zapisz('BLOKADA');
	mysqli_close($link);
	$wroc('Logowanie zablokowane: Twoje IP wskazuje na kraj spoza terytorium Polski' . ($kraj !== '' ? " ($kraj)" : '') . '.');
}

$q = mysqli_prepare($link, 'SELECT id, username, password, avatar, bledne, GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), blokada_do)) FROM users WHERE username = ?');
mysqli_stmt_bind_param($q, 's', $user);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $id, $login, $hash, $avatar, $bledne, $blokada_s);
$znaleziono = mysqli_stmt_fetch($q);
mysqli_stmt_close($q);

if (!$znaleziono)
{
	$zapisz('BLAD');
	mysqli_close($link);
	$wroc('Brak użytkownika o takim loginie.');
}

if ((int)$blokada_s > 0)
{
	$zapisz('BLOKADA');
	mysqli_close($link);
	$wroc('Konto jest zablokowane po ' . LIMIT_BLEDNYCH . ' błędnych logowaniach. Spróbuj ponownie za ' . (int)$blokada_s . ' s.');
}

if (!password_verify($pass, (string)$hash))
{
	$zapisz('BLAD');
	mysqli_query($link, 'UPDATE users SET bledne = bledne + 1 WHERE id = ' . (int)$id);
	$bledne = (int)$bledne + 1;
	if ($bledne >= LIMIT_BLEDNYCH)
	{
		mysqli_query($link, 'UPDATE users SET bledne = 0, blokada_do = DATE_ADD(NOW(), INTERVAL ' . (int)BLOKADA_SEKUND . ' SECOND) WHERE id = ' . (int)$id);
		$b = mysqli_prepare($link, 'INSERT INTO break_ins (ipaddress, login) VALUES (?, ?)');
		mysqli_stmt_bind_param($b, 'ss', $ip, $login);
		mysqli_stmt_execute($b);
		mysqli_stmt_close($b);
		mysqli_close($link);
		$wroc('Błędne hasło. To była ' . LIMIT_BLEDNYCH . '. nieudana próba z rzędu -- konto zablokowane na ' . BLOKADA_SEKUND . ' s.');
	}
	mysqli_close($link);
	$wroc('Błędne hasło. Pozostało prób: ' . (LIMIT_BLEDNYCH - $bledne) . '.');
}

$ostrzezenie = '';
function liczba_prob(int $n): string { $d = $n % 10; return $n === 1 ? 'próba' : (($d >= 2 && $d <= 4 && ($n % 100 < 12 || $n % 100 > 14)) ? 'próby' : 'prób'); }
$r = mysqli_prepare($link, "SELECT COUNT(*), MAX(id) FROM goscieportalu WHERE login = ? AND wynik <> 'OK' AND id > COALESCE((SELECT MAX(id) FROM goscieportalu WHERE login = ? AND wynik = 'OK'), 0)");
mysqli_stmt_bind_param($r, 'ss', $login, $login);
mysqli_stmt_execute($r);
mysqli_stmt_bind_result($r, $ile, $ostatnie_id);
mysqli_stmt_fetch($r);
mysqli_stmt_close($r);
if ((int)$ile > 0)
{
	$o = mysqli_query($link, 'SELECT `datetime`, ipaddress FROM goscieportalu WHERE id = ' . (int)$ostatnie_id);
	if ($o && ($w = mysqli_fetch_row($o)))
	{
		$ostrzezenie = 'Uwaga! Od Twojego ostatniego poprawnego logowania ktoś próbował zalogować się na to konto nieudanie (' . (int)$ile . ' ' . liczba_prob((int)$ile)
			. '). Ostatnia nieudana próba: ' . $w[0] . ', adres IP: ' . $w[1] . '.';
	}
}
mysqli_query($link, 'UPDATE users SET bledne = 0, blokada_do = NULL WHERE id = ' . (int)$id);
$zapisz('OK');

$katalog = dysk_katalog_domowy($login);
mysqli_close($link);
if ($katalog === null) { $wroc('Nie udało się otworzyć katalogu macierzystego na serwerze.'); }

session_regenerate_id(true);
$_SESSION['loggedin']    = true;
$_SESSION['user_id']     = (int)$id;
$_SESSION['wizyta_id']   = $ostatni_wpis;
$_SESSION['username']    = $login;
$_SESSION['admin']       = strcasecmp($login, ADMIN_LOGIN) === 0;
$_SESSION['avatar']      = (string)($avatar ?? '');
$_SESSION['katalog']     = $katalog;
$_SESSION['ostrzezenie'] = $ostrzezenie;
header('Location: dysk.php');
exit();
