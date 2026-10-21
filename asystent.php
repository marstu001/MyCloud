<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid, $admin] = wymagaj_logowania();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$link = db_polacz();
if (!$link) { echo json_encode(['tekst' => 'Nie mogę połączyć się z bazą danych.']); exit; }
$wiersze = $admin ? monitor_pobierz($link) : monitor_pobierz($link, 'd.user_id = ? OR d.worker_id = ?', 'ii', [$uid, $uid]);
monitor_odswiez($link, $wiersze);
$wiersze = $admin ? monitor_pobierz($link) : monitor_pobierz($link, 'd.user_id = ? OR d.worker_id = ?', 'ii', [$uid, $uid]);
mysqli_close($link);

$nie = []; $tak = [];
foreach ($wiersze as $w) {
	$sek = awaria_sekundy($w);
	$opis = (TYPY_URZADZEN[$w['typ']] ?? 'Usługa') . ' ' . $w['host'] . ':' . $w['port'];
	if ($sek > 0 || (int)($w['status'] ?? 1) === 0) {
		$nie[] = $opis . ' – awaria od ' . hms($sek) . ' (' . ($w['last_error'] ?? 'brak odpowiedzi') . ')';
	} else {
		$tak[] = $opis;
	}
}

$p = mb_strtolower((string)($_GET['q'] ?? 'wszystko'), 'UTF-8');
if ($p === 'nie' || preg_match('/nie\s+dzia|awari|pada|problem|błęd|bled|offline|uszkodz/u', $p)) {
	$tryb = 'nie';
} elseif ($p === 'dziala' || preg_match('/dzia[łl]a|online|sprawn|ok\b|w porz/u', $p)) {
	$tryb = 'dziala';
} else {
	$tryb = 'wszystko';
}

$wypisz = function (array $l): string { return implode("\n", array_map(fn($x) => '• ' . $x, $l)); };
$razem = count($wiersze);
if ($razem === 0) {
	$tekst = 'Nie monitorujesz jeszcze żadnych hostów. Dodaj pierwszy w menu Monitoring.';
} elseif ($tryb === 'nie') {
	$tekst = $nie ? 'Nie działa ' . count($nie) . ' z ' . $razem . ":\n" . $wypisz($nie) : "Wszystko działa – żadna z $razem usług nie ma awarii.";
} elseif ($tryb === 'dziala') {
	$tekst = $tak ? 'Działa ' . count($tak) . ' z ' . $razem . ":\n" . $wypisz($tak) : 'Obecnie nic nie działa.';
} else {
	$tekst = "Podsumowanie: działa " . count($tak) . ", nie działa " . count($nie) . " (razem $razem).";
	if ($nie) { $tekst .= "\n\nNie działa:\n" . $wypisz($nie); }
	if ($tak) { $tekst .= "\n\nDziała:\n" . $wypisz($tak); }
}
echo json_encode(['tekst' => $tekst, 'nie' => count($nie), 'ok' => count($tak)], JSON_UNESCAPED_UNICODE);
