<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$w   = (int)($_GET['w'] ?? 0);
$rel = trim((string)($_GET['p'] ?? ''), '/');
$seg = sciezka_segmenty($rel, true);
if ($seg === null || !$seg) { http_response_code(400); exit('Niepoprawna ścieżka.'); }

$wlasciciel = $login;
if ($w !== 0 && $w !== $uid)
{
	$link = db_polacz();
	if (!$link) { http_response_code(500); exit('Błąd bazy danych.'); }
	$q = mysqli_prepare($link, 'SELECT username FROM users WHERE id = ?');
	mysqli_stmt_bind_param($q, 'i', $w);
	mysqli_stmt_execute($q);
	mysqli_stmt_bind_result($q, $nazwa_wl);
	$istnieje = mysqli_stmt_fetch($q);
	mysqli_stmt_close($q);
	$ok = $istnieje && czy_udostepnione($link, $w, $uid, $rel);
	mysqli_close($link);
	if (!$ok) { http_response_code(403); exit('Ten plik nie jest Ci udostępniony.'); }
	$wlasciciel = (string)$nazwa_wl;
}

$plik = dysk_pelna($wlasciciel, $seg);
if ($plik === null || !is_file($plik)) { http_response_code(404); exit('Nie ma takiego pliku.'); }

$nazwa  = basename($plik);
$roz    = strtolower(pathinfo($nazwa, PATHINFO_EXTENSION));
$inline = isset($_GET['inline']) && isset(MIME_INLINE[$roz]);
$mime   = $inline ? MIME_INLINE[$roz] : 'application/octet-stream';
$rozmiar = (int)filesize($plik);

session_write_close();
while (ob_get_level() > 0) { ob_end_clean(); }
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
@ini_set('zlib.output_compression', '0');
@set_time_limit(0);

$start = 0; $koniec = $rozmiar - 1;
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) && ($m[1] !== '' || $m[2] !== ''))
{
	if ($m[1] === '') { $start = max(0, $rozmiar - (int)$m[2]); }
	else { $start = (int)$m[1]; if ($m[2] !== '') { $koniec = min($koniec, (int)$m[2]); } }
	if ($start > $koniec || $start >= $rozmiar)
	{
		http_response_code(416);
		header('Content-Range: bytes */' . $rozmiar);
		exit();
	}
	http_response_code(206);
	header('Content-Range: bytes ' . $start . '-' . $koniec . '/' . $rozmiar);
}

$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nazwa);
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nazwa));
header('Content-Length: ' . ($koniec - $start + 1));
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
if ($roz !== 'pdf') { header('Content-Security-Policy: sandbox'); }
header('Cache-Control: private, max-age=0, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)filemtime($plik)) . ' GMT');

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') { exit(); }

$f = fopen($plik, 'rb');
if ($f === false) { exit(); }
fseek($f, $start);
$zostalo = $koniec - $start + 1;
while ($zostalo > 0 && !feof($f))
{
	$czesc = fread($f, (int)min(1048576, $zostalo));
	if ($czesc === false || $czesc === '') { break; }
	echo $czesc;
	$zostalo -= strlen($czesc);
	flush();
	if (connection_aborted()) { break; }
}
fclose($f);
