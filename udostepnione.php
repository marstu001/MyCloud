<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

$dla_mnie = [];
$q = mysqli_prepare($link, 'SELECT ud.wlasciciel_id, us.username, ud.sciezka, ud.utworzono FROM udostepnienia ud JOIN users us ON us.id = ud.wlasciciel_id WHERE ud.odbiorca_id = ? ORDER BY us.username, ud.sciezka');
mysqli_stmt_bind_param($q, 'i', $uid);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $wid, $wnazwa, $sciezka, $kiedy);
while (mysqli_stmt_fetch($q)) { $dla_mnie[] = [$wid, $wnazwa, $sciezka, $kiedy]; }
mysqli_stmt_close($q);

$przeze_mnie = [];
$q = mysqli_prepare($link, 'SELECT ud.sciezka, us.username, ud.utworzono FROM udostepnienia ud JOIN users us ON us.id = ud.odbiorca_id WHERE ud.wlasciciel_id = ? ORDER BY ud.sciezka, us.username');
mysqli_stmt_bind_param($q, 'i', $uid);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $sciezka2, $onazwa, $kiedy2);
while (mysqli_stmt_fetch($q)) { $przeze_mnie[] = [$sciezka2, $onazwa, $kiedy2]; }
mysqli_stmt_close($q);

$tytul = 'Udostępnione';
require 'gora.php';
foreach (flash_pobierz() as [$typ, $tekst]) { echo '<div class="alert alert-' . e($typ) . '" role="alert">' . e($tekst) . '</div>' . "\n"; }
?>
<h2 class="h5"><i class="bi bi-box-arrow-in-down"></i> Udostępnione mi przez innych</h2>
<?php if (!$dla_mnie): ?>
<p class="text-muted">Nikt jeszcze nic Ci nie udostępnił.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover align-middle">
	<thead><tr><th>Właściciel</th><th>Element</th><th>Od</th></tr></thead>
	<tbody>
<?php foreach ($dla_mnie as [$wid, $wnazwa, $sciezka, $kiedy]):
	$seg   = sciezka_segmenty($sciezka, true);
	$pelna = $seg ? dysk_pelna($wnazwa, $seg) : null;
	$jest  = $pelna !== null; $kat = $jest && is_dir($pelna);
?>
		<tr>
			<td><i class="bi bi-person"></i> <?php echo e($wnazwa); ?></td>
			<td>
<?php if (!$jest): ?>
				<span class="text-muted"><i class="bi bi-slash-circle"></i> <?php echo e($sciezka); ?> (już nie istnieje)</span>
<?php elseif ($kat): ?>
				<i class="bi bi-folder-fill text-warning"></i> <a href="<?php echo e(url_dysk($sciezka, (int)$wid)); ?>"><?php echo e($sciezka); ?></a>
<?php else: ?>
				<i class="bi <?php echo e(ikona_pliku($sciezka)); ?>"></i> <a href="<?php echo e(url_pobierz($sciezka, (int)$wid)); ?>"><?php echo e($sciezka); ?></a>
<?php if (typ_pliku($sciezka) !== 'inne'): ?> <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="<?php echo e(url_pobierz($sciezka, (int)$wid, true)); ?>" title="Otwórz w przeglądarce"><i class="bi bi-eye"></i></a><?php endif; ?>
<?php endif; ?>
			</td>
			<td><?php echo e($kiedy); ?></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<?php endif; ?>

<h2 class="h5 mt-4"><i class="bi bi-box-arrow-up"></i> Udostępnione przeze mnie</h2>
<?php if (!$przeze_mnie): ?>
<p class="text-muted">Nie udostępniasz nic nikomu. Użyj ikony <i class="bi bi-share"></i> przy pliku lub katalogu na swoim dysku.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover align-middle">
	<thead><tr><th>Element</th><th>Użytkownik</th><th>Od</th><th></th></tr></thead>
	<tbody>
<?php foreach ($przeze_mnie as [$sciezka, $onazwa, $kiedy]): ?>
		<tr>
			<td>/<?php echo e($login . '/' . $sciezka); ?></td>
			<td><i class="bi bi-person"></i> <?php echo e($onazwa); ?></td>
			<td><?php echo e($kiedy); ?></td>
			<td class="text-end"><a class="btn btn-sm btn-outline-success" href="udostepnij.php?p=<?php echo e(rawurlencode($sciezka)); ?>"><i class="bi bi-gear"></i> Zarządzaj</a></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<?php endif; ?>
<?php mysqli_close($link); require 'dol.php'; ?>
