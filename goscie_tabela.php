<?php declare(strict_types=1);
// Wspólna tabela prób logowania (używana przez goscie_podglad.php i goscie.php). Wiersze: [data, IP, wynik, przeglądarka, (login)].
function nazwa_przegladarki(string $ua): string
{
	foreach (['Edg/' => 'Edge', 'OPR/' => 'Opera', 'Firefox/' => 'Firefox', 'Chrome/' => 'Chrome', 'Safari/' => 'Safari'] as $znacznik => $nazwa) {
		if (strpos($ua, $znacznik) !== false) { return $nazwa; }
	}
	return $ua !== '' ? mb_substr($ua, 0, 30) : '?';
}
function tabela_logowan(array $wiersze, bool $z_loginem): void
{
	if (!$wiersze) { echo '<p class="text-muted">Brak zapisanych prób logowania.</p>'; return; }
	$etykiety = ['OK' => ['success', 'udane'], 'BLAD' => ['danger', 'błędne'], 'BLOKADA' => ['warning text-dark', 'zablokowane (konto w blokadzie)']];
	echo '<div class="table-responsive"><table class="table table-sm table-striped align-middle"><thead><tr><th>Data i godzina</th>' . ($z_loginem ? '<th>Login</th>' : '') . '<th>Adres IP</th><th>Wynik</th><th>Przeglądarka</th></tr></thead><tbody>';
	foreach ($wiersze as $w) {
		[$kolor, $opis] = $etykiety[$w[2]] ?? ['secondary', $w[2]];
		echo '<tr><td>' . e($w[0]) . '</td>' . ($z_loginem ? '<td>' . e($w[4]) . '</td>' : '') . '<td>' . e($w[1]) . '</td><td><span class="badge bg-' . $kolor . '">' . e($opis) . '</span></td><td>' . e(nazwa_przegladarki((string)$w[3])) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}
