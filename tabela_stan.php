<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid, $admin] = wymagaj_logowania();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$link = db_polacz();
if (!$link) { http_response_code(500); exit('<div class="alert alert-danger">Błąd połączenia z bazą danych.</div>'); }
$wiersze = monitor_pobierz($link, 'd.user_id = ? OR d.worker_id = ?', 'ii', [$uid, $uid]);
monitor_odswiez($link, $wiersze);
$wiersze = monitor_pobierz($link, 'd.user_id = ? OR d.worker_id = ?', 'ii', [$uid, $uid]);
mysqli_close($link);
echo monitor_tabela($wiersze, false);
