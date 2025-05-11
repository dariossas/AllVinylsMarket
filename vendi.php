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
    if (empty($titolo) || empty($artista) || empty($prezzo) || empty($condizioni) || empty($formato)) {
        $errorMsg = "Per favore compila tutti i campi obbligatori.";
    } else if ($prezzo <= 0 || $prezzo > 999.99) {
        $errorMsg = "Il prezzo deve essere compreso tra 0.01€ e 999.99€.";
    } else {
        // Handle image uploads
        $targetDir = "uploads/";
        $uploadedFiles = [];
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        $maxFiles = 5; // Maximum number of allowed files
        $uploadSuccess = true;
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Process multiple file upload
        if (isset($_FILES['immagini']) && !empty($_FILES['immagini']['name'][0])) {
            // Count total files
            $countFiles = count($_FILES['immagini']['name']);
            
            // Limit to max 5 files
            $countFiles = min($countFiles, $maxFiles);
            
            // Loop through each file
            for ($i = 0; $i < $countFiles; $i++) {
                if ($_FILES['immagini']['error'][$i] == 0) {
                    $fileName = basename($_FILES['immagini']['name'][$i]);
                    $targetFilePath = $targetDir . time() . "_" . $i . "_" . $fileName; // Adding timestamp and index to make filename unique
                    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
                    
                    // Check if file is an image
                    if (in_array(strtolower($fileType), $allowTypes)) {
                        // Upload file
                        if (move_uploaded_file($_FILES['immagini']['tmp_name'][$i], $targetFilePath)) {
                            $uploadedFiles[] = $targetFilePath;
                        } else {
                            $uploadSuccess = false;
                            $errorMsg = "Si è verificato un errore durante il caricamento dell'immagine " . ($i + 1);
                            break;
                        }
                    } else {
                        $uploadSuccess = false;
                        $errorMsg = "Sono supportati solo file JPG, JPEG, PNG e GIF.";
                        break;
                    }
                } else if ($_FILES['immagini']['error'][$i] != 4) { // 4 means no file was uploaded, which is OK
                    $uploadSuccess = false;
                    $errorMsg = "Si è verificato un errore durante il caricamento dell'immagine " . ($i + 1);
                    break;
                }
            }
        }
        
        // If no images were uploaded, use default
        if (empty($uploadedFiles)) {
            $uploadedFiles[] = "images/default_vinyl.jpg";
        }
        
        // If uploads were successful, insert record into database
        if ($uploadSuccess) {
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Insert main record into ANNUNCI table
                $mainImagePath = $uploadedFiles[0]; // First image is the main cover
                
                $stmt = $conn->prepare("INSERT INTO ANNUNCI (id_utente, titolo, descrizione, prezzo, condizioni, immagine_copertina, artista, formato) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issdssss", $userId, $titolo, $descrizione, $prezzo, $condizioni, $mainImagePath, $artista, $formato);
                
                if ($stmt->execute()) {
                    $annuncioId = $conn->insert_id;
                    
                    // Insert additional images if any
                    if (count($uploadedFiles) > 1) {
                        $stmtImages = $conn->prepare("INSERT INTO IMMAGINI_ANNUNCIO (id_annuncio, percorso_immagine) VALUES (?, ?)");
                        
                        // Start from index 1 because index 0 is already used as the main image
                        for ($i = 1; $i < count($uploadedFiles); $i++) {
                            $stmtImages->bind_param("is", $annuncioId, $uploadedFiles[$i]);
                            $stmtImages->execute();
                        }
                        $stmtImages->close();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $successMsg = "Annuncio pubblicato con successo!";
                    // Clear form data
                    $titolo = $artista = $descrizione = "";
                    $prezzo = "";
                    $formato = "";
                } else {
                    // Rollback on error
                    $conn->rollback();
                    $errorMsg = "Si è verificato un errore durante la pubblicazione dell'annuncio. Riprova più tardi.";
                }
                $stmt->close();
            } catch (Exception $e) {
                // Rollback on exception
                $conn->rollback();
                $errorMsg = "Si è verificato un errore durante la pubblicazione dell'annuncio: " . $e->getMessage();
            }
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
            max-width: 150px;
            max-height: 150px;
            display: block;
            margin-right: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        
        .image-uploads {
            border: 1px dashed #ddd;
            padding: 15px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        
        .image-upload-item {
            margin-bottom: 15px;
        }
        
        .previews-container {
            display: flex;
            flex-wrap: wrap;
            margin-top: 15px;
            gap: 15px;
        }
        
        .file-input-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .preview-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .preview-label {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .main-cover-label {
            font-weight: bold;
            color: #b22222;
        }
        
        .warning-text {
            color: #b22222;
            font-size: 14px;
            margin-top: 10px;
            padding: 5px;
            background-color: #fde8e8;
            border-radius: 4px;
            width: 100%;
            text-align: center;
        }
        
        /* Stile personalizzato per il bottone di caricamento file */
        .custom-file-button {
            display: inline-block;
            padding: 10px 16px;
            background-color:rgb(255, 255, 255);
            color: white;
            border-radius: 40px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
           
        }
        
      
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php"><img src="LOGO.png" alt="AllVinylsMarket Logo" /></a>
            <h3 style="color: #bb1e10; font-family:brush script mt; font-size:160%;">AllVinylsMarket</h3> 
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
                <label for="formato" class="required">Formato</label>
                <select id="formato" name="formato" required>
                    <option value="">Seleziona il formato</option>
                    <optgroup label="Vinili">
                        <option value="Vinile LP 33 giri" <?php echo (isset($formato) && $formato == 'Vinile LP 33 giri') ? 'selected' : ''; ?>>Vinile LP 33 giri</option>
                        <option value="Vinile Singolo 45 giri" <?php echo (isset($formato) && $formato == 'Vinile Singolo 45 giri') ? 'selected' : ''; ?>>Vinile Singolo 45 giri</option>
                        <option value="Vinile EP 45 giri" <?php echo (isset($formato) && $formato == 'Vinile EP 45 giri') ? 'selected' : ''; ?>>Vinile EP 45 giri</option>
                        <option value="Vinile Doppio 33 giri" <?php echo (isset($formato) && $formato == 'Vinile Doppio 33 giri') ? 'selected' : ''; ?>>Vinile Doppio 33 giri</option>
                        <option value="Vinile Picture Disc" <?php echo (isset($formato) && $formato == 'Vinile Picture Disc') ? 'selected' : ''; ?>>Vinile Picture Disc</option>
                        <option value="Vinile Colorato" <?php echo (isset($formato) && $formato == 'Vinile Colorato') ? 'selected' : ''; ?>>Vinile Colorato</option>
                        <option value="Box Set Vinile" <?php echo (isset($formato) && $formato == 'Box Set Vinile') ? 'selected' : ''; ?>>Box Set Vinile</option>
                        <option value="Vinile 78 giri" <?php echo (isset($formato) && $formato == 'Vinile 78 giri') ? 'selected' : ''; ?>>Vinile 78 giri</option>
                    </optgroup>
                    <optgroup label="CD">
                        <option value="CD Standard" <?php echo (isset($formato) && $formato == 'CD Standard') ? 'selected' : ''; ?>>CD Standard</option>
                        <option value="CD Singolo" <?php echo (isset($formato) && $formato == 'CD Singolo') ? 'selected' : ''; ?>>CD Singolo</option>
                        <option value="CD Doppio" <?php echo (isset($formato) && $formato == 'CD Doppio') ? 'selected' : ''; ?>>CD Doppio</option>
                        <option value="Box Set CD" <?php echo (isset($formato) && $formato == 'Box Set CD') ? 'selected' : ''; ?>>Box Set CD</option>
                        <option value="CD Digipack" <?php echo (isset($formato) && $formato == 'CD Digipack') ? 'selected' : ''; ?>>CD Digipack</option>
                        <option value="CD Deluxe Edition" <?php echo (isset($formato) && $formato == 'CD Deluxe Edition') ? 'selected' : ''; ?>>CD Deluxe Edition</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" placeholder="Descrivi il tuo vinile, fornisci dettagli sulle condizioni, l'edizione, ecc."><?php echo isset($descrizione) ? htmlspecialchars($descrizione) : ''; ?></textarea>
            </div>
            
            <div class="form-group image-uploads">
                <h3>Immagini (fino a 5)</h3>
                <p class="info-text">Puoi caricare fino a 5 immagini. <strong>La prima immagine selezionata sarà l'immagine di copertina</strong>. Formati supportati: JPG, PNG, GIF. Dimensione massima: 5MB per immagine.</p>
                
                <div class="image-upload-item">
                    <label for="immagini" class="file-input-label">Seleziona fino a 5 immagini</label>
                    
                    <!-- Input file nascosto -->
                    <input type="file" id="immagini" name="immagini[]" accept="image/jpeg, image/png, image/gif" 
                           multiple onchange="previewImages(this)" style="display: none;">
                    
                    <!-- Bottone personalizzato -->
                    <label for="immagini" class="custom-file-button">+ Carica le Foto</label>
                    
                    <div class="previews-container" id="images-preview">
                        <!-- Le anteprime delle immagini verranno mostrate qui -->
                    </div>
                </div>
            </div>
            
            <button type="submit" class="submit-button">PUBBLICA ANNUNCIO</button>
        </form>
    </div>
    
    <script>
        function previewImages(input) {
            var previewContainer = document.getElementById('images-preview');
            previewContainer.innerHTML = ''; // Pulisce le anteprime esistenti
            
            if (input.files && input.files.length > 0) {
                // Limita il numero di file a 5
                var filesToPreview = Math.min(input.files.length, 5);
                
                for (var i = 0; i < filesToPreview; i++) {
                    var reader = new FileReader();
                    var file = input.files[i];
                    
                    (function(file, index) {
                        reader.onload = function(e) {
                            var imgContainer = document.createElement('div');
                            imgContainer.className = 'preview-item';
                            
                            var img = document.createElement('img');
                            img.className = 'image-preview';
                            img.src = e.target.result;
                            img.style.display = 'block';
                            
                            var label = document.createElement('div');
                            label.className = 'preview-label';
                            label.textContent = index === 0 ? 'Copertina' : 'Foto ' + index;
                            
                            if (index === 0) {
                                label.className += ' main-cover-label';
                            }
                            
                            imgContainer.appendChild(img);
                            imgContainer.appendChild(label);
                            previewContainer.appendChild(imgContainer);
                        };
                        
                        reader.readAsDataURL(file);
                    })(file, i);
                }
                
                // Mostra un avviso se sono stati selezionati più di 5 file
                if (input.files.length > 5) {
                    var warning = document.createElement('p');
                    warning.className = 'warning-text';
                    warning.textContent = 'Hai selezionato ' + input.files.length + ' immagini. Verranno utilizzate solo le prime 5.';
                    previewContainer.appendChild(warning);
                }
            }
        }
    </script>
</body>
</html>
