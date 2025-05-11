<?php
// contatta.php - Gestisce la chat tra utenti per AllVinylsMarket
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!isLoggedIn()) {
    // Reindirizza alla pagina di login con redirect dopo il login
    header("Location: login.php?redirect=contatta.php" . (isset($_GET['id']) ? "&id=" . $_GET['id'] : "") . 
        (isset($_GET['venditore']) ? "&venditore=" . $_GET['venditore'] : ""));
    exit();
}

$userId = getCurrentUserId();

// Verifica se sono stati passati i parametri necessari
if (!isset($_GET['id']) || !isset($_GET['venditore'])) {
    header("Location: index.php");
    exit();
}

$annuncioId = intval($_GET['id']);
$venditoreId = intval($_GET['venditore']);

// Verifica se l'annuncio esiste
$annuncio = getAnnuncioById($conn, $annuncioId);
if (!$annuncio) {
    header("Location: index.php");
    exit();
}

// Verifica se il venditore esiste
$venditore = getVenditoreById($conn, $venditoreId);
if (!$venditore) {
    header("Location: index.php");
    exit();
}

// Se l'utente è il venditore, reindirizza alla pagina dell'annuncio
if ($userId == $venditoreId) {
    header("Location: annuncio.php?id=" . $annuncioId);
    exit();
}

// Ottieni o crea la chat
$chatId = getOrCreateChat($conn, $userId, $venditoreId, $annuncioId);

// Gestisci l'invio di un nuovo messaggio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['messaggio'])) {
    $messaggio = trim($_POST['messaggio']);
    if (!empty($messaggio)) {
        // Inserisci il messaggio nel database
        inviaMessaggio($conn, $userId, $venditoreId, $messaggio);
        
        // Refresh per evitare ritrasmissione del form
        header("Location: contatta.php?id=" . $annuncioId . "&venditore=" . $venditoreId);
        exit();
    }
}

