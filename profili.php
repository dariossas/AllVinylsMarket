<?php
// profilo.php - Pagina profilo utente per AllVinylsMarket
require_once 'functions.php';

// Controlla se l'utente è loggato, altrimenti reindirizza alla pagina di login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Recupera l'ID dell'utente corrente
$userId = getCurrentUserId();

// Inizializza le variabili per i messaggi
$error = '';
$success = '';

// Array delle regioni italiane
$regioni = [
    'Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna', 
    'Friuli Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche',
    'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana',
    'Trentino-Alto Adige', 'Umbria', 'Valle d_Aosta', 'Veneto'
];

// Recupera i dati dell'utente dal database
$sql = "SELECT * FROM UTENTI WHERE id_utente = ?";
$stmt = $conn->prepare($sql);

// Verifica se la preparazione della query è riuscita
if ($stmt === false) {
    $error = "Errore nella preparazione della query: " . $conn->error;
    $user = [];  // Inizializza $user come array vuoto
} else {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
    } else {
        // Gestione errore se l'utente non esiste
        $error = "Errore: impossibile recuperare i dati dell'utente";
        $user = [];  // Inizializza $user come array vuoto
    }
}

// Gestione dell'aggiornamento del profilo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiorna_profilo'])) {
    // Recupera i dati dal form
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $regione = isset($_POST['regione']) ? $_POST['regione'] : '';
    $paese = isset($_POST['paese']) ? $_POST['paese'] : 'Italia';
    
    // Validazione dei campi
    if (empty($nome) || empty($cognome) || empty($username) || empty($email) || empty($regione)) {
        $error = "Tutti i campi obbligatori devono essere compilati.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } else {
        // Controlla se username o email esistono già e non appartengono all'utente corrente
        $sql = "SELECT id_utente FROM UTENTI WHERE (username = ? OR email = ?) AND id_utente != ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $error = "Errore nella preparazione della query: " . $conn->error;
        } else {
            $stmt->bind_param("ssi", $username, $email, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username o email già in uso da un altro utente.";
            } else {
                // Gestione upload immagine se presente
                $immagine_profilo = isset($user['immagine_profilo']) ? $user['immagine_profilo'] : ''; // Mantieni l'immagine esistente come default
                $upload_ok = true;
                
                if (isset($_FILES['immagine_profilo']) && $_FILES['immagine_profilo']['size'] > 0) {
                    $target_dir = "uploads/profili/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES["immagine_profilo"]["name"], PATHINFO_EXTENSION));
                    $nuovo_nome = uniqid('profile_') . '.' . $file_extension;
                    $target_file = $target_dir . $nuovo_nome;
                    
                    // Controlla se è un'immagine
                    $check = getimagesize($_FILES["immagine_profilo"]["tmp_name"]);
                    if ($check === false) {
                        $error = "Il file non è un'immagine.";
                        $upload_ok = false;
                    }
                    
                    // Controlla dimensione file (max 2MB)
                    if ($_FILES["immagine_profilo"]["size"] > 2000000) {
                        $error = "L'immagine è troppo grande (max 2MB).";
                        $upload_ok = false;
                    }
                    
                    // Controlla estensione
                    $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = "Sono permessi solo file JPG, JPEG, PNG e GIF.";
                        $upload_ok = false;
                    }
                    
                    // Carica il file se tutto è ok
                    if ($upload_ok) {
                        if (move_uploaded_file($_FILES["immagine_profilo"]["tmp_name"], $target_file)) {
                            // Elimina vecchia immagine se esiste
                            if (!empty($user['immagine_profilo']) && file_exists($user['immagine_profilo'])) {
                                unlink($user['immagine_profilo']);
                            }
                            $immagine_profilo = $target_file;
                        } else {
                            $error = "Si è verificato un errore nel caricamento dell'immagine.";
                        }
                    }
                }
                
                // Aggiorna i dati utente nel database se non ci sono errori
                if (empty($error)) {
                    $sql = "UPDATE UTENTI SET nome = ?, cognome = ?, username = ?, email = ?, paese = ?, regione = ?, immagine_profilo = ? 
                            WHERE id_utente = ?";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt === false) {
                        $error = "Errore nella preparazione della query di aggiornamento: " . $conn->error;
                    } else {
                        $stmt->bind_param("sssssssi", $nome, $cognome, $username, $email, $paese, $regione, $immagine_profilo, $userId);
                        
                        if ($stmt->execute()) {
                            $success = "Profilo aggiornato con successo!";
                            
                            // Aggiorna i dati utente nella variabile $user
                            $user['nome'] = $nome;
                            $user['cognome'] = $cognome;
                            $user['username'] = $username;
                            $user['email'] = $email;
                            $user['paese'] = $paese;
                            $user['regione'] = $regione;
                            $user['immagine_profilo'] = $immagine_profilo;
                        } else {
                            $error = "Errore durante l'aggiornamento del profilo: " . $stmt->error;
                        }
                    }
                }
            }
        }
    }
}

