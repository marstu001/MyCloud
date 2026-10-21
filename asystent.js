(function () {
	var $roz = $('#asystent-rozmowa'), $aw = $('#awatar-asystenta'), trwa = false;

	function dodaj(klasa, tekst) { var $b = $('<div class="alert py-2 mb-2"></div>').addClass(klasa); $roz.append($b); $roz.scrollTop($roz[0].scrollHeight); return $b; }

	function mow(tekst)
	{
		var $b = dodaj('alert-secondary odp', ''), i = 0;
		$aw.addClass('mowi');
		if ($('#asystent-glos').is(':checked') && window.speechSynthesis) {
			var u = new SpeechSynthesisUtterance(tekst.replace(/•/g, '')); u.lang = 'pl-PL'; speechSynthesis.cancel(); speechSynthesis.speak(u);
		}
		var t = setInterval(function () {
			i += 3; $b.text(tekst.substring(0, i)); $roz.scrollTop($roz[0].scrollHeight);
			if (i >= tekst.length) { clearInterval(t); $aw.removeClass('mowi'); trwa = false; }
		}, 20);
	}

	function pytaj(q, etykieta)
	{
		if (trwa) { return; }
		trwa = true;
		dodaj('alert-primary text-end', etykieta || q).text(etykieta || q);
		$.getJSON('asystent.php', { q: q }).done(function (d) { mow(d.tekst); }).fail(function () { mow('Nie udało się pobrać stanu hostów.'); });
	}

	$(document).on('click', '#asystent [data-pytanie]', function () { pytaj($(this).data('pytanie'), $(this).text().trim()); });
	$('#asystent-form').on('submit', function (e) { e.preventDefault(); var q = $('#asystent-pytanie').val().trim(); if (q) { $('#asystent-pytanie').val(''); pytaj(q); } });
})();
