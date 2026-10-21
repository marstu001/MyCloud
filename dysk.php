<?php declare(strict_types=1);
require_once 'lib.php';
require_once 'geo.php';
[$uid, $login, $admin] = wymagaj_logowania();

$wyslij = !empty($_SESSION['wizyta_id']) && empty($_SESSION['klient_wyslany']);
if ($wyslij) { $_SESSION['klient_wyslany'] = true; }

$w     = (int)($_GET['w'] ?? 0);
$wlasny = ($w === 0 || $w === $uid);
if ($wlasny) { $w = 0; }
$rel   = trim((string)($_GET['p'] ?? ''), '/');
$seg   = sciezka_segmenty($rel);
if ($seg === null) { flash('danger', 'Niepoprawna ścieżka katalogu.'); header('Location: dysk.php'); exit(); }

$link = db_polacz();
if (!$link) { exit('Błąd połączenia z bazą danych.'); }

$wlasciciel = $login;
if (!$wlasny)
{
	$q = mysqli_prepare($link, 'SELECT username FROM users WHERE id = ?');
	mysqli_stmt_bind_param($q, 'i', $w);
	mysqli_stmt_execute($q);
	mysqli_stmt_bind_result($q, $nazwa_wl);
	$istnieje = mysqli_stmt_fetch($q);
	mysqli_stmt_close($q);
	if (!$istnieje || $rel === '' || !czy_udostepnione($link, $w, $uid, $rel))
	{
		flash('danger', 'Ten katalog nie jest Ci udostępniony.');
		header('Location: udostepnione.php');
		exit();
	}
	$wlasciciel = (string)$nazwa_wl;
}

$pelna = dysk_pelna($wlasciciel, $seg);
if ($pelna === null || !is_dir($pelna))
{
	flash('danger', 'Taki katalog nie istnieje.');
	header('Location: ' . ($wlasny ? 'dysk.php' : 'udostepnione.php'));
	exit();
}

if (isset($_GET['widok']) && in_array($_GET['widok'], ['lista', 'miniatury'], true)) { $_SESSION['widok'] = $_GET['widok']; }
if (isset($_GET['szczegoly'])) { $_SESSION['szczegoly'] = $_GET['szczegoly'] === '1' ? 1 : 0; }
$widok     = $_SESSION['widok'] ?? 'lista';
$szczegoly = (int)($_SESSION['szczegoly'] ?? 0) === 1;

[$katalogi, $pliki] = dysk_lista($pelna);

$ostrzezenie = (string)($_SESSION['ostrzezenie'] ?? '');
unset($_SESSION['ostrzezenie']);

$adres = function (array $zmiany) use ($rel, $w): string {
	$q = array_merge(array_filter(['w' => $w ?: null, 'p' => $rel !== '' ? $rel : null]), $zmiany);
	return 'dysk.php?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
};

$tytul = $wlasny ? 'Mój dysk' : 'Katalog użytkownika ' . $wlasciel;
require 'gora.php';

foreach (flash_pobierz() as [$typ, $tekst]) { echo '<div class="alert alert-' . e($typ) . '" role="alert">' . e($tekst) . '</div>' . "\n"; }
if ($ostrzezenie !== '') { echo '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <strong>' . e($ostrzezenie) . '</strong></div>' . "\n"; }

$okruchy = [];
$lacznie = '';
foreach ($seg as $s)
{
	$lacznie .= ($lacznie === '' ? '' : '/') . $s;
	$okruchy[] = [$s, $lacznie];
}
$rodzic = implode('/', array_slice($seg, 0, -1));
?>
<nav aria-label="breadcrumb">
	<ol class="breadcrumb mb-2">
<?php if ($wlasny): ?>
		<li class="breadcrumb-item"><a href="<?php echo e(url_dysk()); ?>"><i class="bi bi-house-door"></i> <?php echo e($wlasciciel); ?></a></li>
