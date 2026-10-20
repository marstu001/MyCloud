<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();
if (!$admin) { flash('danger', 'Ta strona jest dostępna tylko dla administratora.'); header('Location: dysk.php'); exit(); }

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }
$wiersze = [];
$r = mysqli_query($link, 'SELECT `datetime`, ipaddress, login FROM break_ins ORDER BY id DESC LIMIT 500');
while ($r && ($w = mysqli_fetch_row($r))) { $wiersze[] = $w; }
mysqli_close($link);

$tytul = 'Włamania (break_ins)';
require 'gora.php';
?>
<p class="text-muted">Zdarzenia, w których ktoś trzykrotnie z rzędu błędnie podał hasło do konta (konto jest wtedy blokowane na <?php echo (int)BLOKADA_SEKUND; ?> s). Na podstawie tych danych można tworzyć białe i czarne listy adresów IP.</p>
<?php if (!$wiersze): ?>
<p class="text-muted">Brak zarejestrowanych zdarzeń.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-sm table-striped">
	<thead><tr><th>Data i godzina</th><th>Adres IP</th><th>Konto</th></tr></thead>
	<tbody>
<?php foreach ($wiersze as $w): ?>
		<tr><td><?php echo e($w[0]); ?></td><td><?php echo e($w[1]); ?></td><td><?php echo e($w[2]); ?></td></tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<?php endif; ?>
<?php require 'dol.php'; ?>
