<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid, $admin] = wymagaj_logowania();

[$host, $port] = array_pad(explode('|', (string)($_POST['hostport'] ?? ''), 2), 2, '');
$port = (int)$port;
$link = db_polacz();
if (!$link || $host === '' || $port < 1) { header('Location: kasuj1.php?blad=' . urlencode('Wybierz host do usunięcia.')); exit(); }

if ($admin) {
	$st = mysqli_prepare($link, "DELETE FROM domeny WHERE host = ? AND port = ?");
	mysqli_stmt_bind_param($st, 'si', $host, $port);
} else {
	$st = mysqli_prepare($link, "DELETE FROM domeny WHERE host = ? AND port = ? AND user_id = ?");
	mysqli_stmt_bind_param($st, 'sii', $host, $port, $uid);
}
mysqli_stmt_execute($st);
$ile = mysqli_stmt_affected_rows($st);
mysqli_close($link);
header('Location: ' . ($admin ? 'index1_admin.php' : 'index1.php') . '?info=' . urlencode($ile > 0 ? "Usunięto $host:$port." : 'Nie znaleziono takiego hosta wśród Twoich hostów.'));
exit();
