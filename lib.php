<?php declare(strict_types=1);
require_once 'db.php';

function sesja_start(): void
{
	if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
}

function csrf_token(): string
{
	sesja_start();
	if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
	return $_SESSION['csrf'];
}
function csrf_pole(): string
{
	return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_wymagaj(): void
{
	sesja_start();
	$token = (string)($_POST['csrf'] ?? '');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
		http_response_code(400);
		exit('Nieprawidłowe żądanie (brak lub zły token formularza). Wróć do poprzedniej strony i spróbuj ponownie.');
	}
}

function flash(string $typ, string $tekst): void
{
	sesja_start();
	$_SESSION['flash'][] = [$typ, $tekst];
}
function flash_pobierz(): array
{
	sesja_start();
	$f = $_SESSION['flash'] ?? [];
	unset($_SESSION['flash']);
	return $f;
}

function login_poprawny(string $login): bool
{
	return (bool)preg_match('/^[A-Za-z0-9_-]{1,20}$/', $login);
}
function nazwa_katalogu_poprawna(string $n): bool
{
	return (bool)preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _().,+-]{0,59}$/u', $n) && !preg_match('/[ .]$/', $n);
}
function nazwa_pliku_czysta(string $n): string
{
	$n = basename(str_replace('\\', '/', $n));
	$n = preg_replace('/[\x00-\x1F\x7F"*:<>?|\/\\\\]/u', '_', $n) ?? '';
	$n = ltrim($n, ". \t");
	$n = rtrim($n, ". ");
	if ($n === '') { return ''; }
	if (strlen($n) > 200) {
		$roz = pathinfo($n, PATHINFO_EXTENSION);
		$n   = mb_strcut(pathinfo($n, PATHINFO_FILENAME), 0, 190 - strlen($roz), 'UTF-8') . ($roz !== '' ? '.' . $roz : '');
	}
	return $n;
}
function nazwa_elementu_poprawna(string $n): bool
{
	return $n !== '' && $n === nazwa_pliku_czysta($n);
}

function sciezka_segmenty(string $rel, bool $element = false): ?array
{
	$rel = trim($rel, '/');
	if ($rel === '') { return []; }
	$seg = explode('/', $rel);
	$ost = count($seg) - 1;
	foreach ($seg as $i => $s) {
		$ok = ($element && $i === $ost) ? nazwa_elementu_poprawna($s) : nazwa_katalogu_poprawna($s);
		if (!$ok) { return null; }
	}
	$katalogi = $element ? count($seg) - 1 : count($seg);
	if ($katalogi > DYSK_MAX_GLEBOKOSC) { return null; }
	return $seg;
}

function dysk_upewnij_korzen(): bool
{
	if (!is_dir(DYSK_KATALOG) && !@mkdir(DYSK_KATALOG, 0755, true)) { return false; }
	$ht = DYSK_KATALOG . '/.htaccess';
	if (!is_file($ht)) {
		@file_put_contents($ht, "# Pliki użytkowników są wydawane wyłącznie przez pobierz.php (po sprawdzeniu sesji) -- bezpośredni dostęp zablokowany\n"
			. "Options -Indexes\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n");
	}
	if (!is_file(DYSK_KATALOG . '/index.html')) { @file_put_contents(DYSK_KATALOG . '/index.html', ''); }
	return true;
}
function dysk_katalog_domowy(string $login): ?string
{
	if (!login_poprawny($login) || !dysk_upewnij_korzen()) { return null; }
	$k = DYSK_KATALOG . '/' . $login;
	if (!is_dir($k) && !@mkdir($k, 0755)) { return null; }
	return $k;
}
function dysk_pelna(string $login, array $seg): ?string
{
	$dom = dysk_katalog_domowy($login);
	if ($dom === null) { return null; }
	$pelna = $seg ? $dom . '/' . implode('/', $seg) : $dom;
	$r = realpath($pelna);
	$d = realpath($dom);
	if ($r === false || $d === false) { return null; }
	return ($r === $d || strpos($r, $d . DIRECTORY_SEPARATOR) === 0) ? $r : null;
}
function dysk_lista(string $katalog): array
{
	$kat = []; $pl = [];
	foreach (@scandir($katalog) ?: [] as $n) {
		if ($n === '.' || $n === '..' || $n[0] === '.') { continue; }
		$p = $katalog . '/' . $n;
		if (is_dir($p)) { $kat[] = ['nazwa' => $n, 'czas' => (int)filemtime($p), 'rozmiar' => 0]; }
		elseif (is_file($p)) { $pl[] = ['nazwa' => $n, 'czas' => (int)filemtime($p), 'rozmiar' => (int)filesize($p)]; }
	}
	$cmp = fn($a, $b) => strnatcasecmp($a['nazwa'], $b['nazwa']);
	usort($kat, $cmp); usort($pl, $cmp);
	return [$kat, $pl];
}
function dysk_usun(string $sciezka): bool
{
	if (is_file($sciezka) || is_link($sciezka)) { return @unlink($sciezka); }
	if (!is_dir($sciezka)) { return false; }
	foreach (scandir($sciezka) ?: [] as $n) {
		if ($n === '.' || $n === '..') { continue; }
		if (!dysk_usun($sciezka . '/' . $n)) { return false; }
	}
	return @rmdir($sciezka);
}
function dysk_wolna_nazwa(string $katalog, string $nazwa): string
{
	if (!file_exists($katalog . '/' . $nazwa)) { return $nazwa; }
	$roz = pathinfo($nazwa, PATHINFO_EXTENSION);
	$baz = pathinfo($nazwa, PATHINFO_FILENAME);
	for ($i = 1; $i < 1000; $i++) {
		$n = $baz . " ($i)" . ($roz !== '' ? '.' . $roz : '');
		if (!file_exists($katalog . '/' . $n)) { return $n; }
	}
	return bin2hex(random_bytes(4)) . '_' . $nazwa;
}