<?php else: ?>
		<li class="breadcrumb-item"><a href="udostepnione.php"><i class="bi bi-share"></i> Udostępnione mi</a></li>
		<li class="breadcrumb-item"><?php echo e($wlasciciel); ?></li>
<?php endif; ?>
<?php foreach ($okruchy as $i => [$nazwa, $sciezka]): ?>
		<li class="breadcrumb-item<?php echo $i === count($okruchy) - 1 ? ' active' : ''; ?>"><?php if ($i < count($okruchy) - 1): ?><a href="<?php echo e(url_dysk($sciezka, $w)); ?>"><?php echo e($nazwa); ?></a><?php else: echo e($nazwa); endif; ?></li>
<?php endforeach; ?>
	</ol>
</nav>
<?php if (!$wlasny): ?>
<div class="alert alert-info py-2"><i class="bi bi-eye"></i> Katalog udostępniony Ci przez użytkownika <strong><?php echo e($wlasciciel); ?></strong> &ndash; tylko do odczytu i pobierania.</div>
<?php endif; ?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
<?php
	if ($rel !== '')
	{
		$gora = $wlasny ? url_dysk($rodzic) : (($rodzic !== '' && czy_udostepnione($link, $w, $uid, $rodzic)) ? url_dysk($rodzic, $w) : 'udostepnione.php');
		echo '<a class="btn btn-outline-secondary" href="' . e($gora) . '" title="Poziom wyżej (level up)"><i class="bi bi-arrow-90deg-up"></i> Wyżej</a>';
	}
	if ($wlasny && count($seg) < DYSK_MAX_GLEBOKOSC)
	{
		echo '<a class="btn btn-outline-primary" href="nowy_katalog.php?p=' . e(rawurlencode($rel)) . '" title="Utwórz podkatalog"><i class="bi bi-folder-plus"></i> Nowy katalog</a>';
	}
	if ($wlasny)
	{
		echo '<a class="btn btn-primary" href="select.php?p=' . e(rawurlencode($rel)) . '" title="Prześlij plik na serwer"><i class="bi bi-cloud-upload"></i> Prześlij plik</a>';
	}
?>
	<div class="ms-auto d-flex flex-wrap gap-2">
		<div class="btn-group" role="group" aria-label="Widok plików">
			<a class="btn btn-sm <?php echo $widok === 'lista' ? 'btn-secondary' : 'btn-outline-secondary'; ?>" href="<?php echo e($adres(['widok' => 'lista'])); ?>" title="Zwykła lista nazw"><i class="bi bi-list-ul"></i> Lista</a>
			<a class="btn btn-sm <?php echo $widok === 'miniatury' ? 'btn-secondary' : 'btn-outline-secondary'; ?>" href="<?php echo e($adres(['widok' => 'miniatury'])); ?>" title="Miniatury, odtwarzacze audio i wideo"><i class="bi bi-grid-3x3-gap"></i> Miniatury i odtwarzacze</a>
		</div>
		<a class="btn btn-sm <?php echo $szczegoly ? 'btn-secondary' : 'btn-outline-secondary'; ?>" href="<?php echo e($adres(['szczegoly' => $szczegoly ? '0' : '1'])); ?>" title="Data i rozmiar plików"><i class="bi bi-info-circle"></i> Data i rozmiar</a>
	</div>
</div>

<?php
$pusto = !$katalogi && !$pliki;
if ($pusto) { echo '<p class="text-muted"><i class="bi bi-folder2-open"></i> Ten katalog jest pusty.</p>'; }

