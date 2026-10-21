<?php declare(strict_types=1);
session_start();
require_once 'avatar.php';
if (!isset($_SESSION['loggedin'])) { return; }
$admin = !empty($_SESSION['admin']);
?>
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">

		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-cloud"></i> MyCloud</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="dysk.php"><i class="bi bi-cloud"></i> Mój dysk</a></li>
						<li><a class="dropdown-item" href="select.php"><i class="bi bi-cloud-upload"></i> Prześlij plik</a></li>
						<li><a class="dropdown-item" href="udostepnione.php"><i class="bi bi-share"></i> Udostępnione mi</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-shield-check"></i> Logowania</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="goscie_podglad.php"><i class="bi bi-journal-check"></i> Moje logowania</a></li>
<?php if ($admin): ?>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="goscie.php"><i class="bi bi-journal-text"></i> Wszystkie logowania (admin)</a></li>
						<li><a class="dropdown-item" href="break_ins.php"><i class="bi bi-exclamation-octagon"></i> Włamania &ndash; break_ins (admin)</a></li>
						<li><a class="dropdown-item" href="uzytkownicy.php"><i class="bi bi-people"></i> Użytkownicy portalu (admin)</a></li>
<?php endif; ?>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-activity"></i> Monitoring</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="index1.php"><i class="bi bi-list-check"></i> Moje hosty</a></li>
						<li><a class="dropdown-item" href="dodaj1.php"><i class="bi bi-plus-circle"></i> Dodaj host</a></li>
						<li><a class="dropdown-item" href="kasuj1.php"><i class="bi bi-trash"></i> Usuń host</a></li>
						<?php if (!empty($_SESSION['admin'])): ?>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="index1_admin.php"><i class="bi bi-shield-check"></i> Wszystkie hosty (admin)</a></li>
						<li><a class="dropdown-item" href="staff.php"><i class="bi bi-person-gear"></i> Host z pracownikiem (admin)</a></li>
						<li><a class="dropdown-item" href="pracownicy.php"><i class="bi bi-people"></i> Pracownicy (admin)</a></li>
						<?php endif; ?>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="test.php"><i class="bi bi-plug"></i> Test fsockopen()</a></li>
					</ul>
				</li>
				<li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="offcanvas" data-bs-target="#asystent"><i class="bi bi-robot"></i> Asystent</a></li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-hdd-network"></i> Sieć i serwer</a>
					<ul class="dropdown-menu">
						<li><h6 class="dropdown-header">Serwer</h6></li>
						<li><a class="dropdown-item" href="netstat.php"><i class="bi bi-ethernet"></i> Status usług &ndash; netstat</a></li>
						<li><a class="dropdown-item" href="phpinfo_test.php"><i class="bi bi-info-circle"></i> Informacje o PHP &ndash; phpinfo</a></li>
						<li><a class="dropdown-item" href="whoami.php"><i class="bi bi-person-badge"></i> Użytkownik serwera &ndash; whoami</a></li>
						<li><a class="dropdown-item" href="top.php"><i class="bi bi-cpu"></i> Obciążenie serwera &ndash; top</a></li>
						<li><a class="dropdown-item" href="ls.php"><i class="bi bi-folder2-open"></i> Lista plików &ndash; ls -al</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><h6 class="dropdown-header">DNS i IP</h6></li>
						<li><a class="dropdown-item" href="dns_rekord.php"><i class="bi bi-journal-text"></i> Rekordy DNS domeny</a></li>
						<li><a class="dropdown-item" href="ip_hosty.php"><i class="bi bi-hdd-rack"></i> IP serwera, IP gościa, nazwy domenowe</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><h6 class="dropdown-header">Geolokalizacja</h6></li>
						<li><a class="dropdown-item" href="geolokalizacja.php"><i class="bi bi-pin-map"></i> Geolokalizacja gościa (ipinfo.io)</a></li>
						<li><a class="dropdown-item" href="geo_diagnoza.php"><i class="bi bi-wrench-adjustable"></i> Diagnoza serwisów geolokalizacji</a></li>
						<li><a class="dropdown-item" href="geoip.php"><i class="bi bi-database"></i> Lokalna baza GeoIP (.mmdb)</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-people"></i> Goście portalu</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="wizyty.php"><i class="bi bi-journal-check"></i> Rejestr wizyt gości</a></li>
						<li><a class="dropdown-item" href="blokada_test.php"><i class="bi bi-shield-lock"></i> Wariant a &ndash; blokada spoza Polski</a></li>
						<li><a class="dropdown-item" href="goscie_unikalne.php"><i class="bi bi-fingerprint"></i> Wariant b &ndash; różne IP i liczba wejść</a></li>
						<li><a class="dropdown-item" href="goscie_ranking.php"><i class="bi bi-sort-numeric-down-alt"></i> Wariant c &ndash; ranking wejść</a></li>
					</ul>
				</li>
			</ul>

			<ul class="navbar-nav ms-auto">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
						<?php echo avatar_html((string)($_SESSION['avatar'] ?? ''), 24); ?>
						<?php echo htmlentities((string)($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
					</a>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="dysk.php"><i class="bi bi-cloud"></i> Mój dysk</a></li>
						<li><a class="dropdown-item" href="zmien_avatar.php"><i class="bi bi-image"></i> Zmień avatar</a></li>
						<li><a class="dropdown-item" href="goscie_podglad.php"><i class="bi bi-journal-check"></i> Moje logowania</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Wyloguj</a></li>
					</ul>
				</li>
			</ul>
		</div>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav"  aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

	</div>
</nav>

</div>
</header>
