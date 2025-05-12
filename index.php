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
        <div class="search-container" style="display: flex; align-items: center; flex-grow: 1; max-width: 600px; margin: 0 20px;">
            <button id="filterBtn" class="filter-button">Filtri
            </button>
            <div class="search-bar" style="flex-grow: 1; margin-left: 10px;">
                <form action="risultati.php" method="GET">
                   <b> <input type="text" name="q" placeholder="Cerca prodotti"> </b>
                </form>
            </div>
        </div>
        <div class="icons">
            <?php if ($isLoggedIn): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profili.php" class="icon">👤</a>
                
            <?php else: ?>
                <a href="login.php" class="login-button">Accedi | Iscriviti</a>
            <?php endif; ?>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>
    
    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Filtri</h2>
                <span class="close">&times;</span>
            </div>
            <form action="risultati.php" method="GET" id="filterForm">
                <div class="filter-section">
                    <h3>Condizioni</h3>
                    <div class="condition-options">
                        <div class="condition-option">
                            <input type="radio" id="condizione_nuovo_pellicola" name="condizione" value="nuovo_pellicola">
                            <label for="condizione_nuovo_pellicola">Nuovo con pellicola</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condizione_nuovo" name="condizione" value="nuovo">
                            <label for="condizione_nuovo">Nuovo</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condizione_buone" name="condizione" value="buone">
                            <label for="condizione_buone">Buone Condizioni</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condizione_usato" name="condizione" value="usato">
                            <label for="condizione_usato">Usato</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condizione_molto_usato" name="condizione" value="molto_usato">
                            <label for="condizione_molto_usato">Molto usato</label>
                        </div>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3>Prezzo</h3>
                    <div class="price-range-control">
                        <input type="range" id="price-range" name="max_price" class="price-slider" min="1" max="1000" value="500" oninput="updatePriceDisplay(this.value)">
                        <div class="price-range-labels">
                            <span>1€</span>
                            <span id="price-display">500€</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="apply-filters-btn">Applica Filtri</button>
            </form>
        </div>
    </div>
    
    <div class="hero">
        <img src="Homepage.jpg" alt="Homepage" />
        <div class="hero-content">
            <div class="hero-text">
                è arrivato il momento di liberare lo scaffale!  <br>  
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
        // Get the modal
        var modal = document.getElementById("filterModal");
        
        // Get the button that opens the modal
        var btn = document.getElementById("filterBtn");
        
        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];
        
        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Funzione per aggiornare il display del prezzo
        function updatePriceDisplay(value) {
            document.getElementById('price-display').textContent = value + '€';
        }
    </script>
    
    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>
