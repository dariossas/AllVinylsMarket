<?php
// Include functions file
require_once 'functions.php';

// Get user state
$isLoggedIn = isLoggedIn();
$userId = getCurrentUserId();

// Get favorites if user is logged in
$favorites = $isLoggedIn ? getFavoriteListings($conn, $userId) : array();

// Get recent listings for exploration section
$recentListings = getRecentListings($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
   
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color:#bb1e10; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3>
        </div>
        <div class="search-bar">
            <form action="search.php" method="GET">
               <b> <input type="text" name="q" placeholder="🔍 Cerca prodotti"> </b>
            </form>
        </div>
        <div class="icons">
            <?php if ($isLoggedIn): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profilo.php" class="icon">👤</a>
                
            <?php else: ?>
                <a href="login.php" class="login-button">Accedi | Iscriviti</a>
            <?php endif; ?>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>
    
   
            
            <!-- Categoria CONDIZIONI -->
            <div class="category-dropdown">
                <div class="category-btn">Condizioni</div>
                <div class="dropdown-content">
                    <a href="risultati.php?condizione=nuovo_pellicola">Nuovo con pellicola</a>
                    <a href="risultati.php?condizione=nuovo">Nuovo</a>
                    <a href="risultati.php?condizione=buone">Buone Condizioni</a>
                    <a href="risultati.php?condizione=usato">Usato</a>
                    <a href="risultati.php?condizione=molto_usato">Molto usato</a>
                </div>
            </div>
            
            <!-- Filtro prezzo -->
            <form action="risultati.php" method="GET" class="price-filter">
                <label for="price-range">Prezzo:</label>
                <input type="range" id="price-range" name="max_price" min="1" max="1000" value="500" oninput="updatePriceDisplay(this.value)">
                <span class="price-display" id="price-display">1€ - 500€</span>
                <button type="submit" class="apply-filter">Applica</button>
            </form>
        </div>
    </div>
    
    <div class="hero">
        <img src="Homepage.jpg" alt="Homepage" />
        <div class="hero-content">
            <div class="hero-text">
                arrivato il momento di liberare lo scaffale!  <br>  
                <a href="vendi.php?categoria=cassette" class="cassette-button">Vendi subito</a> <br>
               <b><a href="comefunziona.html" class="hero-button">Scopri come funziona</a></b>
                
            </div>
        </div>
    </div>
    

    
    <div class="esplora">
        <h3 class="esplora-title">Esplora</h3>
        <div class="esplora-grid">
            <?php if (count($recentListings) > 0): ?>
                <?php foreach ($recentListings as $vinyl): ?>
                    <div class="vinyl-item">
                        <a href="annuncio.php?id=<?php echo $vinyl['id_annuncio']; ?>">
                            <img src="<?php echo $vinyl['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($vinyl['titolo']); ?>" onerror="this.src='https://via.placeholder.com/150x150'"/>
                            <h4><?php echo htmlspecialchars($vinyl['titolo']); ?></h4>
                            <p>Di: <?php echo htmlspecialchars($vinyl['artista']); ?></p>
                            <p>Vinile, <?php echo htmlspecialchars($vinyl['formato']); ?></p>
                            <p class="price">€<?php echo number_format($vinyl['prezzo'], 2, ',', '.'); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
               
            <?php endif; ?>
        </div>
    </div>
    
    <div class="cassette-box">
        <div class="cassette-image">
            <div class="novita-overlay">
                <div class="novita-title">NOVITÀ</div>
                <h3 class="novita-text">Vendi CD o Vinili senza costi!</h3>
            </div>
            <img src="cassette.png" alt="Vintage cassette tapes" />
        </div>
    </div>
    
    <script>
        // Funzione per aggiornare il display del prezzo
        function updatePriceDisplay(value) {
            document.getElementById('price-display').textContent = '1€ - ' + value + '€';
        }
    </script>
    
    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>
