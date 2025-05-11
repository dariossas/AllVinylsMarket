<?php
// messaggi.php - Pagina delle conversazioni per AllVinylsMarket
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!isLoggedIn()) {
    // Reindirizza alla pagina di login
    header("Location: login.php?redirect=messaggi.php");
    exit();
}

$userId = getCurrentUserId();

// Recupera tutte le conversazioni dell'utente
$conversazioni = getConversazioniUtente($conn, $userId);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei messaggi - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .messaggi-container {
            max-width: 1000px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .page-header {
            background-color: #bb1e10;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .conversazioni-list {
            border-top: 1px solid #eee;
        }
        
        .conversazione-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.2s;
        }
        
        .conversazione-item:hover {
            background-color: #f9f9f9;
        }
        
        .conversazione-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #f0f0f0;
            margin-right: 15px;
            overflow: hidden;
        }
        
        .conversazione-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .conversazione-content {
            flex: 1;
            min-width: 0; /* Importante per impedire l'overflow del testo */
        }
        
        .conversazione-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .conversazione-username {
            font-weight: bold;
            font-size: 16px;
        }
        
        .conversazione-time {
            color: #888;
            font-size: 12px;
        }
        
        .conversazione-preview {
            color: #666;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversazione-product {
            display: flex;
            align-items: center;
            margin-top: 5px;
            font-size: 12px;
            color: #888;
        }
        
        .conversazione-product img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            margin-right: 10px;
            border-radius: 4px;
        }
        
        .no-conversazioni {
            padding: 30px;
            text-align: center;
            color: #888;
        }
        
        .no-conversazioni a {
            color: #bb1e10;
            text-decoration: none;
            font-weight: bold;
        }
        
        .no-conversazioni a:hover {
            text-decoration: underline;
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
    
    <div class="messaggi-container">
        <div class="page-header">
            I miei messaggi
        </div>
        
        <div class="conversazioni-list">
            <?php if (empty($conversazioni)): ?>
                <div class="no-conversazioni">
                    <p>Non hai ancora conversazioni attive.</p>
                    <p>Esplora il <a href="index.php">catalogo</a> e contatta i venditori per iniziare a chattare!</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversazioni as $conv): ?>
                    <a href="contatta.php?id=<?php echo $conv['id_annuncio']; ?>&venditore=<?php echo ($conv['id_utente1'] == $userId) ? $conv['id_utente2'] : $conv['id_utente1']; ?>" class="conversazione-item">
                        <div class="conversazione-avatar">
                            <?php if (!empty($conv['immagine_profilo'])): ?>
                                <img src="<?php echo $conv['immagine_profilo']; ?>" alt="Profilo">
                            <?php else: ?>
                                <!-- Placeholder per l'avatar -->
                                <div style="width:100%; height:100%; background-color:#ddd; display:flex; align-items:center; justify-content:center;">
                                    👤
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="conversazione-content">
                            <div class="conversazione-details">
                                <div class="conversazione-username">
                                    <?php echo htmlspecialchars($conv['username']); ?>
                                </div>
                                <div class="conversazione-time">
                                    <?php echo date('d/m/Y H:i', strtotime($conv['ultimo_messaggio_data'])); ?>
                                </div>
                            </div>
                            
                            <div class="conversazione-preview">
                                <?php echo htmlspecialchars($conv['ultimo_messaggio']); ?>
                            </div>
                            
                            <div class="conversazione-product">
                                <img src="<?php echo $conv['immagine_copertina']; ?>" alt="Annuncio" onerror="this.src='https://via.placeholder.com/30x30'">
                                <span><?php echo htmlspecialchars($conv['titolo_annuncio']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
<?php
// Funzione per ottenere tutte le conversazioni dell'utente
function getConversazioniUtente($conn, $userId) {
    $conversazioni = [];
    
    // Seleziona tutte le chat dell'utente con il messaggio più recente
    $sql = "SELECT c.*, 
                u.username, u.immagine_profilo,
                a.titolo AS titolo_annuncio, a.immagine_copertina,
                (SELECT contenuto FROM MESSAGGI WHERE 
                    (id_mittente = ? AND id_destinatario = IF(c.id_utente1 = ?, c.id_utente2, c.id_utente1)) OR 
                    (id_mittente = IF(c.id_utente1 = ?, c.id_utente2, c.id_utente1) AND id_destinatario = ?)
                ORDER BY data_invio DESC LIMIT 1) AS ultimo_messaggio,
                (SELECT data_invio FROM MESSAGGI WHERE 
                    (id_mittente = ? AND id_destinatario = IF(c.id_utente1 = ?, c.id_utente2, c.id_utente1)) OR 
                    (id_mittente = IF(c.id_utente1 = ?, c.id_utente2, c.id_utente1) AND id_destinatario = ?)
                ORDER BY data_invio DESC LIMIT 1) AS ultimo_messaggio_data
            FROM CHATS c
            JOIN ANNUNCI a ON c.id_annuncio = a.id_annuncio
            JOIN UTENTI u ON (u.id_utente = IF(c.id_utente1 = ?, c.id_utente2, c.id_utente1))
            WHERE c.id_utente1 = ? OR c.id_utente2 = ?
            ORDER BY ultimo_messaggio_data DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $conversazioni[] = $row;
    }
    
    return $conversazioni;
}
?>
</body>
</html>
