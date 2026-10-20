<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();
if (!$admin) { flash('danger', 'Ta strona jest dostępna tylko dla administratora.'); header('Location: dysk.php'); exit(); }

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }
$wiersze = [];
$r = mysqli_query($link, "SELECT u.id, u.username, u.utworzono, (SELECT MAX(`datetime`) FROM goscieportalu g WHERE g.login = u.username AND g.wynik = 'OK') FROM users u ORDER BY u.username");
while ($r && ($w = mysqli_fetch_row($r))) { $wiersze[] = $w; }
mysqli_close($link);

if (($_GET['format'] ?? '') === 'json')
{
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array_map(fn($w) => ['id' => (int)$w[0], 'login' => $w[1], 'utworzono' => $w[2], 'ostatnie_logowanie' => $w[3]], $wiersze), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit();
}

$tytul = 'Użytkownicy portalu';
require 'gora.php';
?>
<div class="table-responsive"><table class="table table-sm table-striped">
	<thead><tr><th>#</th><th>Login</th><th>Konto założone</th><th>Ostatnie udane logowanie</th></tr></thead>
	<tbody>
<?php foreach ($wiersze as $w): ?>
		<tr><td><?php echo (int)$w[0]; ?></td><td><?php echo e($w[1]); ?></td><td><?php echo e($w[2]); ?></td><td><?php echo e($w[3] ?? '–'); ?></td></tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<p class="text-muted small">Dane w formacie JSON: <a href="uzytkownicy.php?format=json">uzytkownicy.php?format=json</a></p>
<?php require 'dol.php'; ?>
