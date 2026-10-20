<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */
// Punkt 6b: rejestr wszystkich prób logowania (tabela goscieportalu) -- tylko dla administratora. Filtr po wyniku i loginie.
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();
if (!$admin) { flash('danger', 'Ta strona jest dostępna tylko dla administratora.'); header('Location: dysk.php'); exit(); }

$wynik = (string)($_GET['wynik'] ?? '');
$szukaj = trim((string)($_GET['login'] ?? ''));
$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

$gdzie = []; $typy = ''; $par = [];
if (in_array($wynik, ['OK', 'BLAD', 'BLOKADA'], true)) { $gdzie[] = 'wynik = ?'; $typy .= 's'; $par[] = $wynik; }
if ($szukaj !== '') { $gdzie[] = 'login LIKE ?'; $typy .= 's'; $par[] = '%' . $szukaj . '%'; }
$sql = 'SELECT `datetime`, ipaddress, wynik, przegladarka, login FROM goscieportalu' . ($gdzie ? ' WHERE ' . implode(' AND ', $gdzie) : '') . ' ORDER BY id DESC LIMIT 500';
$q = mysqli_prepare($link, $sql);
if ($par) { mysqli_stmt_bind_param($q, $typy, ...$par); }
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $c, $i, $wy, $ua, $lg);
$wiersze = [];
while (mysqli_stmt_fetch($q)) { $wiersze[] = [$c, $i, $wy, $ua, $lg]; }
mysqli_stmt_close($q);
mysqli_close($link);

$tytul = 'Wszystkie logowania (admin)';
require 'gora.php';
require_once 'goscie_tabela.php';
?>
<form method="get" class="row g-2 mb-3" style="max-width:640px;">
	<div class="col-12 col-sm-4"><input type="text" class="form-control" name="login" placeholder="Login zawiera..." value="<?php echo e($szukaj); ?>"></div>
	<div class="col-8 col-sm-5">
		<select name="wynik" class="form-select">
			<option value="">Wszystkie wyniki</option>
			<option value="OK"<?php echo $wynik === 'OK' ? ' selected' : ''; ?>>udane</option>
			<option value="BLAD"<?php echo $wynik === 'BLAD' ? ' selected' : ''; ?>>błędne</option>
			<option value="BLOKADA"<?php echo $wynik === 'BLOKADA' ? ' selected' : ''; ?>>zablokowane</option>
		</select>
	</div>
	<div class="col-4 col-sm-3"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filtruj</button></div>
</form>
<p class="text-muted">Najnowsze 500 wpisów tabeli <code>goscieportalu</code>.</p>
<?php
tabela_logowan($wiersze, true);
require 'dol.php';