// Gestione della modifica della password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambia_password'])) {
    $password_attuale = $_POST['password_attuale'];
    $nuova_password = $_POST['nuova_password'];
    $conferma_password = $_POST['conferma_password'];
    
    // Verifica che la password attuale sia corretta
    if (!isset($user['password_utente']) || !password_verify($password_attuale, $user['password_utente'])) {
        $error = "La password attuale non è corretta.";
    } elseif (empty($nuova_password) || strlen($nuova_password) < 6) {
        $error = "La nuova password deve essere di almeno 6 caratteri.";
    } elseif ($nuova_password !== $conferma_password) {
        $error = "Le nuove password non corrispondono.";
    } else {
        // Aggiorna la password
        $password_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE UTENTI SET password_utente = ? WHERE id_utente = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $error = "Errore nella preparazione della query: " . $conn->error;
        } else {
            $stmt->bind_param("si", $password_hash, $userId);
            
            if ($stmt->execute()) {
                $success = "Password modificata con successo!";
            } else {
                $error = "Errore durante la modifica della password: " . $stmt->error;
            }
        }
    }
}

// Impostazioni predefinite per le statistiche
$annunci_count = 0;
$vendite_count = 0;
$acquisti_count = 0;

// Recupera le statistiche utente
$sql_annunci = "SELECT COUNT(*) as total FROM ANNUNCI WHERE id_utente = ?";
$stmt_annunci = $conn->prepare($sql_annunci);
if ($stmt_annunci) {
    $stmt_annunci->bind_param("i", $userId);
    $stmt_annunci->execute();
    $result_annunci = $stmt_annunci->get_result();
    $annunci_count = $result_annunci->fetch_assoc()['total'];
}

$sql_vendite = "SELECT COUNT(*) as total FROM TRANSAZIONI WHERE id_venditore = ? AND stato = 'completata'";
$stmt_vendite = $conn->prepare($sql_vendite);
if ($stmt_vendite) {
    $stmt_vendite->bind_param("i", $userId);
    $stmt_vendite->execute();
    $result_vendite = $stmt_vendite->get_result();
    $vendite_count = $result_vendite->fetch_assoc()['total'];
}

$sql_acquisti = "SELECT COUNT(*) as total FROM TRANSAZIONI WHERE id_acquirente = ? AND stato = 'completata'";
$stmt_acquisti = $conn->prepare($sql_acquisti);
if ($stmt_acquisti) {
    $stmt_acquisti->bind_param("i", $userId);
    $stmt_acquisti->execute();
    $result_acquisti = $stmt_acquisti->get_result();
    $acquisti_count = $result_acquisti->fetch_assoc()['total'];
}

// Variabili per memorizzare i risultati
$result_annunci_attivi = null;
$result_annunci_venduti = null;

// Recupera gli annunci attivi dell'utente
$sql_annunci_attivi = "SELECT * FROM ANNUNCI WHERE id_utente = ? AND stato = 'attivo' ORDER BY data_inserimento DESC LIMIT 5";
$stmt_annunci_attivi = $conn->prepare($sql_annunci_attivi);
if ($stmt_annunci_attivi) {
    $stmt_annunci_attivi->bind_param("i", $userId);
    $stmt_annunci_attivi->execute();
    $result_annunci_attivi = $stmt_annunci_attivi->get_result();
}

