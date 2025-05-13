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
        $targetDir = "uploads/annunci/";
        $uploadedFiles = [];
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        $maxFileSize = 5 * 1024 * 1024; // 5MB max per immagine
        $maxFiles = 5; // Numero massimo di file consentiti
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
            
            // Validate files before processing
            for ($i = 0; $i < $countFiles; $i++) {
                if ($_FILES['immagini']['error'][$i] != 0 && $_FILES['immagini']['error'][$i] != 4) {
                    $uploadSuccess = false;
                    $errorMsg = "Errore nel caricamento dell'immagine " . ($i + 1) . ": " . getUploadError($_FILES['immagini']['error'][$i]);
                    break;
                }
                
                if ($_FILES['immagini']['size'][$i] > $maxFileSize) {
                    $uploadSuccess = false;
                    $errorMsg = "L'immagine " . ($i + 1) . " supera la dimensione massima di 5MB";
                    break;
                }
                
                $fileType = strtolower(pathinfo($_FILES['immagini']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($fileType, $allowTypes)) {
                    $uploadSuccess = false;
                    $errorMsg = "Tipo file non supportato per l'immagine " . ($i + 1) . ". Sono supportati solo JPG, JPEG, PNG e GIF.";
                    break;
                }
            }
            
            // If validation passed, process uploads
            if ($uploadSuccess) {
                for ($i = 0; $i < $countFiles; $i++) {
                    if ($_FILES['immagini']['error'][$i] == 0) {
                        $fileName = uniqid('img_') . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '_', basename($_FILES['immagini']['name'][$i]));
                        $targetFilePath = $targetDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['immagini']['tmp_name'][$i], $targetFilePath)) {
                            $uploadedFiles[] = $targetFilePath;
                        } else {
                            $uploadSuccess = false;
                            $errorMsg = "Impossibile caricare l'immagine " . ($i + 1);
                            // Cleanup any already uploaded files
                            foreach ($uploadedFiles as $file) {
                                if (file_exists($file)) {
                                    unlink($file);
                                }
                            }
                            $uploadedFiles = [];
                            break;
                        }
                    }
                }
            }
        }
        
        // If no images were uploaded, show error
        if (empty($uploadedFiles)) {
            $uploadSuccess = false;
            $errorMsg = "È richiesta almeno un'immagine per l'annuncio";
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
        
        .form-group input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-container {
            position: relative;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .file-upload-container.highlight {
            border-color: #bb1e10;
            background-color: #fef0ef;
        }
        
        .upload-prompt {
            text-align: center;
        }
        
        .upload-icon {
            font-size: 36px;
            margin-bottom: 10px;
            display: block;
        }
        
        .upload-prompt p {
            margin: 5px 0;
            color: #555;
        }
        
        .upload-prompt small {
            color: #888;
            font-size: 12px;
        }
        
        .images-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 2px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .preview-item.main-image {
            border-color: #bb1e10;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            background: rgba(0, 0, 0, 0.6);
            padding: 5px;
            justify-content: center;
            gap: 5px;
        }
        
        .preview-actions button {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 3px;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            padding: 0;
        }
        
        .preview-actions button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .preview-actions button:hover:not(:disabled) {
            background: #fff;
        }
        
        .main-label {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #bb1e10;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 3px;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
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
            
            <div class="form-group">
                <label for="immagini" class="required">Immagini (massimo 1)</label>
                <div class="file-upload-container" id="drop-area">
                    <input type="file" id="immagini" name="immagini[]" multiple accept="image/jpeg, image/png, image/jpg, image/gif" onchange="previewImages(this)">
                    <div class="upload-prompt">
                        <i class="upload-icon">📁</i>
                        <p>Trascina qui L'immagine di copertina</p>
                        <small>Massimo 1 immagini (JPG, PNG, GIF).</small>
                    </div>
                </div>
                <div id="images-preview" class="images-preview"></div>
                <div id="file-errors" class="error-text"></div>
            </div>
            
            <button type="submit" class="submit-button">PUBBLICA ANNUNCIO</button>
        </form>
    </div>
    
    <script>
        // Gestione drag & drop
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('immagini');
        const previewContainer = document.getElementById('images-preview');
        const errorContainer = document.getElementById('file-errors');
        const maxFiles = 5;
        let files = [];

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropArea.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
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
