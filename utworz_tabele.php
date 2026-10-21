<?php declare(strict_types=1);
require_once 'lib.php';

header('Content-Type: text/plain; charset=utf-8');

$link = db_polacz();
if (!$link) {
	exit("Błąd połączenia z bazą: " . mysqli_connect_error() . "\nSprawdź nazwę bazy, użytkownika i hasło w pliku db.php.");
}

$tabele = [
	'users' => "CREATE TABLE IF NOT EXISTS users (
		id         INT AUTO_INCREMENT PRIMARY KEY,
		username   VARCHAR(20)  NOT NULL,                              -- login = nazwa katalogu macierzystego
		password   VARCHAR(255) NOT NULL,                              -- skrót hasła
		avatar     VARCHAR(255) NULL DEFAULT NULL,                     -- ścieżka do avatara (NULL = ikona domyślna)
		bledne     TINYINT UNSIGNED NOT NULL DEFAULT 0,                -- kolejne błędne logowania (opcja 6b.iv)
		blokada_do DATETIME NULL DEFAULT NULL,                         -- do kiedy konto jest zablokowane
		worker     TINYINT(1) NOT NULL DEFAULT 0,                      -- zadanie 4 (16e): pracownik, który może być wskazany jako odpowiedzialny za host
		utworzono  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uq_username (username)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

	'goscieportalu' => "CREATE TABLE IF NOT EXISTS goscieportalu (
		id           INT AUTO_INCREMENT PRIMARY KEY,
		ipaddress    VARCHAR(45)  NOT NULL,                            -- IP komputera, z którego próbowano się zalogować
		`datetime`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- data i godzina próby
		login        VARCHAR(50)  NOT NULL DEFAULT '',                 -- login wpisany w formularzu
		wynik        ENUM('OK','BLAD','BLOKADA','WIZYTA') NOT NULL DEFAULT 'WIZYTA', -- OK = poprawne logowanie, BLAD = błędne hasło/login, BLOKADA = blokada konta lub kraju, WIZYTA = wejście zapisane przez rejestr wizyt (zadanie 3)
		przegladarka TEXT NULL,                                        -- nagłówek User-Agent
		ekran        VARCHAR(20)  NULL,                                -- zadanie 3: rozdzielczość ekranu
		okno         VARCHAR(20)  NULL,                                -- rozdzielczość okna przeglądarki
		kolory       TINYINT UNSIGNED NULL,                            -- bity koloru na piksel
		ciasteczka   TINYINT(1)   NULL,
		java         TINYINT(1)   NULL,
		jezyk        VARCHAR(20)  NULL,
		KEY idx_login (login), KEY idx_datetime (`datetime`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

	'break_ins' => "CREATE TABLE IF NOT EXISTS break_ins (
		id         INT AUTO_INCREMENT PRIMARY KEY,
		`datetime` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
		ipaddress  VARCHAR(45) NOT NULL,
		login      VARCHAR(50) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

	'domeny' => "CREATE TABLE IF NOT EXISTS domeny (
		id           INT AUTO_INCREMENT PRIMARY KEY,
		host         VARCHAR(255) NOT NULL,
		port         INT          NOT NULL,
		user_id      INT          NOT NULL,
		typ          VARCHAR(10)  NOT NULL DEFAULT 'www',
		worker_id    INT          NULL DEFAULT NULL,
		errors       INT          NOT NULL DEFAULT 0,
		failure      DATETIME     NULL DEFAULT NULL,
		total_errors INT          NOT NULL DEFAULT 0,
		last_check   DATETIME     NULL DEFAULT NULL,
		status       TINYINT(1)   NULL DEFAULT NULL,
		last_error   VARCHAR(255) NULL DEFAULT NULL,
		KEY idx_user (user_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

	'udostepnienia' => "CREATE TABLE IF NOT EXISTS udostepnienia (
		id            INT AUTO_INCREMENT PRIMARY KEY,
		wlasciciel_id INT NOT NULL,
		odbiorca_id   INT NOT NULL,
		sciezka       VARCHAR(300) NOT NULL,                           -- ścieżka względem katalogu macierzystego właściciela, np. zdjecia/2026/foto.jpg
		utworzono     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uq_udostepnienie (wlasciciel_id, odbiorca_id, sciezka),
		CONSTRAINT fk_ud_wlasciciel FOREIGN KEY (wlasciciel_id) REFERENCES users(id) ON DELETE CASCADE,
		CONSTRAINT fk_ud_odbiorca   FOREIGN KEY (odbiorca_id)   REFERENCES users(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tabele as $nazwa => $sql) {
	echo mysqli_query($link, $sql) ? "OK -- tabela $nazwa istnieje.\n" : "BŁĄD przy tabeli $nazwa: " . mysqli_error($link) . "\n";
}

$zmiany = [
	"ALTER TABLE users ADD COLUMN IF NOT EXISTS worker TINYINT(1) NOT NULL DEFAULT 0",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS ekran VARCHAR(20) NULL",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS okno VARCHAR(20) NULL",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS kolory TINYINT UNSIGNED NULL",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS ciasteczka TINYINT(1) NULL",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS java TINYINT(1) NULL",
	"ALTER TABLE goscieportalu ADD COLUMN IF NOT EXISTS jezyk VARCHAR(20) NULL",
	"ALTER TABLE goscieportalu MODIFY wynik ENUM('OK','BLAD','BLOKADA','WIZYTA') NOT NULL DEFAULT 'WIZYTA'",
	"ALTER TABLE goscieportalu MODIFY login VARCHAR(50) NOT NULL DEFAULT ''",
	"ALTER TABLE goscieportalu MODIFY przegladarka TEXT NULL",
];
foreach ($zmiany as $z) {
	if (!mysqli_query($link, $z)) { echo "BŁĄD: " . mysqli_error($link) . "\n"; }
}
echo "OK -- struktura tabel uzupełniona o kolumny zadań 3 i 4.\n";

$q = mysqli_prepare($link, 'SELECT id FROM users WHERE username = ?');
$a = ADMIN_LOGIN;
mysqli_stmt_bind_param($q, 's', $a);
mysqli_stmt_execute($q);
mysqli_stmt_store_result($q);
if (mysqli_stmt_num_rows($q) === 0) {
	$haslo = password_hash('admin', PASSWORD_DEFAULT);
	$ins = mysqli_prepare($link, 'INSERT INTO users (username, password) VALUES (?, ?)');
	mysqli_stmt_bind_param($ins, 'ss', $a, $haslo);
	echo mysqli_stmt_execute($ins) ? "OK -- konto administratora admin / admin utworzone.\n" : "BŁĄD przy koncie admin: " . mysqli_error($link) . "\n";
} else {
	echo "OK -- konto administratora już istnieje.\n";
}
mysqli_stmt_close($q);
mysqli_query($link, "UPDATE users SET worker = 1 WHERE username = 'admin'");

$r = mysqli_query($link, 'SELECT COUNT(*) FROM domeny');
if ($r && (int)mysqli_fetch_row($r)[0] === 0) {
	$admin_id = (int)mysqli_fetch_row(mysqli_query($link, "SELECT id FROM users WHERE username = 'admin'"))[0];
	$self = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$przyklady = [[$self, 80, 'www'], [$self, 443, 'www'], [$self, 3306, 'db'], [$self, 25, 'mail'], [$self, 22, 'switch'], ['facebook.com', 80, 'www'], ['wp.ppl', 80, 'router']];
	$st = mysqli_prepare($link, 'INSERT INTO domeny (host, port, user_id, typ) VALUES (?, ?, ?, ?)');
	foreach ($przyklady as [$h, $p, $t]) {
		mysqli_stmt_bind_param($st, 'siis', $h, $p, $admin_id, $t);
		mysqli_stmt_execute($st);
	}
	echo "OK -- dodano " . count($przyklady) . " przykładowych hostów (właściciel: admin).\n";
}

if (dysk_upewnij_korzen() && dysk_katalog_domowy(ADMIN_LOGIN) !== null) {
	echo "OK -- katalog dysk/ (z blokadą dostępu przez .htaccess) i katalog macierzysty admina istnieją.\n";
} else {
	echo "BŁĄD: nie udało się utworzyć katalogu dysk/ -- sprawdź prawa zapisu w katalogu zadania.\n";
}

mysqli_close($link);
echo "\nGotowe. Teraz usuń plik utworz_tabele.php z serwera.";