$akcje = function (string $nazwa, bool $katalog) use ($wlasny, $rel, $w): string {
	$el  = ($rel !== '' ? $rel . '/' : '') . $nazwa;
	$out = '';
	if (!$katalog)
	{
		if (typ_pliku($nazwa) !== 'inne') { $out .= '<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="' . e(url_pobierz($el, $w, true)) . '" title="Otwórz w przeglądarce"><i class="bi bi-eye"></i></a> '; }
		$out .= '<a class="btn btn-sm btn-outline-primary" href="' . e(url_pobierz($el, $w)) . '" title="Pobierz"><i class="bi bi-download"></i></a> ';
	}
	if ($wlasny)
	{
		$out .= '<a class="btn btn-sm btn-outline-success" href="udostepnij.php?p=' . e(rawurlencode($el)) . '" title="Udostępnij innym użytkownikom"><i class="bi bi-share"></i></a> ';
		$out .= '<form method="post" action="kasuj.php" class="d-inline" onsubmit="return confirm(' . e(json_encode(($katalog ? 'Usunąć katalog „' : 'Usunąć plik „') . $nazwa . ($katalog ? '” razem z całą zawartością?' : '”?'), JSON_UNESCAPED_UNICODE)) . ');">'
			. csrf_pole() . '<input type="hidden" name="p" value="' . e($el) . '"><input type="hidden" name="wroc" value="' . e($rel) . '">'
			. '<button class="btn btn-sm btn-outline-danger" type="submit" title="Usuń"><i class="bi bi-trash"></i></button></form>';
	}
	return $out;
};
$el_sciezka = fn(string $nazwa): string => ($rel !== '' ? $rel . '/' : '') . $nazwa;

if (!$pusto && $widok === 'lista'):
?>
<div class="table-responsive">
<table class="table table-hover align-middle">
	<thead><tr><th>Nazwa</th><?php if ($szczegoly): ?><th>Data modyfikacji</th><th class="text-end">Rozmiar</th><?php endif; ?><th class="text-end">Akcje</th></tr></thead>
	<tbody>
<?php foreach ($katalogi as $k): ?>
		<tr>
			<td><i class="bi bi-folder-fill text-warning"></i> <a href="<?php echo e(url_dysk($el_sciezka($k['nazwa']), $w)); ?>"><?php echo e($k['nazwa']); ?></a></td>
<?php if ($szczegoly): ?>			<td><?php echo date('Y-m-d H:i', $k['czas']); ?></td><td class="text-end">&ndash;</td><?php endif; ?>
			<td class="text-end text-nowrap"><?php echo $akcje($k['nazwa'], true); ?></td>
		</tr>
<?php endforeach; ?>
<?php foreach ($pliki as $f): ?>
		<tr>
			<td><i class="bi <?php echo e(ikona_pliku($f['nazwa'])); ?>"></i> <a href="<?php echo e(url_pobierz($el_sciezka($f['nazwa']), $w)); ?>"><?php echo e($f['nazwa']); ?></a></td>
<?php if ($szczegoly): ?>			<td><?php echo date('Y-m-d H:i', $f['czas']); ?></td><td class="text-end"><?php echo e(format_rozmiaru($f['rozmiar'])); ?></td><?php endif; ?>
			<td class="text-end text-nowrap"><?php echo $akcje($f['nazwa'], false); ?></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</div>
<?php elseif (!$pusto): ?>
<div class="row g-3">
<?php foreach ($katalogi as $k): ?>
	<div class="col-12 col-sm-6 col-lg-4 col-xl-3">
		<div class="card h-100 kafel">
			<div class="card-body text-center">
				<a href="<?php echo e(url_dysk($el_sciezka($k['nazwa']), $w)); ?>" class="kafel-ikona"><i class="bi bi-folder-fill text-warning"></i></a>
				<div class="mt-1"><a href="<?php echo e(url_dysk($el_sciezka($k['nazwa']), $w)); ?>"><?php echo e($k['nazwa']); ?></a></div>
<?php if ($szczegoly): ?>				<div class="small text-muted"><?php echo date('Y-m-d H:i', $k['czas']); ?></div><?php endif; ?>
			</div>
			<div class="card-footer text-end text-nowrap"><?php echo $akcje($k['nazwa'], true); ?></div>
		</div>
	</div>
