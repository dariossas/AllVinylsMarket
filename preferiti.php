<?php
// Include functions file
require_once 'functions.php';

// Verifica se l'utente è loggato
if (!isLoggedIn()) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$userId = getCurrentUserId();

// Ottieni la lista dei preferiti dell'utente
$favorites = getFavoriteListings($conn, $userId);

// Numero totale di preferiti
$totalFavorites = count($favorites);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I miei preferiti - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .preferiti-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .preferiti-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .preferiti-title {
            font-size: 24px;
            color: #333;
        }
        
        .preferiti-count {
            background-color: #bb1e10;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .empty-message {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .empty-message p {
            margin: 10px 0;
            color: #666;
        }
        
        .empty-message a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #bb1e10;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .empty-message a:hover {
            background-color: #a01a0e;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color:#bb1e10; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3>
        </div>
        <div class="search-bar">
            <form action="search.php" method="GET">
                <b><input type="text" name="q" placeholder="🔍 Cerca prodotti"></b>
            </form>
        </div>
        <div class="icons">
            <a href="messaggi.php" class="icon">📧</a>
            <a href="preferiti.php" class="icon" style="color: #bb1e10;">❤️</a>
            <a href="profili.php" class="icon">👤</a>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>
    
    <div class="preferiti-container">
        <div class="preferiti-header">
            <h2 class="preferiti-title">I miei preferiti</h2>
            <span class="preferiti-count"><?php echo $totalFavorites; ?> articoli</span>
        </div>
        
        <?php if ($totalFavorites > 0): ?>
            <div class="esplora-grid">
                <?php foreach ($favorites as $vinyl): ?>
                    <div class="vinyl-item">
                        <a href="annuncio.php?id=<?php echo $vinyl['id_annuncio']; ?>">
                            <img src="<?php echo $vinyl['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($vinyl['titolo']); ?>" onerror="this.src='https://via.placeholder.com/150x150'"/>
                            <h4><?php echo htmlspecialchars($vinyl['titolo']); ?></h4>
                            <p>Di: <?php echo htmlspecialchars($vinyl['artista']); ?></p>
                            <p>Vinile, <?php echo htmlspecialchars($vinyl['formato']); ?></p>
                            <p class="price">€<?php echo number_format($vinyl['prezzo'], 2, ',', '.'); ?></p>
                            <button class="remove-favorite-btn" title="Rimuovi dai preferiti" onclick="toggleFavorite(<?php echo $vinyl['id_annuncio']; ?>, this); event.preventDefault();">❌</button>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <h3>Non hai ancora aggiunto preferiti!</h3>
                <p>Quando trovi articoli che ti piacciono, aggiungili ai preferiti cliccando sul simbolo del cuore.</p>
                <p>I tuoi preferiti saranno sempre disponibili qui per consultarli in seguito.</p>
                <a href="index.php">Inizia ad esplorare</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleFavorite(annuncioId, button) {
            // AJAX request to remove from favorites
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'annuncio_id=' + annuncioId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rimuovi l'elemento dal DOM con un effetto di transizione
                    const vinylItem = button.closest('.vinyl-item');
                    vinylItem.style.opacity = '0';
                    setTimeout(() => {
                        vinylItem.remove();
                        
                        // Aggiorna il contatore
                        const counter = document.querySelector('.preferiti-count');
                        const currentCount = parseInt(counter.textContent);
                        counter.textContent = (currentCount - 1) + ' articoli';
                        
                        // Se non ci sono più preferiti, mostra il messaggio vuoto
                        if (currentCount - 1 <= 0) {
                            const grid = document.querySelector('.esplora-grid');
                            grid.innerHTML = `
                                <div class="empty-message">
                                    <h3>Non hai ancora aggiunto preferiti!</h3>
                                    <p>Quando trovi articoli che ti piacciono, aggiungili ai preferiti cliccando sul simbolo del cuore.</p>
                                    <p>I tuoi preferiti saranno sempre disponibili qui per consultarli in seguito.</p>
                                    <a href="index.php">Inizia ad esplorare</a>
                                </div>
                            `;
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
            });
        }
    </script>
    
    <style>
        .vinyl-item {
            position: relative;
            transition: opacity 0.3s ease;
        }
        
        .remove-favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .vinyl-item:hover .remove-favorite-btn {
            opacity: 1;
        }
    </style>
</body>
</html>