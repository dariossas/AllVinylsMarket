<?php
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Devi essere loggato']);
    exit();
}

$userId = getCurrentUserId();

// Verifica che l'annuncio_id sia stato inviato
if (!isset($_POST['annuncio_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID annuncio mancante']);
    exit();
}

$annuncioId = intval($_POST['annuncio_id']);

// Verifica se l'annuncio esiste
$stmt = $conn->prepare("SELECT id_annuncio FROM ANNUNCI WHERE id_annuncio = ?");
$stmt->bind_param("i", $annuncioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Annuncio non trovato']);
    exit();
}

// Controlla se è già nei preferiti
$stmt = $conn->prepare("SELECT * FROM LISTA_PREFERITI WHERE id_utente = ? AND id_annuncio = ?");
$stmt->bind_param("ii", $userId, $annuncioId);
$stmt->execute();
$result = $stmt->get_result();

$isFavorite = $result->num_rows > 0;

if ($isFavorite) {
    // Rimuovi dai preferiti
    $stmt = $conn->prepare("DELETE FROM LISTA_PREFERITI WHERE id_utente = ? AND id_annuncio = ?");
    $stmt->bind_param("ii", $userId, $annuncioId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'isFavorite' => false]);
} else {
    // Aggiungi ai preferiti
    $stmt = $conn->prepare("INSERT INTO LISTA_PREFERITI (id_utente, id_annuncio, data_aggiunta) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $userId, $annuncioId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'isFavorite' => true]);
}
exit();
?>