<?php endforeach; ?>
<?php foreach ($pliki as $f): $typ = typ_pliku($f['nazwa']); $el = $el_sciezka($f['nazwa']); ?>
	<div class="col-12 col-sm-6 col-lg-4 col-xl-3">
		<div class="card h-100 kafel">
			<div class="card-body text-center">
<?php if ($typ === 'obraz'): ?>
				<a href="#" class="miniatura" data-bs-toggle="modal" data-bs-target="#podgladObrazu" data-src="<?php echo e(url_pobierz($el, $w, true)); ?>" data-nazwa="<?php echo e($f['nazwa']); ?>">
					<img src="<?php echo e(url_pobierz($el, $w, true)); ?>" loading="lazy" alt="<?php echo e($f['nazwa']); ?>" class="img-thumbnail kafel-obraz">
				</a>
<?php elseif ($typ === 'wideo'): ?>
				<video controls preload="none" class="kafel-wideo"><source src="<?php echo e(url_pobierz($el, $w, true)); ?>" type="<?php echo e(MIME_INLINE[strtolower(pathinfo($f['nazwa'], PATHINFO_EXTENSION))]); ?>">Przeglądarka nie obsługuje tego odtwarzacza.</video>
<?php elseif ($typ === 'audio'): ?>
				<div class="kafel-ikona"><i class="bi <?php echo e(ikona_pliku($f['nazwa'])); ?>"></i></div>
				<audio controls preload="none" class="w-100"><source src="<?php echo e(url_pobierz($el, $w, true)); ?>" type="<?php echo e(MIME_INLINE[strtolower(pathinfo($f['nazwa'], PATHINFO_EXTENSION))]); ?>">Przeglądarka nie obsługuje tego odtwarzacza.</audio>
<?php else: ?>
				<a href="<?php echo e(url_pobierz($el, $w)); ?>" class="kafel-ikona"><i class="bi <?php echo e(ikona_pliku($f['nazwa'])); ?>"></i></a>
<?php endif; ?>
				<div class="mt-1 text-break"><a href="<?php echo e(url_pobierz($el, $w)); ?>"><?php echo e($f['nazwa']); ?></a></div>
<?php if ($szczegoly): ?>				<div class="small text-muted"><?php echo date('Y-m-d H:i', $f['czas']); ?> &middot; <?php echo e(format_rozmiaru($f['rozmiar'])); ?></div><?php endif; ?>
			</div>
			<div class="card-footer text-end text-nowrap"><?php echo $akcje($f['nazwa'], false); ?></div>
		</div>
	</div>
<?php endforeach; ?>
</div>

<div class="modal fade" id="podgladObrazu" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header"><h5 class="modal-title" id="podgladNazwa"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button></div>
			<div class="modal-body text-center"><img id="podgladObraz" src="" alt="" class="img-fluid"></div>
		</div>
	</div>
</div>
<script>
document.getElementById('podgladObrazu').addEventListener('show.bs.modal', function (ev) {
	var a = ev.relatedTarget;
	document.getElementById('podgladObraz').src = a.getAttribute('data-src');
	document.getElementById('podgladNazwa').textContent = a.getAttribute('data-nazwa');
});
document.getElementById('podgladObrazu').addEventListener('hidden.bs.modal', function () { document.getElementById('podgladObraz').src = ''; });
</script>
<?php endif; ?>
<p class="text-muted small mt-3">Katalogów: <?php echo count($katalogi); ?>, plików: <?php echo count($pliki); ?>.</p>
<?php if ($wyslij): ?>
<script src="klient.js"></script>
<script>wyslijDaneKlienta(<?php echo (int)$_SESSION['wizyta_id']; ?>, <?php echo json_encode(wizyta_token((int)$_SESSION['wizyta_id'])); ?>);</script>
<?php endif; ?>
<?php mysqli_close($link); require 'dol.php'; ?>