// Ottieni i messaggi tra gli utenti per questo annuncio
$messaggi = getMessaggi($conn, $userId, $venditoreId);
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
            display: flex;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 80vh;
        }
        
        .chat-sidebar {
            width: 300px;
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }
        
        .chat-annuncio {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .chat-annuncio-img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .chat-annuncio-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .chat-annuncio-price {
            color: #bb1e10;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .chat-venditore {
            padding: 15px;
            display: flex;
            align-items: center;
        }
        
        .chat-venditore-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #888;
        }
        
        .chat-venditore-info {
            flex: 1;
        }
        
        .chat-venditore-name {
            font-weight: bold;
        }
        
        .chat-venditore-status {
            font-size: 12px;
            color: #888;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-sent {
            align-self: flex-end;
            background-color: #bb1e10;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message-received {
            align-self: flex-start;
            background-color: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
            display: inline-block;
        }
        
        .message-sent .message-time {
            text-align: right;
        }
        
        .chat-form {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 24px;
            font-size: 14px;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #bb1e10;
        }
        
        .chat-send {
            background-color: #bb1e10;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .chat-send:hover {
            background-color: #a01a0d;
        }
        
        .no-messages {
            text-align: center;
            color: #888;
            margin: auto;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: auto;
            }
            
            .chat-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
            }
            
            .chat-main {
                height: 60vh;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color: #bb1e10; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3>
        </div>
        <div class="search-bar">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Cerca prodotti">
            </form>
        </div>
        <div class="icons">
            <a href="messaggi.php" class="icon" style="font-weight: bold;">📧</a>
            <a href="preferiti.php" class="icon">❤️</a>
            <a href="profilo.php" class="icon">👤</a>
        </div>
    </header>
    
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="chat-annuncio">
                <img src="<?php echo $annuncio['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>" class="chat-annuncio-img" onerror="this.src='https://via.placeholder.com/300'">
                <div class="chat-annuncio-title"><?php echo htmlspecialchars($annuncio['titolo']); ?></div>
                <div class="chat-annuncio-price"><?php echo number_format($annuncio['prezzo'], 2, ',', '.'); ?>€</div>
                <a href="annuncio.php?id=<?php echo $annuncioId; ?>" class="info-button" style="display: block; text-align: center;">Vedi annuncio</a>
            </div>
            
            <div class="chat-venditore">
                <div class="chat-venditore-avatar">
                    <?php if (!empty($venditore['immagine_profilo'])): ?>
                        <img src="<?php echo $venditore['immagine_profilo']; ?>" alt="Profilo" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <div class="chat-venditore-info">
                    <div class="chat-venditore-name"><?php echo htmlspecialchars($venditore['username']); ?></div>
                    <div class="chat-venditore-status">Venditore</div>
                </div>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-header">
                Chat con <?php echo htmlspecialchars($venditore['username']); ?>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messaggi)): ?>
                    <div class="no-messages">
                        Inizia a chattare con <?php echo htmlspecialchars($venditore['username']); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($messaggi as $msg): ?>
                        <div class="message <?php echo ($msg['id_mittente'] == $userId) ? 'message-sent' : 'message-received'; ?>">
                            <?php echo htmlspecialchars($msg['contenuto']); ?>
                            <div class="message-time">
                                <?php echo date('d/m/Y H:i', strtotime($msg['data_invio'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <form class="chat-form" method="POST">
                <input type="text" name="messaggio" class="chat-input" placeholder="Scrivi un messaggio..." autocomplete="off" required>
                <button type="submit" class="chat-send">➤</button>
            </form>
        </div>
    </div>
    
    <script>
        // Scroll automatico alla fine dei messaggi
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    </script>

<?php
/**
 * Ottiene un annuncio dal database in base all'ID
 */
function getAnnuncioById($conn, $id) {
    $id = intval($id);
    $sql = "SELECT a.*, u.username as venditore_username 
            FROM ANNUNCI a 
            JOIN UTENTI u ON a.id_utente = u.id_utente 
            WHERE a.id_annuncio = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Ottiene le informazioni del venditore dal database
 */
function getVenditoreById($conn, $id) {
    $id = intval($id);
    $sql = "SELECT id_utente, username, email, immagine_profilo FROM UTENTI WHERE id_utente = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Verifica se esiste già una chat tra i due utenti per l'annuncio specificato,
 * altrimenti ne crea una nuova
 */
function getOrCreateChat($conn, $userId, $venditoreId, $annuncioId) {
    // Prima cerca se esiste già una chat
    $sql = "SELECT id_chat FROM CHATS WHERE 
            ((id_utente1 = ? AND id_utente2 = ?) OR (id_utente1 = ? AND id_utente2 = ?)) 
            AND id_annuncio = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiii", $userId, $venditoreId, $venditoreId, $userId, $annuncioId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id_chat'];
    }
    
    // Se non esiste, crea una nuova chat
    $sql = "INSERT INTO CHATS (id_utente1, id_utente2, id_annuncio, data_creazione) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $venditoreId, $annuncioId);
    $stmt->execute();
    
    return $conn->insert_id;
}

/**
 * Invia un messaggio da un utente all'altro
 */
function inviaMessaggio($conn, $mittente, $destinatario, $contenuto) {
    $sql = "INSERT INTO MESSAGGI (id_mittente, id_destinatario, contenuto, data_invio) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $mittente, $destinatario, $contenuto);
    return $stmt->execute();
}

/**
 * Ottiene i messaggi tra due utenti
 */
function getMessaggi($conn, $utente1, $utente2) {
    $messaggi = [];
    $sql = "SELECT * FROM MESSAGGI 
            WHERE (id_mittente = ? AND id_destinatario = ?) 
            OR (id_mittente = ? AND id_destinatario = ?) 
            ORDER BY data_invio ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $utente1, $utente2, $utente2, $utente1);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $messaggi[] = $row;
    }
    
    return $messaggi;
}
?>
</body>
</html>
