<?php
// Include functions file
require_once 'functions.php';

// Get user state
$isLoggedIn = isLoggedIn();
$userId = getCurrentUserId();

// Get annuncio ID from URL
$annuncioId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if ID is valid
if ($annuncioId <= 0) {
    // Redirect to homepage or show error
    header("Location: index.php");
    exit();
}

// Get annuncio details from database
$annuncio = getAnnuncioById($conn, $annuncioId);

// Check if annuncio exists
if (!$annuncio) {
    // Redirect to homepage or show error
    header("Location: index.php");
    exit();
}

// Check if this item is in user's favorites
$isFavorite = $isLoggedIn ? checkIsFavorite($conn, $userId, $annuncioId) : false;

// Get seller information
$venditore = getVenditoreById($conn, $annuncio['id_utente']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($annuncio['titolo']); ?> - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .annuncio-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .annuncio-images {
            flex: 1;
            min-width: 300px;
        }

        .annuncio-info {
            flex: 1;
            min-width: 300px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
        }

        .annuncio-title {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .annuncio-price {
            font-size: 28px;
            color: #b22222;
            margin: 15px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: 500;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-button {
            padding: 12px;
            text-align: center;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .contact-button {
            background-color: #b22222;
            color: white;
        }

        .offer-button {
            border: 1px solid #b22222;
            color: #b22222;
            background-color: white;
        }

        .favorite-button {
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 24px;
            align-self: flex-end;
        }

        .vinyl-details {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color:#b22222; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3>
        </div>
        <div class="search-bar">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Cerca articoli">
            </form>
        </div>
        <div class="icons">
            <?php if ($isLoggedIn): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profili.php" class="icon">👤</a>
               
            <?php else: ?>
                <a href="login.php" class="login-button">ACCEDI/ISCRIVITI</a>
            <?php endif; ?>
            <a href="vendi.php" class="login-button" style="background-color: #b22222; color: white;">vendi subito</a>
        </div>
    </header>

    <div class="annuncio-container">
        <div class="annuncio-images">
            <img src="<?php echo $annuncio['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>" style="width: 100%;" onerror="this.src='https://via.placeholder.com/500x500'"/>
        </div>

        <div class="annuncio-info">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h1 class="annuncio-title"><?php echo htmlspecialchars($annuncio['titolo']); ?></h1>
                <?php if ($isLoggedIn): ?>
                    <button class="favorite-button" title="Aggiungi ai preferiti" onclick="toggleFavorite(<?php echo $annuncioId; ?>)">
                        <?php echo $isFavorite ? '❤️' : '🤍'; ?>
                    </button>
                <?php endif; ?>
            </div>

            <p><?php echo htmlspecialchars($annuncio['condizioni']); ?></p>

            <h2 class="annuncio-price"><?php echo number_format($annuncio['prezzo'], 2, ',', '.'); ?>€</h2>

            <div class="info-row">
                <span class="info-label">Condizioni</span>
                <span class="info-value"><?php echo htmlspecialchars($annuncio['condizioni']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Caricato</span>
                <span class="info-value"><?php echo date("d/m/Y", strtotime($annuncio['data_caricamento'])); ?></span>
            </div>

            <div class="vinyl-details">
                <p>Vinile <?php echo htmlspecialchars($annuncio['titolo']); ?> - <?php echo htmlspecialchars($annuncio['artista']); ?></p>
                <?php if (!empty($annuncio['descrizione'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($annuncio['descrizione'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($annuncio['formato'])): ?>
                    <div class="info-row">
                        <span class="info-label">Formato</span>
                        <span class="info-value"><?php echo htmlspecialchars($annuncio['formato']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="contatta.php?id=<?php echo $annuncioId; ?>&venditore=<?php echo $annuncio['id_utente']; ?>" class="action-button contact-button">Contatta per info</a>
                
            </div>
        </div>
    </div>

    <script>
        function toggleFavorite(annuncioId) {
            // AJAX request to add/remove from favorites
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
                    const button = document.querySelector('.favorite-button');
                    if (data.isFavorite) {
                        button.innerHTML = '❤️';
                    } else {
                        button.innerHTML = '🤍';
                    }
                }
            })
            .catch(error => {
                console.error('Errore:', error);
            });
        }
    </script>

    <?php
    // Funzioni per recuperare i dati dell'annuncio
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

    function getVenditoreById($conn, $id) {
        $id = intval($id);
        $sql = "SELECT id_utente, username, email FROM UTENTI WHERE id_utente = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    function checkIsFavorite($conn, $userId, $annuncioId) {
        $sql = "SELECT * FROM LISTA_PREFERITI WHERE id_utente = ? AND id_annuncio = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $annuncioId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
    ?>
</body>
</html>
