<?php
// Include database connection
require_once 'db_connect.php';

// Start session if not started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['id_utente']);
}

// Get current user ID
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['id_utente'] : null;
}

// Function to get user's favorite listings
function getFavoriteListings($conn, $userId) {
    $favorites = array();
    
    if ($userId) {
        $sql = "SELECT a.*, lp.data_aggiunta 
                FROM ANNUNCI a 
                JOIN LISTA_PREFERITI lp ON a.id_annuncio = lp.id_annuncio 
                WHERE lp.id_utente = ? 
                ORDER BY lp.data_aggiunta DESC 
                LIMIT 5";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
        
        $stmt->close();
    }
    
    return $favorites;
}

// Function to get recent listings for exploration
function getRecentListings($conn) {
    $listings = array();
    
    $sql = "SELECT * FROM ANNUNCI ORDER BY data_caricamento DESC LIMIT 7";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $listings[] = $row;
        }
    }
    
    return $listings;
}

?>
