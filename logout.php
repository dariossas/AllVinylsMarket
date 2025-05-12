<?php
// logout.php - Gestisce il logout dell'utente

// Avvia la sessione se non è già attiva
session_start();

// Distruggi tutte le variabili di sessione
$_SESSION = array();

// Se si sta usando un cookie di sessione, eliminalo
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Distruggi la sessione
session_destroy();

// Reindirizza alla home page
header("Location: index.php");
exit;
?>