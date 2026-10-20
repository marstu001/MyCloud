<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */
// Tworzenie podkatalogu (punkt 6f): formularz z nazwą + mkdir() wewnątrz katalogu macierzystego użytkownika.
// Zgodnie z opcją 8b można zagłębiać katalogi do DYSK_MAX_GLEBOKOSC (4) poziomów.
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$rel = trim((string)($_REQUEST['p'] ?? ''), '/');
$seg = sciezka_segmenty($rel);
$cel = $seg === null ? null : dysk_pelna($login, $seg);
if ($cel === null || !is_dir($cel)) { flash('danger', 'Niepoprawny katalog.'); header('Location: dysk.php'); exit(); }
if (count($seg) >= DYSK_MAX_GLEBOKOSC)
{
	flash('warning', 'Osiągnięto maksymalną głębokość katalogów (' . DYSK_MAX_GLEBOKOSC . ' poziomy).');
	header('Location: ' . url_dysk($rel));
	exit();
}

$blad = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	csrf_wymagaj();
	$nazwa = trim((string)($_POST['nazwa'] ?? ''));
	if (!nazwa_katalogu_poprawna($nazwa))
	{
		$blad = 'Niepoprawna nazwa. Użyj liter, cyfr, spacji oraz znaków _ ( ) . , + - (maksymalnie 60 znaków, bez kropki na początku i na końcu).';
	}
	elseif (file_exists($cel . '/' . $nazwa))
	{
		$blad = 'Plik lub katalog o takiej nazwie już istnieje.';
	}
	elseif (!@mkdir($cel . '/' . $nazwa, 0755))
	{
		$blad = 'Nie udało się utworzyć katalogu na serwerze.';
	}
	else
	{
		flash('success', 'Utworzono katalog „' . $nazwa . '”.');
		header('Location: ' . url_dysk($rel));
		exit();
	}
}

$tytul = 'Nowy podkatalog';
require 'gora.php';
?>
<p>Katalog nadrzędny: <strong>/<?php echo e($login . ($rel !== '' ? '/' . $rel : '')); ?></strong></p>
<?php if ($blad !== ''): ?><div class="alert alert-danger"><?php echo e($blad); ?></div><?php endif; ?>
<form method="post" action="nowy_katalog.php" class="mb-3" style="max-width:480px;">
	<?php echo csrf_pole(); ?>
	<input type="hidden" name="p" value="<?php echo e($rel); ?>">
	<label for="nazwa" class="form-label">Nazwa nowego podkatalogu</label>
	<input type="text" class="form-control mb-3" id="nazwa" name="nazwa" maxlength="60" value="<?php echo e((string)($_POST['nazwa'] ?? '')); ?>" required autofocus>
	<button class="btn btn-primary" type="submit"><i class="bi bi-folder-plus"></i> Utwórz</button>
	<a class="btn btn-outline-secondary" href="<?php echo e(url_dysk($rel)); ?>">Anuluj</a>
</form>
<?php require 'dol.php'; ?>
