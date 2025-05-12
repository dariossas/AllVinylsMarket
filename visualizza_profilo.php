<?php
// visualizza_profilo.php - Pagina per visualizzare il profilo di un altro utente
require_once 'functions.php';

// Verifica che sia stato fornito un ID utente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$profiloUserId = intval($_GET['id']);
$loggedInUserId = isLoggedIn() ? getCurrentUserId() : 0;

// Se l'utente sta cercando di visualizzare il proprio profilo, reindirizzalo alla pagina "profilo.php"
if ($loggedInUserId === $profiloUserId) {
    header("Location: profilo.php");
    exit;
}

// Recupera i dati dell'utente dal database
// Correzione della query SQL - aggiunta selezione corretta dei campi
$sql = "SELECT id_utente, username, nome, cognome, paese, regione, immagine_profilo FROM UTENTI WHERE id_utente = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Errore nella preparazione della query: " . $conn->error);
}

$stmt->bind_param("i", $profiloUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // Utente non trovato
    header("Location: index.php");
    exit;
}

$user = $result->fetch_assoc();

// Statistiche dell'utente
$annunci_count = 0;
$vendite_count = 0;

// Conta annunci attivi
$sql_annunci = "SELECT COUNT(*) as total FROM ANNUNCI WHERE id_utente = ?";
$stmt_annunci = $conn->prepare($sql_annunci);
if ($stmt_annunci) {
    $stmt_annunci->bind_param("i", $profiloUserId);
    $stmt_annunci->execute();
    $result_annunci = $stmt_annunci->get_result();
    $annunci_count = $result_annunci->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo di <?php echo htmlspecialchars($user['username']); ?> - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        .profile-sidebar {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #f5f5f5;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-username {
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-fullname {
            text-align: center;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .profile-location {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 18px;
            font-weight: 600;
            color: #bb1e10;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .content-section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .member-since {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #888;
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
            <form action="risultati.php" method="GET">
                <b><input type="text" name="q" placeholder="🔍 Cerca prodotti"></b>
            </form>
        </div>
        <div class="icons">
            <?php if (isLoggedIn()): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profilo.php" class="icon">👤</a>
            <?php else: ?>
                <a href="login.php" class="login-button">ACCEDI/ISCRIVITI</a>
            <?php endif; ?>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>

    <div class="profile-container">
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-image">
                    <img src="<?php echo !empty($user['immagine_profilo']) ? $user['immagine_profilo'] : 'https://via.placeholder.com/150'; ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>" />
                </div>
                <h3 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h3>
                <?php if (!empty($user['nome']) && !empty($user['cognome'])): ?>
                    <p class="profile-fullname"><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></p>
                <?php endif; ?>
                <p class="profile-location">
                    <?php echo htmlspecialchars($user['regione']); ?>
                    <?php echo !empty($user['paese']) ? ', ' . htmlspecialchars($user['paese']) : ''; ?>
                </p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $annunci_count; ?></div>
                        <div class="stat-label">Annunci</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $vendite_count; ?></div>
                        <div class="stat-label">Vendite</div>
                    </div>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="content-section">
                    <div style="text-align: center; padding: 20px;">
                        <h3>Informazioni sul profilo</h3>
                        <p>Questo è il profilo di <?php echo htmlspecialchars($user['username']); ?>.</p>
                        <p>Puoi visualizzare le informazioni essenziali dell'utente nella barra laterale.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>