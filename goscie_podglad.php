<?php declare(strict_types=1);
require_once 'lib.php';
[$uid, $login, $admin] = wymagaj_logowania();

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }
$q = mysqli_prepare($link, 'SELECT `datetime`, ipaddress, wynik, przegladarka FROM goscieportalu WHERE login = ? ORDER BY id DESC LIMIT 200');
mysqli_stmt_bind_param($q, 's', $login);
mysqli_stmt_execute($q);
mysqli_stmt_bind_result($q, $czas, $ip, $wynik, $ua);
$wiersze = [];
while (mysqli_stmt_fetch($q)) { $wiersze[] = [$czas, $ip, $wynik, $ua]; }
mysqli_stmt_close($q);
mysqli_close($link);

$tytul = 'Moje logowania';
require 'gora.php';
require_once 'goscie_tabela.php';
?>
<?php
tabela_logowan($wiersze, false);
require 'dol.php';
