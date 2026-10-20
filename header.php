<?php declare(strict_types=1);  /* Ta linia musi być pierwsza */
session_start();                                                               // header.php jest ładowany osobnym żądaniem AJAX, więc sam musi otworzyć sesję
require_once 'avatar.php';                                                     // wyświetlanie avatara w pasku
// Pasek nawigacji pojawia się dopiero po zalogowaniu
if (!isset($_SESSION['loggedin'])) { return; }
$admin = !empty($_SESSION['admin']);
?>
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">

		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav">
				<li class="nav-item"><a class="nav-link" href="dysk.php"><i class="bi bi-cloud"></i> MyCloud &ndash; mój dysk</a></li>
				<li class="nav-item"><a class="nav-link" href="select.php"><i class="bi bi-cloud-upload"></i> Prześlij plik</a></li>
				<li class="nav-item"><a class="nav-link" href="udostepnione.php"><i class="bi bi-share"></i> Udostępnione</a></li>
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
