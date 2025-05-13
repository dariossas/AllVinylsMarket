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

// Gestione della rimozione di un annuncio
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_annuncio'])) {
    $annuncio_id = intval($_POST['annuncio_id']);
    
    // Verifica che l'annuncio appartenga all'utente corrente
    $check_sql = "SELECT id_utente FROM ANNUNCI WHERE id_annuncio = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $annuncio_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $annuncio_data = $check_result->fetch_assoc();
        if ($annuncio_data['id_utente'] == $userId) {
            // Elimina prima le eventuali preferenze
            $delete_pref_sql = "DELETE FROM LISTA_PREFERITI WHERE id_annuncio = ?";
            $delete_pref_stmt = $conn->prepare($delete_pref_sql);
            $delete_pref_stmt->bind_param("i", $annuncio_id);
            $delete_pref_stmt->execute();
            
            // Poi elimina l'annuncio
            $delete_sql = "DELETE FROM ANNUNCI WHERE id_annuncio = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $annuncio_id);
            
            if ($delete_stmt->execute()) {
                $success = "Annuncio rimosso con successo!";
            } else {
                $error = "Errore durante la rimozione dell'annuncio: " . $conn->error;
            }
        } else {
            $error = "Non sei autorizzato a rimuovere questo annuncio.";
        }
    } else {
        $error = "Annuncio non trovato.";
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

// Recupera il conteggio degli annunci dell'utente
$sql_count = "SELECT COUNT(*) as total FROM ANNUNCI WHERE id_utente = ?";
$stmt_count = $conn->prepare($sql_count);
$annunci_count = 0;

if ($stmt_count) {
    $stmt_count->bind_param("i", $userId);
    if ($stmt_count->execute()) {
        $result_count = $stmt_count->get_result();
        $count_data = $result_count->fetch_assoc();
        $annunci_count = $count_data['total'];
    }
    $stmt_count->close();
}

// Recupera gli annunci dell'utente
$sql_annunci = "SELECT * FROM ANNUNCI WHERE id_utente = ? ORDER BY data_caricamento DESC";
$stmt_annunci = $conn->prepare($sql_annunci);
$annunci = [];

if ($stmt_annunci) {
    $stmt_annunci->bind_param("i", $userId);
    if ($stmt_annunci->execute()) {
        $result_annunci = $stmt_annunci->get_result();
        $annunci = $result_annunci->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_annunci->close();
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
            margin-left: 85px;
            width: 100%;
            
        }
        
        .logout-button:hover {
            background-color: #e5e5e5;
        }
        
        .esplora-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }
        
        .vinyl-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .vinyl-item:hover {
            transform: translateY(-5px);
        }
        
        .vinyl-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .vinyl-item h4 {
            margin: 10px 15px 5px;
            font-size: 1em;
            color: #333;
        }
        
        .vinyl-item p {
            margin: 0 15px 5px;
            font-size: 0.9em;
            color: #666;
        }
        
        .price {
            font-weight: bold;
            color: #bb1e10 !important;
            font-size: 1.1em !important;
            margin: 10px 15px !important;
        }
        
        .remove-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-bottom: 15px;
            transition: background-color 0.3s;
        }
        
        .remove-button:hover {
            background-color: #c82333;
        }
        
        .annuncio-grid {
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
        
        .annuncio-data {
            font-size: 12px;
            color: #666;
        }
        
        .annuncio-preferiti {
            display: flex;
            align-items: center;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .annuncio-preferiti .heart {
            color: #bb1e10;
            margin-right: 3px;
        }
        
        .annuncio-actions {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }
        
        .action-btn {
            flex: 1;
            text-align: center;
            padding: 5px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .edit-btn {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .remove-btn {
            background-color: #ffe6e6;
            color: #bb1e10;
            border: 1px solid #ffcccc;
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
        
        .confirm-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .dialog-content {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        
        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .dialog-btn {
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            border: none;
        }
        
        .confirm-btn {
            background-color: #bb1e10;
            color: white;
        }
        
        .cancel-btn {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
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
            <a href="profili.php" class="icon">👤</a>
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

    <!-- Dialog di conferma per rimozione annuncio -->
    <div id="confirm-dialog" class="confirm-dialog">
        <div class="dialog-content">
            <h3>Conferma rimozione</h3>
            <p>Sei sicuro di voler rimuovere questo annuncio? Questa azione non può essere annullata.</p>
            <form id="remove-form" action="profili.php" method="POST">
                <input type="hidden" id="remove-annuncio-id" name="annuncio_id" value="">
                <input type="hidden" name="rimuovi_annuncio" value="1">
                <div class="dialog-buttons">
                    <button type="button" class="dialog-btn cancel-btn" onclick="hideConfirmDialog()">Annulla</button>
                    <button type="submit" class="dialog-btn confirm-btn">Rimuovi</button>
                </div>
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
                </div>
                
                <div class="profile-links">
                    
                    <a href="logout.php" class="logout-button">Logout</a>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="tabs">
                    <div class="tab active" data-tab="info">Informazioni profilo</div>
                    <div class="tab" data-tab="annunci">I miei annunci</div>
                    <div class="tab" data-tab="password">Cambia password</div>
                </div>
                
                <!-- Sezione informazioni profilo -->
                <div id="info" class="tab-content content-section active">
                    <h3 class="section-title">Modifica profilo</h3>
                    <form action="profili.php" method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?php echo isset($user['nome']) ? htmlspecialchars($user['nome']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome</label>
                                <input type="text" id="cognome" name="cognome" value="<?php echo isset($user['cognome']) ? htmlspecialchars($user['cognome']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="regione">Regione</label>
                                <select id="regione" name="regione">
                                    <option value="">Seleziona una regione</option>
                                    <?php foreach ($regioni as $regione): ?>
                                        <option value="<?php echo $regione; ?>" <?php echo (isset($user['regione']) && $user['regione'] == $regione) ? 'selected' : ''; ?>>
                                            <?php echo $regione; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="paese">Paese</label>
                                <input type="text" id="paese" name="paese" value="<?php echo isset($user['paese']) ? htmlspecialchars($user['paese']) : 'Italia'; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="immagine_profilo">Immagine del profilo</label>
                                <input type="file" id="immagine_profilo" name="immagine_profilo" accept="image/*">
                            </div>
                        </div>
                        
                        <button type="submit" name="aggiorna_profilo" class="submit-button">Aggiorna profilo</button>
                    </form>
                </div>
                
                <!-- Sezione annunci -->
                <div id="annunci" class="tab-content content-section">
                    <h3 class="section-title">I miei annunci (<?php echo $annunci_count; ?>)</h3>
                    
                    <?php if (empty($annunci)): ?>
                        <div class="empty-state">
                            <p>Non hai ancora pubblicato annunci.</p>
                            <a href="pubblica.php" class="submit-button" style="display: inline-block; margin-top: 15px;">Pubblica un annuncio</a>
                        </div>
                    <?php else: ?>
                        <div class="annuncio-grid">
                            <?php foreach ($annunci as $annuncio): ?>
                                <div class="annuncio-item">
                                    <div class="annuncio-image">
                                        <img src="<?php echo !empty($annuncio['immagine_copertina']) ? htmlspecialchars($annuncio['immagine_copertina']) : 'https://via.placeholder.com/200x150?text=No+Image'; ?>" 
                                             alt="<?php echo htmlspecialchars($annuncio['titolo']); ?>">
                                    </div>
                                    <div class="annuncio-info">
                                        <div class="annuncio-title"><?php echo htmlspecialchars($annuncio['titolo']); ?></div>
                                        <div class="annuncio-price"><?php echo number_format($annuncio['prezzo'], 2); ?>€</div>
                                        <div class="annuncio-data">
                                            <?php echo date('d/m/Y', strtotime($annuncio['data_caricamento'])); ?>
                                        </div>
                                        <div class="annuncio-actions">
                                            
                                            <a href="#" class="action-btn remove-btn" onclick="showConfirmDialog(<?php echo $annuncio['id_annuncio']; ?>)">Rimuovi</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sezione cambio password -->
                <div id="password" class="tab-content content-section">
                    <h3 class="section-title">Cambia password</h3>
                    <form action="profili.php" method="POST">
                        <div class="form-group">
                            <label for="password_attuale">Password attuale</label>
                            <input type="password" id="password_attuale" name="password_attuale" required>
                        </div>
                        <div class="form-group">
                            <label for="nuova_password">Nuova password</label>
                            <input type="password" id="nuova_password" name="nuova_password" required>
                        </div>
                        <div class="form-group">
                            <label for="conferma_password">Conferma nuova password</label>
                            <input type="password" id="conferma_password" name="conferma_password" required>
                        </div>
                        <button type="submit" name="cambia_password" class="submit-button">Cambia password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funzione per aggiornare la visualizzazione del prezzo
        function updatePriceDisplay(value) {
            document.getElementById("price-display").innerText = "1€ - " + value + "€";
        }
        
        // Funzione per gestire le tab
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione delle tab
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            const profileLinks = document.querySelectorAll('.profile-link');
            
            function setActiveTab(tabId) {
                // Rimuove la classe active da tutte le tab e i contenuti
                tabs.forEach(tab => tab.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                profileLinks.forEach(link => link.classList.remove('active'));
                
                // Attiva la tab e il contenuto corrispondente
                document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
                document.getElementById(tabId).classList.add('active');
                document.querySelector(`.profile-link[data-tab="${tabId}"]`).classList.add('active');
            }
            
            // Event listener per i clic sulle tab
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    setActiveTab(tabId);
                });
            });
            
            // Event listener per i link nel sidebar
            profileLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    setActiveTab(tabId);
                });
            });
            
            // Funzioni per la gestione della finestra di dialogo di conferma
            window.showConfirmDialog = function(annuncioId) {
                document.getElementById('remove-annuncio-id').value = annuncioId;
                document.getElementById('confirm-dialog').style.display = 'flex';
            }
            
            window.hideConfirmDialog = function() {
                document.getElementById('confirm-dialog').style.display = 'none';
            }
        });
    </script>
</body>
</html>
