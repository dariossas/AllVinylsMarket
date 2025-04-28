<?php
// registrazione.php - Pagina di iscrizione per AllVinylsMarket
require_once 'functions.php';

$error = '';
$success = '';

// Array delle regioni italiane (corrispondenti all'ENUM nel database)
$regioni = [
    'Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna', 
    'Friuli Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche',
    'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana',
    'Trentino-Alto Adige', 'Umbria', 'Valle d_Aosta', 'Veneto'
];

// Verifica se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $conferma_password = $_POST['conferma_password'];
    $regione = isset($_POST['regione']) ? $_POST['regione'] : '';
    $paese = isset($_POST['paese']) ? $_POST['paese'] : 'Italia';
    $termini = isset($_POST['termini']) ? true : false;
    
    // Inizializza le variabili per l'immagine del profilo
    $immagine_profilo = "";
    $upload_ok = true;
    
    // Validazione dei campi
    if (empty($nome) || empty($cognome) || empty($username) || empty($email) || empty($password) || empty($regione) || !$termini) {
        $error = "Tutti i campi obbligatori devono essere compilati e devi accettare i termini e condizioni.";
    } elseif ($password !== $conferma_password) {
        $error = "Le password non corrispondono.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } else {
        // Controlla se username o email esistono già
        $sql = "SELECT id_utente FROM UTENTI WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username o email già in uso.";
        } else {
            // Gestione upload immagine se presente
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
                        $immagine_profilo = $target_file;
                    } else {
                        $error = "Si è verificato un errore nel caricamento dell'immagine.";
                    }
                }
            }
            
            // Procedi con la registrazione se non ci sono errori
            if (empty($error)) {
                // Hash della password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Inserisci il nuovo utente nel database
                $sql = "INSERT INTO UTENTI (nome, cognome, username, email, password_utente, paese, regione, immagine_profilo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssss", $nome, $cognome, $username, $email, $password_hash, $paese, $regione, $immagine_profilo);
                
                if ($stmt->execute()) {
                    $success = "Registrazione completata con successo! Ora puoi accedere con le tue credenziali.";
                    // Redirect alla pagina di login dopo 3 secondi
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Errore durante la registrazione: " . $stmt->error;
                }
            }
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Stili specifici per la pagina di registrazione */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            overflow: auto;
        }
        
        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            align-items: center;
            z-index: 2;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-container img {
            height: 40px;
            width: 40px;
        }
        
        .logo-container h3 {
            color: #bb1e10;
            font-family: 'Brush Script MT', cursive;
            font-size: 24px;
            margin-left: 10px;
        }
        
        .background-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            object-fit: cover;
        }
        
        .registration-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            flex-grow: 1;
        }
        
        .registration-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 500px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .registration-box h2 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .registration-box p {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .input-field {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
        }
        
        .registration-button {
            background-color: #bb1e10;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .login-link a {
            color: #bb1e10;
            text-decoration: none;
        }
        
        .error-message {
            color: #bb1e10;
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(187, 30, 16, 0.1);
            border-radius: 4px;
        }
        
        .success-message {
            color: #2e7d32;
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(46, 125, 50, 0.1);
            border-radius: 4px;
        }
        
        footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <img src="Homepage.jpg" alt="Sfondo" class="background-image">
        
        <header class="header">
            <div class="logo-container">
                <img src="LOGO.png" alt="AllVinylsMarket Logo">
                <h3>AllVinylsMarket</h3>
            </div>
            <a href="index.php" style="color: #333; text-decoration: none; font-weight: bold;">Home</a>
        </header>
        
        <div class="registration-form-container">
            <div class="registration-box">
                <h2>Registrati</h2>
                <p>Crea un account per iniziare a comprare e vendere vinili</p>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="registrazione.php" method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome*</label>
                            <input type="text" id="nome" name="nome" class="input-field" value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cognome">Cognome*</label>
                            <input type="text" id="cognome" name="cognome" class="input-field" value="<?php echo isset($cognome) ? htmlspecialchars($cognome) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username*</label>
                        <input type="text" id="username" name="username" class="input-field" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" class="input-field" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password*</label>
                            <input type="password" id="password" name="password" class="input-field" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="conferma_password">Conferma Password*</label>
                            <input type="password" id="conferma_password" name="conferma_password" class="input-field" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="paese">Paese di residenza*</label>
                            <input type="text" id="paese" name="paese" class="input-field" value="Italia" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="regione">Regione*</label>
                            <select id="regione" name="regione" class="input-field" required>
                                <option value="">Seleziona regione</option>
                                <?php foreach ($regioni as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo (isset($regione) && $regione === $r) ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="immagine_profilo">Immagine profilo (opzionale)</label>
                        <input type="file" id="immagine_profilo" name="immagine_profilo" class="input-field" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" id="termini" name="termini" required>
                        <label for="termini" style="display: inline;">Accetto i <a href="termini.php" target="_blank">Termini e Condizioni</a>*</label>
                    </div>
                    
                    <button type="submit" class="registration-button">Registrati</button>
                </form>
                
                <div class="login-link">
                    Hai già un account? <a href="login.php">Accedi ora</a>
                </div>
            </div>
        </div>
        
        <footer>
            © 2025 by Business Name. Built on Wix Studio
        </footer>
    </div>
</body>
</html>     
