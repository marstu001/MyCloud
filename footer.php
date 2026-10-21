<?php declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
?>
<?php if (!empty($dzwiek_panel)): ?>
	<footer class="page-footer mt-auto bg-light border-primary">
		<div class="container-fluid d-flex flex-wrap align-items-center gap-2">
			<span id="dzwiek-panel" class="ms-auto d-flex align-items-center gap-2">
				<button type="button" id="dzwiek-przycisk" class="btn btn-sm btn-outline-secondary d-flex align-items-center p-1" title="Włącz / wyłącz dźwięk alarmu" aria-label="Włącz lub wyłącz dźwięk alarmu">
					<svg id="ikona-dzwiek-on" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>
					<svg id="ikona-dzwiek-off" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M11 5L6 9H3v6h3l5 4z"/><path d="M22 9l-6 6M16 9l6 6"/></svg>
				</button>
				<select id="dzwiek-wybor" class="form-select form-select-sm w-auto" aria-label="Rodzaj dźwięku alarmu">
					<option value="0">Dźwięk wyłączony</option>
					<option value="1">Dźwięk 1 &ndash; piknięcia</option>
					<option value="2">Dźwięk 2 &ndash; syrena</option>
					<option value="3">Dźwięk 3 &ndash; dwa tony</option>
				</select>
				<span id="dzwiek-status" class="small text-muted"></span>
				<button type="button" id="dzwiek-odblokuj" class="btn btn-sm btn-warning" style="display:none">Kliknij, aby odblokować dźwięk</button>
			</span>
		</div>
	</footer>
<?php endif; ?>

<?php if (isset($_SESSION['loggedin'])): ?>
	<div class="offcanvas offcanvas-end" tabindex="-1" id="asystent" aria-labelledby="asystent-tytul">
		<div class="offcanvas-header">
			<h5 class="offcanvas-title" id="asystent-tytul"><i class="bi bi-robot"></i> Asystent monitoringu</h5>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Zamknij"></button>
		</div>
		<div class="offcanvas-body d-flex flex-column">
			<div class="text-center mb-2">
				<svg id="awatar-asystenta" width="150" height="150" viewBox="0 0 120 120" role="img" aria-label="Animowany awatar asystenta">
					<line x1="60" y1="8" x2="60" y2="22" stroke="#2c3e50" stroke-width="3" stroke-linecap="round"/>
					<circle class="aw-antena" cx="60" cy="8" r="5" fill="#dc3545"/>
					<rect x="20" y="22" width="80" height="66" rx="18" fill="#e9eef5" stroke="#2c3e50" stroke-width="3"/>
					<rect x="12" y="46" width="8" height="18" rx="4" fill="#2c3e50"/><rect x="100" y="46" width="8" height="18" rx="4" fill="#2c3e50"/>
					<g class="aw-oko"><ellipse cx="44" cy="52" rx="8" ry="9" fill="#2c3e50"/><circle cx="46" cy="49" r="2.5" fill="#fff"/></g>
					<g class="aw-oko"><ellipse cx="76" cy="52" rx="8" ry="9" fill="#2c3e50"/><circle cx="78" cy="49" r="2.5" fill="#fff"/></g>
					<ellipse class="aw-usta" cx="60" cy="72" rx="12" ry="3" fill="#2c3e50"/>
					<rect x="38" y="90" width="44" height="20" rx="8" fill="#2c3e50"/>
					<circle class="aw-led" cx="50" cy="100" r="3" fill="#198754"/><circle class="aw-led aw-led2" cx="60" cy="100" r="3" fill="#ffc107"/><circle class="aw-led aw-led3" cx="70" cy="100" r="3" fill="#0d6efd"/>
				</svg>
			</div>
			<div id="asystent-rozmowa" class="flex-grow-1 mb-2" style="overflow-y:auto; min-height:120px;">
				<div class="alert alert-secondary py-2 mb-2">Cześć! Jestem asystentem monitoringu. Zapytaj, co działa, a co nie.</div>
			</div>
			<div class="d-flex flex-wrap gap-2 mb-2">
				<button class="btn btn-sm btn-danger" data-pytanie="nie"><i class="bi bi-x-octagon"></i> Co nie działa?</button>
				<button class="btn btn-sm btn-success" data-pytanie="dziala"><i class="bi bi-check-circle"></i> Co działa?</button>
				<button class="btn btn-sm btn-outline-primary" data-pytanie="wszystko"><i class="bi bi-card-list"></i> Podsumowanie</button>
			</div>
			<form id="asystent-form" class="input-group mb-2">
				<input type="text" id="asystent-pytanie" class="form-control" placeholder="Np. które serwery nie działają?" autocomplete="off">
				<button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
			</form>
			<div class="form-check form-switch small">
				<input class="form-check-input" type="checkbox" id="asystent-glos">
				<label class="form-check-label" for="asystent-glos">Czytaj odpowiedzi na głos</label>
			</div>
		</div>
	</div>
	<script src="asystent.js"></script>
<?php endif; ?>
