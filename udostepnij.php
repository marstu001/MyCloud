<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */
// Opcja 8c: udostępnianie plików i katalogów innym użytkownikom portalu (tylko odczyt i pobieranie), z możliwością cofnięcia.
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$rel   = trim((string)($_REQUEST['p'] ?? ''), '/');
$seg   = sciezka_segmenty($rel, true);
$pelna = ($seg && $seg !== []) ? dysk_pelna($login, $seg) : null;
if ($pelna === null || (!is_file($pelna) && !is_dir($pelna))) { flash('danger', 'Nie ma takiego pliku ani katalogu.'); header('Location: dysk.php'); exit(); }
$katalog = is_dir($pelna);
$rodzic  = implode('/', array_slice($seg, 0, -1));

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	csrf_wymagaj();
	$akcja = (string)($_POST['akcja'] ?? '');
	if ($akcja === 'dodaj')
	{
		$odbiorcy = array_map('intval', (array)($_POST['odbiorca'] ?? []));
		$dodano = 0;
		foreach ($odbiorcy as $o)
		{
			if ($o <= 0 || $o === $uid) { continue; }
			$ins = mysqli_prepare($link, 'INSERT IGNORE INTO udostepnienia (wlasciciel_id, odbiorca_id, sciezka) SELECT ?, id, ? FROM users WHERE id = ?');
			mysqli_stmt_bind_param($ins, 'isi', $uid, $rel, $o);
			mysqli_stmt_execute($ins);
			$dodano += max(0, mysqli_stmt_affected_rows($ins));
			mysqli_stmt_close($ins);
		}
		flash($dodano ? 'success' : 'warning', $dodano ? 'Udostępniono „' . $seg[count($seg) - 1] . '” (' . $dodano . ' użytkownikom).' : 'Nic nie zostało dodane (nie wybrano użytkownika albo jest już udostępnione).');
	}
	elseif ($akcja === 'cofnij')
	{
		$id = (int)($_POST['id'] ?? 0);
		$del = mysqli_prepare($link, 'DELETE FROM udostepnienia WHERE id = ? AND wlasciciel_id = ?');   // można cofnąć tylko własne udostępnienie
		mysqli_stmt_bind_param($del, 'ii', $id, $uid);
		mysqli_stmt_execute($del);
		mysqli_stmt_close($del);
		flash('success', 'Cofnięto udostępnienie.');
	}
	header('Location: udostepnij.php?p=' . rawurlencode($rel));
	exit();
}

// kto już ma dostęp
$obecni = [];
$q = mysqli_prepare($link, 'SELECT u.id, us.username, u.utworzono FROM udostepnienia u JOIN users us ON us.id = u.odbiorca_id WHERE u.wlasciciel_id = ? AND u.sciezka = ? ORDER BY us.username');
mysqli_stmt_bind_param($q, 'is', $uid, $rel);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $uid_ud, $uname, $kiedy);
while (mysqli_stmt_fetch($q)) { $obecni[] = [$uid_ud, $uname, $kiedy]; }
mysqli_stmt_close($q);
// pozostali użytkownicy
$inni = [];
$r = mysqli_query($link, 'SELECT id, username FROM users WHERE id <> ' . $uid . ' AND id NOT IN (SELECT odbiorca_id FROM udostepnienia WHERE wlasciciel_id = ' . $uid . " AND sciezka = '" . mysqli_real_escape_string($link, $rel) . "') ORDER BY username");
while ($r && ($w = mysqli_fetch_row($r))) { $inni[] = $w; }

$tytul = 'Udostępnianie: ' . $seg[count($seg) - 1];
require 'gora.php';
foreach (flash_pobierz() as [$typ, $tekst]) { echo '<div class="alert alert-' . e($typ) . '" role="alert">' . e($tekst) . '</div>' . "\n"; }
?>
<p><i class="bi <?php echo $katalog ? 'bi-folder-fill text-warning' : e(ikona_pliku($seg[count($seg) - 1])); ?>"></i> <strong>/<?php echo e($login . '/' . $rel); ?></strong>
<?php echo $katalog ? ' (katalog razem z całą zawartością)' : ''; ?></p>

<div class="row g-4">
	<div class="col-12 col-lg-6">
		<h2 class="h5">Udostępnij użytkownikom</h2>
<?php if (!$inni): ?>
		<p class="text-muted">Nie ma więcej użytkowników, którym można udostępnić ten element.</p>
<?php else: ?>
		<form method="post" action="udostepnij.php">
			<?php echo csrf_pole(); ?>
			<input type="hidden" name="p" value="<?php echo e($rel); ?>">
			<input type="hidden" name="akcja" value="dodaj">
			<select name="odbiorca[]" class="form-select mb-3" multiple size="<?php echo min(8, max(3, count($inni))); ?>" required>
<?php foreach ($inni as [$id, $nazwa]): ?>
				<option value="<?php echo (int)$id; ?>"><?php echo e($nazwa); ?></option>
<?php endforeach; ?>
			</select>
			<div class="form-text mb-3">Zaznacz jednego lub kilku użytkowników (Ctrl + klik). Dostaną dostęp tylko do odczytu i pobierania.</div>
			<button class="btn btn-success" type="submit"><i class="bi bi-share"></i> Udostępnij</button>
		</form>
<?php endif; ?>
	</div>
	<div class="col-12 col-lg-6">
		<h2 class="h5">Ma dostęp</h2>
<?php if (!$obecni): ?>
		<p class="text-muted">Nikt jeszcze nie ma dostępu do tego elementu.</p>
<?php else: ?>
		<ul class="list-group">
<?php foreach ($obecni as [$id, $nazwa, $kiedy]): ?>
			<li class="list-group-item d-flex justify-content-between align-items-center">
				<span><i class="bi bi-person"></i> <?php echo e($nazwa); ?> <small class="text-muted">od <?php echo e($kiedy); ?></small></span>
				<form method="post" action="udostepnij.php" class="m-0">
					<?php echo csrf_pole(); ?>
					<input type="hidden" name="p" value="<?php echo e($rel); ?>">
					<input type="hidden" name="akcja" value="cofnij">
					<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
					<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-circle"></i> Cofnij</button>
				</form>
			</li>
<?php endforeach; ?>
		</ul>
<?php endif; ?>
	</div>
</div>
<p class="mt-4"><a class="btn btn-outline-secondary" href="<?php echo e(url_dysk($rodzic)); ?>"><i class="bi bi-arrow-left"></i> Wróć do dysku</a></p>
<?php mysqli_close($link); require 'dol.php'; ?>
