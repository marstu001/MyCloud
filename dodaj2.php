<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid, $admin] = wymagaj_logowania();

$host = trim((string)($_POST['host'] ?? ''));
$port = (int)($_POST['port'] ?? 0);
$typ  = (string)($_POST['typ'] ?? 'www');
if (!isset(TYPY_URZADZEN[$typ])) { $typ = 'www'; }
if (!host_poprawny($host) || $port < 1 || $port > 65535) {
	header('Location: dodaj1.php?blad=' . urlencode('Podaj poprawny host (bez http://) i port 1-65535.') . '&host=' . urlencode($host));
	exit();
}

$link = db_polacz();
if (!$link) { header('Location: dodaj1.php?blad=' . urlencode('Błąd połączenia z bazą danych.')); exit(); }
$st = mysqli_prepare($link, "INSERT INTO domeny (host, port, user_id, typ) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($st, 'siis', $host, $port, $uid, $typ);
$ok = mysqli_stmt_execute($st);
mysqli_close($link);
$cel = $admin ? 'index1_admin.php' : 'index1.php';
header('Location: ' . $cel . '?info=' . urlencode($ok ? "Dodano host $host:$port." : 'Nie udało się dodać hosta.'));
exit();