// Recupera gli annunci venduti dell'utente
$sql_annunci_venduti = "SELECT a.* FROM ANNUNCI a 
                        JOIN TRANSAZIONI t ON a.id_annuncio = t.id_annuncio 
                        WHERE a.id_utente = ? AND t.stato = 'completata' 
                        ORDER BY t.data_transazione DESC LIMIT 5";
$stmt_annunci_venduti = $conn->prepare($sql_annunci_venduti);
if ($stmt_annunci_venduti) {
    $stmt_annunci_venduti->bind_param("i", $userId);
    $stmt_annunci_venduti->execute();
    $result_annunci_venduti = $stmt_annunci_venduti->get_result();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo - AllVinylsMarket</title>
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
        
        .profile-links {
            margin-top: 20px;
        }
        
        .profile-link {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background-color 0.2s;
        }
        
        .profile-link:hover {
            background-color: #f5f5f5;
        }
        
        .profile-link.active {
            background-color: #f5f5f5;
            font-weight: 600;
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
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #444;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #bb1e10;
            outline: none;
        }
        
        .submit-button {
            background-color: #bb1e10;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        
        .submit-button:hover {
            background-color: #951a0d;
        }
        
        .logout-button {
            background-color: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
            margin-top: 20px;
            width: 100%;
        }
        
        .logout-button:hover {
            background-color: #e5e5e5;
        }
        
        .annunci-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .annuncio-item {
            background-color: #f8f8f8;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .annuncio-image {
            width: 100%;
            height: 150px;
            overflow: hidden;
        }
        
        .annuncio-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .annuncio-info {
            padding: 10px;
        }
        
        .annuncio-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .annuncio-price {
            font-size: 16px;
            font-weight: 600;
            color: #bb1e10;
        }
        
        .annuncio-status {
            font-size: 12px;
            color: #666;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background-color: #f5f5f5;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .tab.active {
            background-color: #bb1e10;
            color: white;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        
        .error-message, .success-message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        
        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            color: #d32f2f;
        }
        
        .success-message {
            background-color: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
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
            <a href="messaggi.php" class="icon">📧</a>
            <a href="preferiti.php" class="icon">❤️</a>
            <a href="profilo.php" class="icon">👤</a>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>

    <div class="navigation-menu">
        <div class="menu-container">
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

    <div class="profile-container">
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-image">
                    <img src="<?php echo !empty($user['immagine_profilo']) ? $user['immagine_profilo'] : 'https://via.placeholder.com/150'; ?>" 
                         alt="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'Utente'; ?>" />
                </div>
                <h3 class="profile-username"><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'Utente'; ?></h3>
                <p class="profile-location">
                    <?php echo isset($user['regione']) ? htmlspecialchars($user['regione']) : ''; ?>
                    <?php echo (isset($user['regione']) && isset($user['paese'])) ? ', ' : ''; ?>
                    <?php echo isset($user['paese']) ? htmlspecialchars($user['paese']) : ''; ?>
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
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $acquisti_count; ?></div>
                        <div class="stat-label">Acquisti</div>
                    </div>
                </div>
                
                <div class="profile-links">
                    <a href="#" class="profile-link active" data-tab="info">Informazioni profilo</a>
                    <a href="#" class="profile-link" data-tab="annunci">I miei annunci</a>
                 <a href="#" class="profile-link" data-tab="password">Cambia password</a>
                   
                   
                   
                </div>
                
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-button">Esci</button>
                </form>
            </div>
            
            <div class="profile-content">
                <!-- Tab Informazioni Profilo -->
                <div id="info" class="content-section tab-content active">
                    <h3 class="section-title">Informazioni profilo</h3>
                    <form action="profilo.php" method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome*</label>
                                <input type="text" id="nome" name="nome" value="<?php echo isset($user['nome']) ? htmlspecialchars($user['nome']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome*</label>
                                <input type="text" id="cognome" name="cognome" value="<?php echo isset($user['cognome']) ? htmlspecialchars($user['cognome']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username*</label>
                                <input type="text" id="username" name="username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="paese">Paese*</label>
                                <input type="text" id="paese" name="paese" value="<?php echo isset($user['paese']) ? htmlspecialchars($user['paese']) : 'Italia'; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="regione">Regione*</label>
                                <select id="regione" name="regione" required>
                                    <option value="">Seleziona regione</option>
                                    <?php foreach ($regioni as $r): ?>
                                        <option value="<?php echo $r; ?>" <?php echo (isset($user['regione']) && $user['regione'] === $r) ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="immagine_profilo">Immagine profilo</label>
                            <input type="file" id="immagine_profilo" name="immagine_profilo" accept="image/*">
                            <small>Lascia vuoto per mantenere l'immagine attuale</small>
                        </div>
                        
                        <button type="submit" name="aggiorna_profilo" class="submit-button">Aggiorna profilo</button>
                    </form>
                </div>
                
                <!-- Tab I miei annunci -->
                <div id="annunci" class="content-section tab-content">
                    <h3 class="section-title">I miei annunci</h3>
                    <?php if ($result_annunci_attivi->num_rows > 0): ?>
                        <div class="annunci-grid">
                            <?php while ($annuncio = $result_annunci_attivi->fetch_assoc()): ?>
                                <div class="annuncio-item">
                                    <div class="annuncio-image">
                                        <img src="<?php echo $annuncio['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>" onerror="this.src='https://via.placeholder.com/150x150'">
                                    </div>
                                    <div class="annuncio-info">
                                        <div class="annuncio-title"><?php echo htmlspecialchars($annuncio['titolo']); ?></div>
                                        <div class="annuncio-price">€<?php echo number_format($annuncio['prezzo'], 2, ',', '.'); ?></div>
                                        <div class="annuncio-status">Pubblicato il <?php echo date('d/m/Y', strtotime($annuncio['data_inserimento'])); ?></div>
                                        <a href="modifica_annuncio.php?id=<?php echo $annuncio['id_annuncio']; ?>" class="submit-button" style="display: inline-block; text-decoration: none; text-align: center; margin-top: 10px; font-size: 12px; padding: 5px 10px;">Modifica</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Non hai ancora pubblicato annunci</div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="vendi.php" class="submit-button" style="display: inline-block; text-decoration: none; padding: 10px 20px;">Pubblica nuovo annuncio</a>
                    </div>
                </div>
                
          
                
                <!-- Tab Cambia password -->
                <div id="password" class="content-section tab-content">
                    <h3 class="section-title">Cambia password</h3>
                    <form action="profilo.php" method="POST">
                        <div class="form-group">
                            <label for="password_attuale">Password attuale*</label>
                            <input type="password" id="password_attuale" name="password_attuale" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nuova_password">Nuova password*</label>
                                <input type="password" id="nuova_password" name="nuova_password" required>
                                <small>Minimo 6 caratteri</small>
                            </div>
                            <div class="form-group">
                                <label for="conferma_password">Conferma nuova password*</label>
                                <input type="password" id="conferma_password" name="conferma_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="cambia_password" class="submit-button">Cambia password</button>
                    </form>
                </div>
                
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funzione per mostrare/nascondere i tab
            const showTab = (tabId) => {
                // Nascondi tutti i contenuti dei tab
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Rimuovi la classe active da tutti i link
                document.querySelectorAll('.profile-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Mostra il tab selezionato
                document.getElementById(tabId).classList.add('active');
                
                // Aggiungi la classe active al link cliccato
                document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            };
            
            // Aggiungi l'event listener a tutti i link dei tab
            document.querySelectorAll('.profile-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    showTab(tabId);
                });
            });
            
            // Funzione per aggiornare il display del prezzo
            window.updatePriceDisplay = function(value) {
                document.getElementById('price-display').textContent = '1€ - ' + value + '€';
            };
        });
    </script>
</body>
</html>
                                        
