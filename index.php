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
         
                <a href="login.php" class="login-button">ACCEDI/ISCRIVITI</a>
           
        </div>
    </header>
    
    <div class="hero">
        <img src="Homepage.jpg" alt="Homepage" />
        <div class="hero-content">
            <div class="hero-text">
                Liberati dal dubbio che non vi piacciono i vinili: quelli dei cari artisti preferiti!
            </div>
            <a href="catalogo.php" class="hero-button">SCOPRI ALTRO</a>
        </div>
    </div>
    
    <div class="novita">
        NOVITÀ
        <h3>Vecchi CD e vinili tornano cool!</h3>
    </div>
    
    <div class="cassette-box">
        <div class="cassette-info">
            <a href="catalogo.php?categoria=cassette" class="cassette-button">VEDI SUBITO</a>
        </div>
        <div class="cassette-image">
            <img src="images/cassette.jpg" alt="Vintage cassette tapes" />
        </div>
    </div>
    
    <div class="lista-preferiti">
        <h3 class="preferiti-title">Lista dei preferiti</h3>
        <div class="vinyl-grid">
            <?php if ($isLoggedIn && count($favorites) > 0): ?>
                <?php foreach ($favorites as $vinyl): ?>
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
            <?php elseif ($isLoggedIn): ?>
                <div class="empty-message">
                    Non hai ancora aggiunto annunci ai preferiti. Esplora il catalogo e aggiungi i vinili che ti piacciono!
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <a href="login.php">Accedi</a> per vedere i tuoi annunci preferiti!
                </div>
            <?php endif; ?>
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
                <div class="empty-message">
                    Non ci sono ancora annunci disponibili.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>
