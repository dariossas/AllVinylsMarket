<?php
// Include functions file
require_once 'functions.php';

// Get user state
$isLoggedIn = isLoggedIn();
$userId = getCurrentUserId();

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

// Initialize error/success messages
$errorMsg = "";
$successMsg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $titolo = trim($_POST['titolo']);
    $artista = trim($_POST['artista']);
    $descrizione = trim($_POST['descrizione']);
    $prezzo = floatval($_POST['prezzo']);
    $condizioni = $_POST['condizioni'];
    $formato = trim($_POST['formato']);
    
    // Validate required fields
    if (empty($titolo) || empty($artista) || empty($prezzo) || empty($condizioni)) {
        $errorMsg = "Per favore compila tutti i campi obbligatori.";
    } else if ($prezzo <= 0 || $prezzo > 999.99) {
        $errorMsg = "Il prezzo deve essere compreso tra 0.01€ e 999.99€.";
    } else {
        // Handle image upload
        $targetDir = "uploads/";
        $fileName = "";
        
        if (isset($_FILES["immagine_copertina"]) && $_FILES["immagine_copertina"]["error"] == 0) {
            $fileName = basename($_FILES["immagine_copertina"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Check if file is an image
            $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Create directory if it doesn't exist
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                // Upload file
                if (move_uploaded_file($_FILES["immagine_copertina"]["tmp_name"], $targetFilePath)) {
                    // Insert record into database
                    $stmt = $conn->prepare("INSERT INTO ANNUNCI (id_utente, titolo, descrizione, prezzo, condizioni, immagine_copertina, artista, formato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issdssss", $userId, $titolo, $descrizione, $prezzo, $condizioni, $targetFilePath, $artista, $formato);
                    
                    if ($stmt->execute()) {
                        $successMsg = "Annuncio pubblicato con successo!";
                        // Clear form data
                        $titolo = $artista = $descrizione = $formato = "";
                        $prezzo = "";
                    } else {
                        $errorMsg = "Si è verificato un errore durante la pubblicazione dell'annuncio. Riprova più tardi.";
                    }
                    $stmt->close();
                } else {
                    $errorMsg = "Si è verificato un errore durante il caricamento dell'immagine.";
                }
            } else {
                $errorMsg = "Sono supportati solo file JPG, JPEG, PNG e GIF.";
            }
        } else {
            // No image uploaded, use default
            $defaultImage = "images/default_vinyl.jpg";
            
            // Insert record into database
            $stmt = $conn->prepare("INSERT INTO ANNUNCI (id_utente, titolo, descrizione, prezzo, condizioni, immagine_copertina, artista, formato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssss", $userId, $titolo, $descrizione, $prezzo, $condizioni, $defaultImage, $artista, $formato);
            
            if ($stmt->execute()) {
                $successMsg = "Annuncio pubblicato con successo!";
                // Clear form data
                $titolo = $artista = $descrizione = $formato = "";
                $prezzo = "";
            } else {
                $errorMsg = "Si è verificato un errore durante la pubblicazione dell'annuncio. Riprova più tardi.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendi il tuo vinile - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: #b22222;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .required::after {
            content: "*";
            color: #b22222;
            margin-left: 3px;
        }
        
        .submit-button {
            background-color: #b22222;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        
        .submit-button:hover {
            background-color: #8b0000;
        }
        
        .error-message {
            color: #b22222;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fde8e8;
            border-radius: 4px;
            text-align: center;
        }
        
        .success-message {
            color: #0f5132;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d1e7dd;
            border-radius: 4px;
            text-align: center;
        }
        
        .image-preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 200px;
            display: none;
        }
        
        .info-text {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }
    </style>
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
            <?php if ($isLoggedIn): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profilo.php" class="icon">👤</a>
                <a href="logout.php" class="login-button">ESCI</a>
            <?php else: ?>
                <a href="login.php" class="login-button">ACCEDI/ISCRIVITI</a>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="form-container">
        <h2 class="form-title">Vendi il tuo vinile</h2>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="error-message"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($successMsg)): ?>
            <div class="success-message"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <form action="vendi.php" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="titolo" class="required">Titolo dell'album</label>
                    <input type="text" id="titolo" name="titolo" value="<?php echo isset($titolo) ? htmlspecialchars($titolo) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="artista" class="required">Artista</label>
                    <input type="text" id="artista" name="artista" value="<?php echo isset($artista) ? htmlspecialchars($artista) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="prezzo" class="required">Prezzo (€)</label>
                    <input type="number" id="prezzo" name="prezzo" step="0.01" min="0.01" max="999.99" value="<?php echo isset($prezzo) ? htmlspecialchars($prezzo) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="condizioni" class="required">Condizioni</label>
                    <select id="condizioni" name="condizioni" required>
                        <option value="">Seleziona le condizioni</option>
                        <option value="Nuovo_con_pellicola" <?php echo (isset($condizioni) && $condizioni == 'Nuovo_con_pellicola') ? 'selected' : ''; ?>>Nuovo con pellicola</option>
                        <option value="Nuovo" <?php echo (isset($condizioni) && $condizioni == 'Nuovo') ? 'selected' : ''; ?>>Nuovo</option>
                        <option value="Buone condizioni" <?php echo (isset($condizioni) && $condizioni == 'Buone condizioni') ? 'selected' : ''; ?>>Buone condizioni</option>
                        <option value="Usato" <?php echo (isset($condizioni) && $condizioni == 'Usato') ? 'selected' : ''; ?>>Usato</option>
                        <option value="Molto Usato" <?php echo (isset($condizioni) && $condizioni == 'Molto Usato') ? 'selected' : ''; ?>>Molto Usato</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="formato">Formato</label>
                <input type="text" id="formato" name="formato" placeholder="33 giri, 45 giri, Picture Disc, ecc." value="<?php echo isset($formato) ? htmlspecialchars($formato) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" placeholder="Descrivi il tuo vinile, fornisci dettagli sulle condizioni, l'edizione, ecc."><?php echo isset($descrizione) ? htmlspecialchars($descrizione) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="immagine_copertina">Immagine della copertina</label>
                <input type="file" id="immagine_copertina" name="immagine_copertina" accept="image/jpeg, image/png, image/gif" onchange="previewImage(this)">
                <p class="info-text">Formati supportati: JPG, PNG, GIF. Dimensione massima: 5MB.</p>
                <img id="imagePreview" class="image-preview" src="#" alt="Anteprima immagine">
            </div>
            
            <button type="submit" class="submit-button">PUBBLICA ANNUNCIO</button>
        </form>
    </div>
    
    <script>
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>