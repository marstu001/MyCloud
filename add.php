<?php declare(strict_types=1);  /* Ta linia musi byc pierwsza */
require_once 'lib.php';
require_once 'avatar.php';                                                     // zapis avatara

$pass_raw  = (string)($_POST['pass'] ?? '');
$pass2_raw = (string)($_POST['pass2'] ?? '');
$user      = (string)($_POST['user'] ?? '');

// wspólne przekierowanie z błędem -- wpisany login wraca do formularza
$wroc = function (string $komunikat) use ($user): void {
	header('Location: rejestruj.php?blad=' . urlencode($komunikat) . '&login=' . urlencode($user));
	exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $wroc('Wypełnij formularz rejestracji.'); }
if ($user === '' || $pass_raw === '')
{
	$wroc('Podaj login i hasło.');
}
if (!login_poprawny($user))                                                    // punkt 6c.i: login staje się nazwą katalogu na serwerze
{
	$wroc('Login może mieć do 20 znaków: litery bez polskich znaków, cyfry, _ oraz - (to będzie nazwa Twojego katalogu).');
}
if ($pass_raw !== $pass2_raw)                                                  // hasło wpisane drugi raz musi być identyczne
{
	$wroc('Hasła nie są takie same. Wpisz je jeszcze raz.');
}

$link = db_polacz();                                                           // połączenie z BD
if (!$link)
{
	$wroc('Błąd połączenia z bazą danych.');
}
avatar_upewnij_kolumne($link);

// sprawdzamy najpierw, czy taki login już istnieje w bazie -- unikamy duplikatów (porównanie bez względu na wielkość liter)
$sprawdz = mysqli_prepare($link, 'SELECT id FROM users WHERE username = ?');
mysqli_stmt_bind_param($sprawdz, 's', $user);
mysqli_stmt_execute($sprawdz);
mysqli_stmt_store_result($sprawdz);
$istnieje = mysqli_stmt_num_rows($sprawdz) > 0;
mysqli_stmt_close($sprawdz);
if ($istnieje)
{
	mysqli_close($link);
	$wroc('Użytkownik o takim loginie już istnieje. Wybierz inny login.');
}

// opcjonalny avatar -- bez pliku zostaje NULL (ikona domyślna), a użytkownik może go ustawić później
[$avatar, $blad_avatara] = avatar_zapisz($_FILES['avatar'] ?? [], $user);
if ($blad_avatara !== '')
{
	mysqli_close($link);
	$wroc($blad_avatara);
}

$hash = password_hash($pass_raw, PASSWORD_DEFAULT);                            // hasło trzymane w tabeli users jako skrót
$ins  = mysqli_prepare($link, 'INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($ins, 'sss', $user, $hash, $avatar);
$ok    = mysqli_stmt_execute($ins);
$blad  = mysqli_stmt_error($ins);
$id    = mysqli_stmt_insert_id($ins);
mysqli_stmt_close($ins);

if ($ok)
{
	// punkt 6c: po pomyślnej rejestracji powstaje katalog macierzysty o nazwie takiej, jak login
	if (dysk_katalog_domowy($user) === null)
	{
		mysqli_query($link, 'DELETE FROM users WHERE id = ' . (int)$id);        // bez katalogu konto nie ma sensu
		mysqli_close($link);
		avatar_usun_plik($avatar);
		$wroc('Nie udało się utworzyć katalogu macierzystego na serwerze (prawa zapisu w katalogu zadania?).');
	}
	mysqli_close($link);
	header('Location: index3.php?info=' . urlencode('Rejestracja zakończona sukcesem! Utworzono Twój katalog macierzysty. Możesz się teraz zalogować.'));
	exit();
}
mysqli_close($link);
avatar_usun_plik($avatar);                                                     // rejestracja się nie udała -- nie zostawiamy osieroconego pliku
$wroc('Błąd podczas rejestracji: ' . $blad);
