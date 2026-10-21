function myLoadHeader() 
{ 
	$(document).ready(function()
	{ 
		$('#myHeader').load("header.php"); 
	}); 
}


var DZWIEK_PLIKI = { '1': 'dzwiek1.mp3', '2': 'dzwiek2.mp3', '3': 'dzwiek3.mp3' };

function dzwiekStan()
{
	try { var v = sessionStorage.getItem('dzwiek'); return (v === null || !/^[0-3]$/.test(v)) ? '1' : v; } catch (e) { return '1'; }
}
function dzwiekOstatni()
{
	try { var v = sessionStorage.getItem('dzwiek_ostatni'); return (v === null || !/^[1-3]$/.test(v)) ? '1' : v; } catch (e) { return '1'; }
}
function dzwiekUstaw(v)
{
	try { sessionStorage.setItem('dzwiek', v); if (v !== '0') { sessionStorage.setItem('dzwiek_ostatni', v); } } catch (e) {}
	dzwiekPokaz();
}
function dzwiekPokaz()
{
	var v = dzwiekStan();
	$('#dzwiek-wybor').val(v);
	$('#ikona-dzwiek-on').toggle(v !== '0');
	$('#ikona-dzwiek-off').toggle(v === '0');
	$('#dzwiek-status').text(v === '0' ? 'wyłączony' : 'włączony (' + v + ')');
}
function dzwiekGraj(pokazBlokade)
{
	var v = dzwiekStan();
	if (v === '0') { return; }
	var a = new Audio(DZWIEK_PLIKI[v]);
	var p = a.play();
	if (p && p.catch) { p.then(function () { $('#dzwiek-odblokuj').hide(); }).catch(function () { if (pokazBlokade !== false) { $('#dzwiek-odblokuj').show(); } }); }
}
$(function ()
{
	dzwiekPokaz();
	$(document).on('click', '#dzwiek-przycisk', function () { dzwiekUstaw(dzwiekStan() === '0' ? dzwiekOstatni() : '0'); });
	$(document).on('change', '#dzwiek-wybor', function () { dzwiekUstaw(this.value); dzwiekGraj(false); });
	$(document).on('click', '#dzwiek-odblokuj', function () { $(this).hide(); dzwiekGraj(); });
});

function startMonitor(url, idKontenera, ms)
{
	function odswiez()
	{
		$.ajax({ url: url, cache: false, dataType: 'html' })
			.done(function (html)
			{
				$('#' + idKontenera).html(html);
				var d = $('#dane-stanu');
				$('#czas-odswiezenia').text(d.data('czas'));
				if (parseInt(d.data('max-awaria'), 10) > parseInt(d.data('prog-dzwieku'), 10)) { dzwiekGraj(); }
			})
			.fail(function () { $('#czas-odswiezenia').text('błąd odświeżania'); });
	}
	odswiez();
	setInterval(odswiez, ms);
}
