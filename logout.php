<?php
 session_start();
 session_unset();                                                               // wyczyszczenie wszystkich zmiennych sesyjnych
 session_destroy();                                                             // usunięcie całej sesji
 header('Location: index3.php');                                                // powrót do formularza logowania
 exit();
?>
