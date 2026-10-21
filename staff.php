<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid] = wymagaj_logowania(true);
$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$host = trim((string)($_POST['host'] ?? ''));
	$port = (int)($_POST['port'] ?? 0);
	$typ  = isset(TYPY_URZADZEN[$_POST['typ'] ?? '']) ? (string)$_POST['typ'] : 'www';
	$wid  = (int)($_POST['worker_id'] ?? 0);
	$czy  = $wid > 0 ? mysqli_prepare($link, "SELECT id FROM users WHERE id = ? AND worker = 1") : null;
	$pracownik_ok = false;
	if ($czy) { mysqli_stmt_bind_param($czy, 'i', $wid); mysqli_stmt_execute($czy); $pracownik_ok = mysqli_stmt_get_result($czy)->num_rows === 1; }
	if (!host_poprawny($host) || $port < 1 || $port > 65535 || !$pracownik_ok) {
		header('Location: staff.php?blad=' . urlencode('Podaj poprawny host, port i wybierz pracownika z listy.'));
		exit();
	}
	$st = mysqli_prepare($link, "INSERT INTO domeny (host, port, user_id, typ, worker_id) VALUES (?, ?, ?, ?, ?)");
	mysqli_stmt_bind_param($st, 'siisi', $host, $port, $uid, $typ, $wid);
	$ok = mysqli_stmt_execute($st);
	mysqli_close($link);
	header('Location: index1_admin.php?info=' . urlencode($ok ? "Dodano host $host:$port z przypisanym pracownikiem." : 'Nie udało się dodać hosta.'));
	exit();
}

$res = mysqli_query($link, "SELECT id, username FROM users WHERE worker = 1 ORDER BY username");
$pracownicy = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
mysqli_close($link);
$blad = (string)($_GET['blad'] ?? '');
$tytul = 'Dodaj host z odpowiedzialnym pracownikiem';
require 'gora.php';
?>
<?php if ($blad !== ''): ?><div class="alert alert-danger" role="alert"><?php echo e($blad); ?></div><?php endif; ?>
<?php if (!$pracownicy): ?>
<div class="alert alert-warning">Nikt nie jest jeszcze pracownikiem. Oznacz użytkowników na stronie <a href="pracownicy.php">Pracownicy</a>.</div>
<?php endif; ?>
<form method="post" action="staff.php" class="col-12 col-md-8 col-lg-5 p-0">
	<div class="mb-3">
		<label for="host" class="form-label">Host</label>
		<input type="text" class="form-control" id="host" name="host" maxlength="255" required>
	</div>
	<div class="mb-3">
		<label for="port" class="form-label">Port</label>
		<input type="number" class="form-control" id="port" name="port" min="1" max="65535" value="80" required>
	</div>
	<div class="mb-3">
		<label for="typ" class="form-label">Rodzaj urządzenia</label>
		<select class="form-select" id="typ" name="typ">
<?php foreach (TYPY_URZADZEN as $k => $nazwa): ?>
			<option value="<?php echo $k; ?>"><?php echo e($nazwa); ?></option>
<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label for="worker_id" class="form-label">Pracownik odpowiedzialny</label>
		<select class="form-select" id="worker_id" name="worker_id" required>
<?php foreach ($pracownicy as $p): ?>
			<option value="<?php echo (int)$p['id']; ?>"><?php echo e((string)$p['username']); ?></option>
<?php endforeach; ?>
		</select>
	</div>
	<button type="submit" class="btn btn-primary"<?php echo $pracownicy ? '' : ' disabled'; ?>><i class="bi bi-plus-circle"></i> Dodaj</button>
	<a class="btn btn-outline-secondary" href="index1_admin.php">Anuluj</a>
</form>
<?php require 'dol.php'; ?>
