<?php declare(strict_types=1);
require_once 'monitor.php';
wymagaj_logowania();
$blad = (string)($_GET['blad'] ?? '');
$tytul = 'Dodaj host do monitoringu';
require 'gora.php';
?>
<?php if ($blad !== ''): ?><div class="alert alert-danger" role="alert"><?php echo e($blad); ?></div><?php endif; ?>
<form method="post" action="dodaj2.php" class="col-12 col-md-8 col-lg-5 p-0">
	<div class="mb-3">
		<label for="host" class="form-label">Host (nazwa domenowa lub adres IP)</label>
		<input type="text" class="form-control" id="host" name="host" maxlength="255" placeholder="np. srv126134.seohost.com.pl" value="<?php echo e((string)($_GET['host'] ?? '')); ?>" required>
	</div>
	<div class="mb-3">
		<label for="port" class="form-label">Port</label>
		<input type="number" class="form-control" id="port" name="port" min="1" max="65535" value="80" required>
	</div>
	<div class="mb-3">
		<label for="typ" class="form-label">Rodzaj urządzenia (ikona)</label>
		<select class="form-select" id="typ" name="typ">
<?php foreach (TYPY_URZADZEN as $k => $nazwa): ?>
			<option value="<?php echo $k; ?>"><?php echo e($nazwa); ?></option>
<?php endforeach; ?>
		</select>
		<div class="form-text">Podpowiedź ustawia się sama po wpisaniu portu (np. 25 &rarr; serwer poczty, 3306 &rarr; baza danych).</div>
	</div>
	<button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Dodaj</button>
	<a class="btn btn-outline-secondary" href="index1.php">Anuluj</a>
</form>
<script>
$('#port').on('input', function () {
	var p = parseInt(this.value, 10), t = 'www';
	if ([25,110,143,465,587,993,995].indexOf(p) >= 0) t = 'mail';
	else if ([3306,5432,1433,27017].indexOf(p) >= 0) t = 'db';
	else if ([9100,631,515].indexOf(p) >= 0) t = 'printer';
	else if (p === 161) t = 'switch';
	$('#typ').val(t);
});
</script>
<?php require 'dol.php'; ?>
