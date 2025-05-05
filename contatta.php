<?php
// contatta.php - Sistema di chat per AllVinylsMarket
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!isLoggedIn()) {
    // Reindirizza alla pagina di login con un parametro per tornare qui dopo il login
    header("Location: login.php?redirect=contatta.php&" . http_build_query($_GET));
    exit();
}

// Recupera i parametri dall'URL
$annuncioId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$venditoreId = isset($_GET['venditore']) ? intval($_GET['venditore']) : 0;
$utenteId = getCurrentUserId();

// Verifica che l'annuncio e il venditore esistano
$annuncio = getAnnuncioById($conn, $annuncioId);
$venditore = getUtenteById($conn, $venditoreId);

if (!$annuncio || !$venditore) {
    // Se l'annuncio o il venditore non esistono, reindirizza alla home
    header("Location: index.php");
    exit();
}

// Verifica che l'utente non stia cercando di chattare con se stesso
if ($utenteId == $venditoreId) {
    // Redirect alla pagina dell'annuncio con un messaggio di errore
    header("Location: annuncio.php?id=$annuncioId&error=proprio_annuncio");
    exit();
}

// Cerca o crea una chat esistente
$chatId = getChatId($conn, $utenteId, $venditoreId, $annuncioId);

// Gestisci l'invio di un nuovo messaggio
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['messaggio']) && !empty(trim($_POST['messaggio']))) {
    $messaggio = trim($_POST['messaggio']);
    
    // Inserisci il messaggio nel database
    if (inviaMessaggio($conn, $utenteId, $venditoreId, $messaggio)) {
        // Refresh della pagina per mostrare il nuovo messaggio
        header("Location: contatta.php?id=$annuncioId&venditore=$venditoreId");
        exit();
    }
}

// Recupera i messaggi della chat
$messaggi = getMessaggiChat($conn, $utenteId, $venditoreId);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?php echo htmlspecialchars($venditore['username']); ?> - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .chat-container {
            max-width: 1000px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .chat-header {
            background-color: #bb1e10;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header-left {
            display: flex;
            align-items: center;
        }
        
        .chat-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            background-color: #f5f5f5;
        }
        
        .annuncio-preview {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .annuncio-preview img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .annuncio-info h4 {
            margin: 0;
            font-size: 16px;
        }
        
        .annuncio-info .price {
            color: #bb1e10;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f5f5f5;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 80%;
        }
        
        .message-sent {
            margin-left: auto;
            background-color: #dcf8c6;
            border-radius: 8px 0 8px 8px;
            padding: 10px;
        }
        
        .message-received {
            margin-right: auto;
            background-color: white;
            border-radius: 0 8px 8px 8px;
            padding: 10px;
            border: 1px solid #eee;
        }
        
        .message-meta {
            font-size: 12px;
            color: #888;
            text-align: right;
            margin-top: 5px;
        }
        
        .chat-input {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
        }
        
        .chat-input textarea {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            resize: none;
            height: 60px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .chat-input button {
            background-color: #bb1e10;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0 15px;
            margin-left: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .no-messages {
            text-align: center;
            color: #888;
            padding: 20px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .back-link:before {
            content: "←";
            margin-right: 5px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color:red; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3>
        </div>
        <div class="search-bar">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Cerca prodotti">
            </form>
        </div>
        <div class="icons">
            <a href="messaggi.php" class="icon">📧</a>
            <a href="preferiti.php" class="icon">❤️</a>
            <a href="profilo.php" class="icon">👤</a>
            <a href="logout.php" class="login-button">ESCI</a>
        </div>
    </header>
    
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-left">
                <a href="annuncio.php?id=<?php echo $annuncioId; ?>" class="back-link">Torna all'annuncio</a>
            </div>
            <div class="chat-header-right">
                Chat con <?php echo htmlspecialchars($venditore['username']); ?>
            </div>
        </div>
        
        <div class="annuncio-preview">
            <img src="<?php echo $annuncio['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>" onerror="this.src='https://via.placeholder.com/60x60'">
            <div class="annuncio-info">
                <h4><?php echo htmlspecialchars($annuncio['titolo']); ?></h4>
                <p class="price">€<?php echo number_format($annuncio['prezzo'], 2, ',', '.'); ?></p>
            </div>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <?php if (empty($messaggi)): ?>
                <div class="no-messages">
                    Inizia la conversazione con <?php echo htmlspecialchars($venditore['username']); ?>.
                </div>
            <?php else: ?>
                <?php foreach ($messaggi as $messaggio): ?>
                    <?php $isSent = $messaggio['id_mittente'] == $utenteId; ?>
                    <div class="message <?php echo $isSent ? 'message-sent' : 'message-received'; ?>">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($messaggio['contenuto'])); ?>
                        </div>
                        <div class="message-meta">
                            <?php echo date('d/m/Y H:i', strtotime($messaggio['data_invio'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form class="chat-input" method="POST" action="">
            <textarea name="messaggio" placeholder="Scrivi un messaggio..." required></textarea>
            <button type="submit">Invia</button>
        </form>
    </div>
    
    <script>
        // Auto-scroll to the bottom of the chat on page load
        document.addEventListener('DOMContentLoaded', function() {
            var chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    </script>
    
    <?php
    // Funzioni per la gestione della chat
    
    // Ottiene i dati di un utente dal database
    function getUtenteById($conn, $id) {
        $id = intval($id);
        $sql = "SELECT id_utente, username, immagine_profilo FROM UTENTI WHERE id_utente = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Ottiene i dati di un annuncio dal database
    function getAnnuncioById($conn, $id) {
        $id = intval($id);
        $sql = "SELECT * FROM ANNUNCI WHERE id_annuncio = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Ottiene o crea una chat tra due utenti per un annuncio specifico
    function getChatId($conn, $utente1, $utente2, $annuncioId) {
        // Cerca una chat esistente
        $sql = "SELECT id_chat FROM CHATS 
                WHERE (id_utente1 = ? AND id_utente2 = ? AND id_annuncio = ?) 
                OR (id_utente1 = ? AND id_utente2 = ? AND id_annuncio = ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiii", $utente1, $utente2, $annuncioId, $utente2, $utente1, $annuncioId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id_chat'];
        }
        
        // Crea una nuova chat
        $sql = "INSERT INTO CHATS (id_utente1, id_utente2, id_annuncio) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $utente1, $utente2, $annuncioId);
        
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        
        return null;
    }
    
    // Inserisce un nuovo messaggio nel database
    function inviaMessaggio($conn, $mittente, $destinatario, $contenuto) {
        $sql = "INSERT INTO MESSAGGI (id_mittente, id_destinatario, contenuto) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $mittente, $destinatario, $contenuto);
        
        return $stmt->execute();
    }
    
    // Recupera i messaggi tra due utenti
    function getMessaggiChat($conn, $utente1, $utente2) {
        $sql = "SELECT * FROM MESSAGGI 
                WHERE (id_mittente = ? AND id_destinatario = ?) 
                OR (id_mittente = ? AND id_destinatario = ?)
                ORDER BY data_invio ASC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $utente1, $utente2, $utente2, $utente1);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messaggi = [];
        while ($row = $result->fetch_assoc()) {
            $messaggi[] = $row;
        }
        
        return $messaggi;
    }
    ?>
</body>
</html>