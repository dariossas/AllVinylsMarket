<?php
// login.php - Pagina di accesso per AllVinylsMarket
require_once 'functions.php';

$error = '';
$username = '';

// Verifica se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Controlla che i campi siano stati compilati
    if (empty($username) || empty($password)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        // Prepara e esegui la query
        $sql = "SELECT id_utente, username, password_utente FROM UTENTI WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifica la password (usando password_verify se le password sono hashate)
            if (password_verify($password, $user['password_utente'])) {
                // Password corretta, avvia la sessione
                $_SESSION['id_utente'] = $user['id_utente'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect alla home page
                header("Location: index.php");
                exit();
            } else {
                $error = "Password non corretta.";
            }
        } else {
            $error = "Nome utente o email non trovati.";
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
    <title>Accedi - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Stili specifici per la pagina di login */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            align-items: center;
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
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            object-fit: cover;
        }
        
        .login-form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
        }
        
        .login-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .login-box h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .input-field {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        .login-button {
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
        
        .account-links {
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .account-links a {
            color: #666;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }
        
        .account-links a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #bb1e10;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
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
        
        <div class="login-form-container">
            <div class="login-box">
                <h2>Accedi</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <input type="text" class="input-field" name="username" placeholder="E-mail o username" value="<?php echo htmlspecialchars($username); ?>" required>
                    
                    <input type="password" class="input-field" name="password" placeholder="Password" required>
                    
                    <div class="account-links">
                        <a href="recupero-password.php">non hai un account?</a>
                        <a href="registrazione.php">crea account</a>
                    </div>
                    
                    <button type="submit" class="login-button">Accedi</button>
                </form>
            </div>
        </div>
        
        <footer>
            © 2025 by Business Name. Built on Wix Studio
        </footer>
    </div>
</body>
</html>