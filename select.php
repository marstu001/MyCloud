<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */
// Formularz wyboru plików do przesłania (punkt 6i, 6j): wersja select.php z instrukcji, rozbudowana o katalog docelowy i wiele plików naraz.
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$rel = trim((string)($_GET['p'] ?? ''), '/');
$seg = sciezka_segmenty($rel);
$cel = $seg === null ? null : dysk_pelna($login, $seg);
if ($cel === null || !is_dir($cel)) { flash('danger', 'Niepoprawny katalog.'); header('Location: dysk.php'); exit(); }

$tytul = 'Prześlij plik na serwer';
require 'gora.php';
foreach (flash_pobierz() as [$typ, $tekst]) { echo '<div class="alert alert-' . e($typ) . '" role="alert">' . e($tekst) . '</div>' . "\n"; }
?>
<p>Plik zostanie zapisany w katalogu: <strong>/<?php echo e($login . ($rel !== '' ? '/' . $rel : '')); ?></strong></p>
<form action="upload.php" method="post" enctype="multipart/form-data" class="mb-3" style="max-width:560px;">
	<?php echo csrf_pole(); ?>
	<input type="hidden" name="p" value="<?php echo e($rel); ?>">
	<label for="fileToUpload" class="form-label">Select file to upload (Wybierz pliki do przesłania):</label>
	<input class="form-control mb-3" type="file" name="fileToUpload[]" id="fileToUpload" multiple required>
	<button class="btn btn-primary" type="submit" name="submit" value="Upload"><i class="bi bi-cloud-upload"></i> Upload</button>
	<a class="btn btn-outline-secondary" href="<?php echo e(url_dysk($rel)); ?>">Anuluj</a>
</form>
<p class="text-muted small">Możesz wybrać kilka plików naraz, dowolnego typu. Limity serwera: pojedynczy plik do <strong><?php echo e((string)ini_get('upload_max_filesize')); ?></strong>, łącznie do <strong><?php echo e((string)ini_get('post_max_size')); ?></strong>.
Jeśli plik o takiej nazwie już istnieje, nowy dostanie dopisek (1), (2)... &ndash; nic nie zostanie nadpisane.</p>
<?php require 'dol.php'; ?>
