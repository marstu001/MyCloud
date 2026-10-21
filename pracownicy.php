<?php declare(strict_types=1);
require_once 'monitor.php';
wymagaj_logowania(true);
$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	$st = mysqli_prepare($link, "UPDATE users SET worker = 1 - worker WHERE id = ?");
	mysqli_stmt_bind_param($st, 'i', $id);
	mysqli_stmt_execute($st);
	mysqli_close($link);
	header('Location: pracownicy.php');
	exit();
}
$res = mysqli_query($link, "SELECT id, username, worker FROM users ORDER BY username");
$uzytkownicy = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
mysqli_close($link);
$tytul = 'Pracownicy – kto może być odpowiedzialny za hosty';
require 'gora.php';
?>
<div class="table-responsive col-12 col-lg-6 p-0">
<table class="table table-striped align-middle">
	<thead class="table-dark"><tr><th>Id</th><th>Użytkownik</th><th>Pracownik</th><th></th></tr></thead>
	<tbody>
<?php foreach ($uzytkownicy as $u): ?>
		<tr>
			<td><?php echo (int)$u['id']; ?></td>
			<td><?php echo e((string)$u['username']); ?></td>
			<td><?php echo (int)$u['worker'] === 1 ? '<span class="badge text-bg-success">tak</span>' : '<span class="badge text-bg-secondary">nie</span>'; ?></td>
			<td>
				<form method="post" action="pracownicy.php" class="m-0">
					<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
					<button class="btn btn-sm btn-outline-primary" type="submit"><?php echo (int)$u['worker'] === 1 ? 'Odbierz uprawnienie' : 'Ustaw jako pracownika'; ?></button>
				</form>
			</td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</div>
<?php require 'dol.php'; ?>
