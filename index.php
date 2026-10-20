<?php declare(strict_types=1);
// Strona startowa zadania: po zalogowaniu - dysk użytkownika, bez zalogowania - formularz logowania
session_start();
header('Location: ' . (isset($_SESSION['loggedin']) ? 'dysk.php' : 'index3.php'));
exit();
