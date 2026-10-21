<?php declare(strict_types=1);
require_once 'db.php';

const TYPY_URZADZEN = [
	'www'     => 'Serwer WWW',
	'db'      => 'Serwer baz danych',
	'mail'    => 'Serwer poczty',
	'ap'      => 'Access point',
	'router'  => 'Router',
	'switch'  => 'Switch zarządzalny',
	'printer' => 'Drukarka sieciowa',
];

function typ_z_portu(int $port): string
{
	if (in_array($port, [25, 110, 143, 465, 587, 993, 995], true)) return 'mail';
	if (in_array($port, [3306, 5432, 1433, 27017], true))           return 'db';
	if (in_array($port, [9100, 631, 515], true))                    return 'printer';
	if ($port === 161)                                              return 'switch';
	return 'www';
}

function host_poprawny(string $host): bool
{
	return $host !== '' && strlen($host) <= 255 && preg_match('/^[A-Za-z0-9]([A-Za-z0-9._-]*[A-Za-z0-9])?$|^[0-9a-fA-F:]+$/', $host) === 1;
}

function sprawdz_porty(array $cele, int $timeout = MONITOR_TIMEOUT_S): array
{
	$wynik = [];
	$gniazda = [];
	foreach ($cele as $id => [$host, $port]) {
		$ip = $host;
		if (!filter_var($host, FILTER_VALIDATE_IP)) {
			$ip = gethostbyname($host);
			if ($ip === $host) {
				$wynik[$id] = [false, '0 getaddrinfo failed: Name or service not known'];
				continue;
			}
		}
		$adres = (strpos($ip, ':') !== false ? "[$ip]" : $ip) . ':' . $port;
		$s = @stream_socket_client('tcp://' . $adres, $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
		if ($s === false) {
			$wynik[$id] = [false, trim($errno . ' ' . $errstr)];
			continue;
		}
		stream_set_blocking($s, false);
		$gniazda[$id] = $s;
	}

	$start = microtime(true);
	while ($gniazda && (microtime(true) - $start) < $timeout) {
		$w = array_values($gniazda);
		$e = array_values($gniazda);
		$r = null;
		$ile = @stream_select($r, $w, $e, 0, 200000);
		if ($ile === false) break;
		foreach ($gniazda as $id => $s) {
			$gotowe = in_array($s, $w, true) || in_array($s, $e, true);
			if (!$gotowe) continue;
			if (@stream_socket_get_name($s, true) !== false) {
				$wynik[$id] = [true, 'Ok'];
			} else {
				$wynik[$id] = [false, '111 Connection refused'];
			}
			fclose($s);
			unset($gniazda[$id]);
		}
	}
	foreach ($gniazda as $id => $s) {
		$wynik[$id] = [false, '110 Connection timed out'];
		fclose($s);
	}
	return $wynik;
}

function monitor_odswiez(mysqli $link, array $wiersze): void
{
	$teraz = date('Y-m-d H:i:s');
	$prog  = date('Y-m-d H:i:s', time() - (MONITOR_INTERWAL_S - 1));
	$cele  = [];
	$claim = mysqli_prepare($link, "UPDATE domeny SET last_check = ? WHERE id = ? AND (last_check IS NULL OR last_check <= ?)");
	foreach ($wiersze as $w) {
		$id = (int)$w['id'];
		mysqli_stmt_bind_param($claim, 'sis', $teraz, $id, $prog);
		mysqli_stmt_execute($claim);
		if (mysqli_stmt_affected_rows($claim) === 1) {
			$cele[$id] = [(string)$w['host'], (int)$w['port']];
		}
	}
	mysqli_stmt_close($claim);
	if (!$cele) return;

	$wyniki = sprawdz_porty($cele);
	$ok   = mysqli_prepare($link, "UPDATE domeny SET errors = 0, failure = NULL, status = 1, last_error = NULL WHERE id = ?");
	$fail = mysqli_prepare($link, "UPDATE domeny SET failure = IF(errors = 0, ?, failure), errors = errors + 1, total_errors = total_errors + 1, status = 0, last_error = ? WHERE id = ?");
	foreach ($wyniki as $id => [$dziala, $komunikat]) {
		if ($dziala) {
			mysqli_stmt_bind_param($ok, 'i', $id);
			mysqli_stmt_execute($ok);
		} else {
			mysqli_stmt_bind_param($fail, 'ssi', $teraz, $komunikat, $id);
			mysqli_stmt_execute($fail);
		}
	}
	mysqli_stmt_close($ok);
	mysqli_stmt_close($fail);
}

function monitor_pobierz(mysqli $link, string $warunek = '', string $typy = '', array $parametry = []): array
{
	$sql = "SELECT d.*, u.username AS wlasciciel, w.username AS pracownik FROM domeny d
	        LEFT JOIN users u ON u.id = d.user_id LEFT JOIN users w ON w.id = d.worker_id"
	     . ($warunek !== '' ? " WHERE $warunek" : '') . " ORDER BY d.id";
	$st = mysqli_prepare($link, $sql);
	if ($typy !== '') mysqli_stmt_bind_param($st, $typy, ...$parametry);
	mysqli_stmt_execute($st);
	$res = mysqli_stmt_get_result($st);
	$w = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
	mysqli_stmt_close($st);
	return $w;
}

function awaria_sekundy(array $w): int
{
	if (empty($w['failure']) || (int)$w['errors'] === 0) return 0;
	$t = strtotime((string)$w['failure']);
	return $t ? max(0, time() - $t) : 0;
}

function hms(int $s): string
{
	return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
}

function ikona_svg(string $typ, string $stan = 'ok', int $px = 40): string
{
	static $ksztalty = [
		'www'     => '<circle cx="24" cy="24" r="16"/><ellipse cx="24" cy="24" rx="7" ry="16"/><path d="M8 24h32M10 15h28M10 33h28"/>',
		'db'      => '<ellipse cx="24" cy="12" rx="13" ry="5"/><path d="M11 12v24c0 2.8 5.8 5 13 5s13-2.2 13-5V12"/><path d="M11 24c0 2.8 5.8 5 13 5s13-2.2 13-5"/>',
		'mail'    => '<rect x="6" y="11" width="36" height="26" rx="3"/><path d="M6 14l18 13 18-13"/>',
		'ap'      => '<circle cx="24" cy="30" r="2.5"/><path d="M16.5 22.5a10.6 10.6 0 0 1 15 0M11 17a18.4 18.4 0 0 1 26 0"/><rect x="14" y="36" width="20" height="6" rx="2"/><path d="M24 33v3"/>',
		'router'  => '<rect x="6" y="28" width="36" height="12" rx="3"/><path d="M13 28L9 12M35 28l4-16"/><circle cx="14" cy="34" r="1.2"/><circle cx="20" cy="34" r="1.2"/><path d="M28 34h10"/><path d="M19 20a7 7 0 0 1 10 0"/>',
		'switch'  => '<rect x="4" y="15" width="40" height="18" rx="3"/><path d="M9 21h4v6H9zM16 21h4v6h-4zM23 21h4v6h-4zM30 21h4v6h-4z"/><circle cx="39" cy="24" r="1.3"/><path d="M11 33v4M18 33v4M25 33v4M32 33v4"/>',
		'printer' => '<path d="M14 18V7h20v11"/><rect x="6" y="18" width="36" height="15" rx="3"/><rect x="14" y="27" width="20" height="14" rx="1"/><circle cx="36" cy="23" r="1.2"/>',
	];
	$typ = isset($ksztalty[$typ]) ? $typ : 'www';
	$znaczek = $stan === 'ok' ? '' : '<g class="ico-x"><circle cx="39" cy="39" r="8" fill="#fff"/><path d="M35 35l8 8M43 35l-8 8"/></g>';
	return '<svg class="ico ico-' . $stan . '" width="' . $px . '" height="' . $px . '" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="'
	     . htmlspecialchars(TYPY_URZADZEN[$typ], ENT_QUOTES, 'UTF-8') . ($stan === 'ok' ? ' – działa' : ' – awaria') . '">' . $ksztalty[$typ] . $znaczek . '</svg>';
}

function monitor_tabela(array $wiersze, bool $admin = false): string
{
	$maks = 0;
	foreach ($wiersze as $w) { $maks = max($maks, awaria_sekundy($w)); }
	$h  = '<div id="dane-stanu" data-max-awaria="' . $maks . '" data-ile="' . count($wiersze) . '" data-prog-dzwieku="' . AWARIA_DZWIEK_S . '" data-czas="' . e(date('H:i:s')) . '"></div>';
	$h .= '<div class="table-responsive"><table class="table table-striped table-hover align-middle monitor-tabela"><thead class="table-dark"><tr>'
	    . '<th>Id</th><th></th><th>Host</th><th>Port</th><th>Stan</th><th>Błędy z rzędu</th><th>Początek awarii</th><th>Łączny czas awarii</th>'
	    . ($admin ? '<th>Właściciel</th>' : '') . '<th>Pracownik</th><th></th></tr></thead><tbody>';
	if (!$wiersze) {
		$h .= '<tr><td colspan="' . ($admin ? 11 : 10) . '" class="text-center text-muted">Brak hostów. <a href="dodaj1.php">Dodaj pierwszy host</a>.</td></tr>';
	}
	foreach ($wiersze as $w) {
		$sek   = awaria_sekundy($w);
		$dziala = $sek === 0 && (int)($w['status'] ?? 1) === 1;
		$stan  = $dziala ? 'ok' : ($sek > AWARIA_PULS_S ? 'crit' : 'fail');
		$tekst = $w['status'] === null ? '<span class="text-muted">sprawdzanie…</span>'
		       : ($dziala ? '<span class="badge text-bg-success">Ok</span>' : '<span class="badge text-bg-danger">' . e((string)$w['last_error']) . '</span>');
		$h .= '<tr class="wiersz-' . $stan . '"><td>' . (int)$w['id'] . '</td>'
		    . '<td>' . ikona_svg((string)$w['typ'], $stan, 34) . '</td>'
		    . '<td>' . e((string)$w['host']) . '<div class="small text-muted">' . e(TYPY_URZADZEN[$w['typ']] ?? '') . '</div></td>'
		    . '<td>' . (int)$w['port'] . '</td><td>' . $tekst . '</td><td>' . (int)$w['errors'] . '</td>'
		    . '<td>' . ($dziala || empty($w['failure']) ? '-' : e((string)$w['failure']) . ' <span class="small text-muted">(' . hms($sek) . ' temu)</span>') . '</td>'
		    . '<td>' . hms((int)$w['total_errors'] * MONITOR_INTERWAL_S) . '</td>'
		    . ($admin ? '<td>' . e((string)($w['wlasciciel'] ?? '?')) . '</td>' : '')
		    . '<td>' . e((string)($w['pracownik'] ?? '')) . '</td>'
		    . '<td><a class="btn btn-sm btn-outline-danger" href="kasuj1.php?host=' . urlencode((string)$w['host']) . '&amp;port=' . (int)$w['port'] . '" title="Usuń host"><i class="bi bi-trash"></i></a></td></tr>';
	}
	return $h . '</tbody></table></div>';
}

function wymagaj_logowania(bool $tylko_admin = false): array
{
	if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	if (!isset($_SESSION['loggedin'], $_SESSION['user_id'])) {
		header('Location: index3.php?blad=' . urlencode('Zaloguj się, aby korzystać z monitoringu.'));
		exit();
	}
	$admin = !empty($_SESSION['admin']);
	if ($tylko_admin && !$admin) {
		header('Location: index1.php');
		exit();
	}
	return [(int)$_SESSION['user_id'], $admin];
}
