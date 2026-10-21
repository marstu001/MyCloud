<?php declare(strict_types=1);
require_once 'monitor.php';
wymagaj_logowania();
$tytul = 'Monitorowanie usług w sieciach IP – moje hosty';
$info = (string)($_GET['info'] ?? '');
require 'gora.php';
?>
<?php if ($info !== ''): ?><div class="alert alert-info" role="alert"><?php echo e($info); ?></div><?php endif; ?>
<p class="d-flex flex-wrap gap-2 align-items-center">
	<a class="btn btn-primary" href="dodaj1.php"><i class="bi bi-plus-circle"></i> Dodaj host</a>
	<a class="btn btn-outline-danger" href="kasuj1.php"><i class="bi bi-trash"></i> Usuń host</a>
	<span class="text-muted small ms-auto"><i class="bi bi-arrow-repeat"></i> odświeżanie co <?php echo MONITOR_INTERWAL_S; ?> s (AJAX), ostatnio: <span id="czas-odswiezenia">–</span></span>
</p>
<div id="stan-hostow"><div class="text-muted">Ładowanie stanu hostów…</div></div>
<script>$(function () { startMonitor('tabela_stan.php', 'stan-hostow', <?php echo MONITOR_INTERWAL_S * 1000; ?>); });</script>
<?php require 'dol.php'; ?>
