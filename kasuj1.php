<?php declare(strict_types=1);
require_once 'monitor.php';
[$uid, $admin] = wymagaj_logowania();
$link = db_polacz();
$lista = $link ? ($admin ? monitor_pobierz($link) : monitor_pobierz($link, 'd.user_id = ?', 'i', [$uid])) : [];
if ($link) { mysqli_close($link); }
$blad  = (string)($_GET['blad'] ?? '');
$wybor = (string)($_GET['host'] ?? '') . '|' . (int)($_GET['port'] ?? 0);
$tytul = 'Usuń host z monitoringu';
require 'gora.php';
?>
<?php if ($blad !== ''): ?><div class="alert alert-danger" role="alert"><?php echo e($blad); ?></div><?php endif; ?>
<form method="post" action="kasuj2.php" class="col-12 col-md-8 col-lg-5 p-0">
	<div class="mb-3">
		<label for="hostport" class="form-label">Host i port do usunięcia</label>
		<select class="form-select" id="hostport" name="hostport" required>
<?php foreach ($lista as $w): $v = $w['host'] . '|' . $w['port']; ?>
			<option value="<?php echo e($v); ?>"<?php echo $v === $wybor ? ' selected' : ''; ?>><?php echo e($w['host'] . ':' . $w['port']); ?></option>
<?php endforeach; ?>
		</select>
	</div>
	<button type="submit" class="btn btn-danger"<?php echo $lista ? '' : ' disabled'; ?>><i class="bi bi-trash"></i> Usuń</button>
	<a class="btn btn-outline-secondary" href="index1.php">Anuluj</a>
</form>
<?php require 'dol.php'; ?>
