<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0)
{
	flash('danger', 'Przesyłane pliki są większe niż limit serwera (' . ini_get('post_max_size') . ').');
	header('Location: select.php');
	exit();
}
csrf_wymagaj();

$rel = trim((string)($_POST['p'] ?? ''), '/');
$seg = sciezka_segmenty($rel);
$target_dir = $seg === null ? null : dysk_pelna($login, $seg);
if ($target_dir === null || !is_dir($target_dir)) { flash('danger', 'Niepoprawny katalog docelowy.'); header('Location: dysk.php'); exit(); }

$bledy_php = [
	UPLOAD_ERR_INI_SIZE   => 'plik jest większy niż limit serwera (' . ini_get('upload_max_filesize') . ')',
	UPLOAD_ERR_FORM_SIZE  => 'plik jest za duży',
	UPLOAD_ERR_PARTIAL    => 'plik został przesłany tylko częściowo',
	UPLOAD_ERR_NO_FILE    => 'nie wybrano pliku',
	UPLOAD_ERR_NO_TMP_DIR => 'brak katalogu tymczasowego na serwerze',
	UPLOAD_ERR_CANT_WRITE => 'serwer nie może zapisać pliku na dysku',
	UPLOAD_ERR_EXTENSION  => 'przesyłanie zablokowane przez rozszerzenie PHP',
];

$pliki = $_FILES['fileToUpload'] ?? null;
$ok = 0;
if ($pliki && is_array($pliki['name']))
{
	foreach ($pliki['name'] as $i => $nazwa_org)
	{
		$kod = (int)$pliki['error'][$i];
		if ($kod === UPLOAD_ERR_NO_FILE) { continue; }
		$etykieta = (string)$nazwa_org;
		if ($kod !== UPLOAD_ERR_OK) { flash('danger', 'Error uploading file ' . $etykieta . ': ' . ($bledy_php[$kod] ?? 'kod błędu ' . $kod) . '.'); continue; }
		$nazwa = nazwa_pliku_czysta((string)$nazwa_org);
		if ($nazwa === '') { flash('danger', 'Error uploading file: niepoprawna nazwa pliku.'); continue; }
		$nazwa = dysk_wolna_nazwa($target_dir, $nazwa);
		$target_file = $target_dir . '/' . $nazwa;
		if (move_uploaded_file($pliki['tmp_name'][$i], $target_file))
		{
			@chmod($target_file, 0644);
			flash('success', $nazwa . ' uploaded.');
			$ok++;
		}
		else { flash('danger', 'Error uploading file ' . $etykieta . '.'); }
	}
}
if ($ok === 0 && empty($_SESSION['flash'])) { flash('warning', 'Nie wybrano żadnego pliku.'); }
header('Location: ' . url_dysk($rel));
exit();
