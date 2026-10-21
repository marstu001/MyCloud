<?php declare(strict_types=1);
$tytul = 'Goście portalu z różnych IP i liczba ich wejść (wariant b)';
require 'gora.php';
?>
<div id="tabela">Ładowanie tabeli&hellip;</div>
<noscript><p class="uwaga">Tabela ładuje się przez JavaScript: <a href="wizyty_tabela.php?tryb=unikalne">wizyty_tabela.php?tryb=unikalne</a>.</p></noscript>
<script>
	fetch('wizyty_tabela.php?tryb=unikalne', { credentials: 'same-origin' })
		.then(function (r) { return r.text(); })
		.then(function (html) { document.getElementById('tabela').innerHTML = html; });
</script>
<?php require 'dol.php'; ?>
