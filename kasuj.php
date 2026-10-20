<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();
csrf_wymagaj();

$rel   = trim((string)($_POST['p'] ?? ''), '/');
$wroc  = trim((string)($_POST['wroc'] ?? ''), '/');
$seg   = sciezka_segmenty($rel, true);
$pelna = ($seg && $seg !== []) ? dysk_pelna($login, $seg) : null;
$cofnij = 'Location: ' . (sciezka_segmenty($wroc) !== null ? url_dysk($wroc) : 'dysk.php');

if ($pelna === null || (!is_file($pelna) && !is_dir($pelna)))
{
	flash('danger', 'Nie ma takiego pliku ani katalogu.');
	header($cofnij);
	exit();
}
$nazwa = basename($pelna);
if (dysk_usun($pelna))
{
	$link = db_polacz();
	if ($link) { usun_udostepnienia($link, $uid, $rel); mysqli_close($link); }
	flash('success', 'Usunięto „' . $nazwa . '”.');
}
else { flash('danger', 'Nie udało się usunąć „' . $nazwa . '”.'); }
header($cofnij);
exit();
