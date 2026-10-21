<?php declare(strict_types=1);
$tytul = 'Test fsockopen()';
require 'gora.php';
$host = 'fb.com';
$port = 80;
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
	echo '<div class="alert alert-success">Połączenie z ' . htmlspecialchars("$host:$port") . ' &ndash; <strong>Ok</strong></div>';
	fclose($fp);
} else {
	echo '<div class="alert alert-danger">Połączenie z ' . htmlspecialchars("$host:$port") . ' nieudane: ' . (int)$errno . ' ' . htmlspecialchars((string)$errstr) . '</div>';
}
require 'dol.php';
