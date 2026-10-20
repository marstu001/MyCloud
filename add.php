<?php declare(strict_types=1);
require_once 'lib.php';
require_once 'avatar.php';

$pass_raw  = (string)($_POST['pass'] ?? '');
$pass2_raw = (string)($_POST['pass2'] ?? '');
$user      = (string)($_POST['user'] ?? '');

$wroc = function (string $komunikat) use ($user): void {
	header('Location: rejestruj.php?blad=' . urlencode($komunikat) . '&login=' . urlencode($user));
	exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $wroc('Wypełnij formularz rejestracji.'); }
if ($user === '' || $pass_raw === '')
{
	$wroc('Podaj login i hasło.');
}
if (!login_poprawny($user))
{
	$wroc('Login może mieć do 20 znaków: litery bez polskich znaków, cyfry, _ oraz - (to będzie nazwa Twojego katalogu).');
}
if ($pass_raw !== $pass2_raw)
{
	$wroc('Hasła nie są takie same. Wpisz je jeszcze raz.');
}

$link = db_polacz();
if (!$link)
{
	$wroc('Błąd połączenia z bazą danych.');
}
avatar_upewnij_kolumne($link);

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

[$avatar, $blad_avatara] = avatar_zapisz($_FILES['avatar'] ?? [], $user);
if ($blad_avatara !== '')
{
	mysqli_close($link);
	$wroc($blad_avatara);
}

$hash = password_hash($pass_raw, PASSWORD_DEFAULT);
$ins  = mysqli_prepare($link, 'INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)');
mysqli_stmt_bind_param($ins, 'sss', $user, $hash, $avatar);
$ok    = mysqli_stmt_execute($ins);
$blad  = mysqli_stmt_error($ins);
$id    = mysqli_stmt_insert_id($ins);
mysqli_stmt_close($ins);

if ($ok)
{
	if (dysk_katalog_domowy($user) === null)
	{
		mysqli_query($link, 'DELETE FROM users WHERE id = ' . (int)$id);
		mysqli_close($link);
		avatar_usun_plik($avatar);
		$wroc('Nie udało się utworzyć katalogu macierzystego na serwerze (prawa zapisu w katalogu zadania?).');
	}
	mysqli_close($link);
	header('Location: index3.php?info=' . urlencode('Rejestracja zakończona sukcesem! Utworzono Twój katalog macierzysty. Możesz się teraz zalogować.'));
	exit();
}
mysqli_close($link);
avatar_usun_plik($avatar);
$wroc('Błąd podczas rejestracji: ' . $blad);