const MIME_INLINE = [
	'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
	'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg', 'wav' => 'audio/wav', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'flac' => 'audio/flac', 'opus' => 'audio/ogg',
	'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg',
	'pdf' => 'application/pdf',
];
function typ_pliku(string $nazwa): string
{
	$m = MIME_INLINE[strtolower(pathinfo($nazwa, PATHINFO_EXTENSION))] ?? '';
	if ($m === '') { return 'inne'; }
	if ($m === 'application/pdf') { return 'pdf'; }
	return explode('/', $m)[0] === 'image' ? 'obraz' : (explode('/', $m)[0] === 'audio' ? 'audio' : 'wideo');
}
function ikona_pliku(string $nazwa): string
{
	switch (strtolower(pathinfo($nazwa, PATHINFO_EXTENSION))) {
		case 'pdf': return 'bi-file-earmark-pdf text-danger';
		case 'doc': case 'docx': case 'odt': case 'rtf': return 'bi-file-earmark-word text-primary';
		case 'xls': case 'xlsx': case 'csv': case 'ods': return 'bi-file-earmark-excel text-success';
		case 'ppt': case 'pptx': return 'bi-file-earmark-slides text-warning';
		case 'zip': case 'rar': case '7z': case 'gz': case 'tar': return 'bi-file-earmark-zip';
		case 'txt': case 'md': case 'log': return 'bi-file-earmark-text';
		case 'php': case 'js': case 'html': case 'css': case 'c': case 'cpp': case 'py': case 'java': case 'json': case 'xml': case 'sql': return 'bi-file-earmark-code';
	}
	switch (typ_pliku($nazwa)) {
		case 'obraz': return 'bi-file-earmark-image text-info';
		case 'audio': return 'bi-file-earmark-music text-purple';
		case 'wideo': return 'bi-file-earmark-play text-danger';
	}
	return 'bi-file-earmark';
}
function format_rozmiaru(int $b): string
{
	if ($b < 1024) { return $b . ' B'; }
	$j = ['KB', 'MB', 'GB', 'TB']; $i = -1;
	do { $b /= 1024; $i++; } while ($b >= 1024 && $i < 3);
	return number_format($b, $b < 10 ? 2 : 1, ',', ' ') . ' ' . $j[$i];
}

function czy_udostepnione(mysqli $link, int $wlasciciel, int $odbiorca, string $rel): bool
{
	$rel = trim($rel, '/');
	$q = mysqli_prepare($link, 'SELECT sciezka FROM udostepnienia WHERE wlasciciel_id = ? AND odbiorca_id = ?');
	mysqli_stmt_bind_param($q, 'ii', $wlasciciel, $odbiorca);
	mysqli_stmt_execute($q);
	mysqli_stmt_bind_result($q, $s);
	$ok = false;
	while (mysqli_stmt_fetch($q)) {
		if ($rel === $s || strpos($rel, $s . '/') === 0) { $ok = true; break; }
	}
	mysqli_stmt_close($q);
	return $ok;
}
function usun_udostepnienia(mysqli $link, int $wlasciciel, string $rel): void
{
	$rel = trim($rel, '/');
	$q = mysqli_prepare($link, 'SELECT id, sciezka FROM udostepnienia WHERE wlasciciel_id = ?');
	mysqli_stmt_bind_param($q, 'i', $wlasciciel);
	mysqli_stmt_execute($q);
	mysqli_stmt_bind_result($q, $id, $s);
	$ids = [];
	while (mysqli_stmt_fetch($q)) { if ($s === $rel || strpos($s, $rel . '/') === 0) { $ids[] = $id; } }
	mysqli_stmt_close($q);
	foreach ($ids as $id) { mysqli_query($link, 'DELETE FROM udostepnienia WHERE id = ' . (int)$id); }
}

function wymagaj_logowania(): array
{
	sesja_start();
	if (empty($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
		header('Location: index3.php');
		exit();
	}
	return [(int)$_SESSION['user_id'], (string)$_SESSION['username'], !empty($_SESSION['admin'])];
}

function url_dysk(string $rel = '', int $w = 0): string
{
	$q = [];
	if ($w > 0) { $q['w'] = $w; }
	if ($rel !== '') { $q['p'] = $rel; }
	return 'dysk.php' . ($q ? '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986) : '');
}
function url_pobierz(string $rel, int $w = 0, bool $inline = false): string
{
	$q = ['p' => $rel];
	if ($w > 0) { $q['w'] = $w; }
	if ($inline) { $q['inline'] = 1; }
	return 'pobierz.php?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}
