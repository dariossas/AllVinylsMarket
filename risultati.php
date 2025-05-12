<?php
// Include functions file
require_once 'functions.php';

// Database connection check
if (!$conn) {
    die("Connessione al database fallita: " . mysqli_connect_error());
}

// Get user state
$isLoggedIn = isLoggedIn();
$userId = getCurrentUserId();

// Get favorites if user is logged in
$favorites = $isLoggedIn ? getFavoriteListings($conn, $userId) : array();

// Get filters from URL parameters
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$condizione = isset($_GET['condizione']) ? trim($_GET['condizione']) : '';
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Construct the base query
$sql = "SELECT * FROM ANNUNCI WHERE 1=1";
$params = array();
$types = '';

// Apply filters dynamically
$conditions = array();

if (!empty($categoria)) {
    $conditions[] = "categoria = ?";
    $params[] = $categoria;
    $types .= 's';
}

if (!empty($tipo)) {
    $conditions[] = "formato = ?";
    $params[] = $tipo;
    $types .= 's';
}

if (!empty($condizione)) {
    $conditions[] = "condizioni = ?";
    $params[] = mapCondizione($condizione);
    $types .= 's';
}

if ($maxPrice > 0) {
    $conditions[] = "prezzo <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

if (!empty($query)) {
    $conditions[] = "(titolo LIKE ? OR artista LIKE ? OR descrizione LIKE ?)";
    $searchTerm = "%{$query}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Combine conditions
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Always order by most recent
$sql .= " ORDER BY data_caricamento DESC";

// Prepare and execute the query
$filteredListings = array();
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $filteredListings[] = $row;
}

$stmt->close();

// Helper function to map URL condition to actual condition text
function mapCondizione($conditionParam) {
    $conditionMap = [
        'nuovo_pellicola' => 'Nuovo con pellicola',
        'nuovo' => 'Nuovo',
        'buone' => 'Buone Condizioni',
        'usato' => 'Usato',
        'molto_usato' => 'Molto usato'
    ];
    
    return $conditionMap[$conditionParam] ?? $conditionParam;
}

// Function to get filter title
function getFilterTitle($categoria, $tipo, $condizione) {
    $title = "Risultati";
    
    if (!empty($categoria)) {
        $title .= " - " . ucfirst($categoria);
        
        if (!empty($tipo)) {
            $title .= " ($tipo)";
        }
    }
    
    if (!empty($condizione)) {
        $condizioneTxt = mapCondizione($condizione);
        $title .= " - $condizioneTxt";
    }
    
    return $title;
}

// Get active filters
function getActiveFilters($categoria, $tipo, $condizione, $maxPrice) {
    $filters = array();
    
    if (!empty($categoria)) {
        $filters['Categoria'] = ucfirst($categoria);
    }
    
    if (!empty($tipo)) {
        $filters['Tipo'] = ucfirst(str_replace('_', ' ', $tipo));
    }
    
    if (!empty($condizione)) {
        $filters['Condizione'] = mapCondizione($condizione);
    }
    
    if ($maxPrice < 1000) {
        $filters['Prezzo massimo'] = $maxPrice . '€';
    }
    
    return $filters;
}

$activeFilters = getActiveFilters($categoria, $tipo, $condizione, $maxPrice);
$filterTitle = getFilterTitle($categoria, $tipo, $condizione);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($filterTitle); ?> - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .esplora-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-top: 20px;
        }
        
        .vinyl-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .vinyl-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .vinyl-item img {
            max-width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .vinyl-item h4 {
            margin: 10px 0;
            font-size: 16px;
        }
        
        .vinyl-item .price {
            font-weight: bold;
            color: #bb1e10;
        }
        
        .empty-results {
            grid-column: span 5;
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
        }

        .category-dropdown {
            position: relative;
            display: inline-block;
        }

        .category-btn {
            background-color: #f1f1f1;
            color: black;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .category-dropdown:hover .dropdown-content {
            display: block;
        }

        .price-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .active-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .filter-tag {
            background-color: #f1f1f1;
            padding: 5px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clear-filters {
            color: #bb1e10;
            text-decoration: none;
            margin-left: 10px;
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
                <b><input type="text" name="q" placeholder="🔍 Cerca prodotti" value="<?php echo htmlspecialchars($query); ?>"></b>
            </form>
        </div>
        <div class="icons">
            <?php if ($isLoggedIn): ?>
                <a href="messaggi.php" class="icon">📧</a>
                <a href="preferiti.php" class="icon">❤️</a>
                <a href="profilo.php" class="icon">👤</a>
            <?php else: ?>
                <a href="login.php" class="login-button">Accedi | Iscriviti</a>
            <?php endif; ?>
        </div>
        <div class="info-button">
            <a href="comefunziona.html"> ? </a>
        </div>
    </header>
    
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
    
    <form action="risultati.php" method="GET" class="price-filter">
        <label for="price-range">Prezzo:</label>
        <input type="range" id="price-range" name="max_price" min="1" max="1000" value="<?php echo $maxPrice; ?>" oninput="updatePriceDisplay(this.value)">
        <span class="price-display" id="price-display">1€ - <?php echo $maxPrice; ?>€</span>
        <button type="submit" class="apply-filter">Applica</button>
    </form>

    <div class="results-container">
        <div class="results-header">
            <h1 class="results-title">
                <?php echo htmlspecialchars($filterTitle); ?>
                <span class="results-count">(<?php echo count($filteredListings); ?> risultati)</span>
            </h1>
        </div>
        
        <?php if (!empty($activeFilters)): ?>
            <div class="active-filters">
                <?php foreach ($activeFilters as $label => $value): ?>
                    <div class="filter-tag">
                        <span><?php echo htmlspecialchars($label); ?>:</span> 
                        <?php echo htmlspecialchars($value); ?>
                    </div>
                <?php endforeach; ?>
                <a href="risultati.php" class="clear-filters">Cancella filtri</a>
            </div>
        <?php endif; ?>
        
        <div class="esplora-grid">
            <?php if (!empty($filteredListings)): ?>
                <?php foreach ($filteredListings as $vinyl): ?>
                    <div class="vinyl-item">
                        <a href="annuncio.php?id=<?php echo $vinyl['id_annuncio']; ?>">
                            <img src="<?php echo htmlspecialchars($vinyl['immagine_copertina']); ?>" 
                                 alt="<?php echo htmlspecialchars($vinyl['titolo']); ?>" 
                                 onerror="this.src='https://via.placeholder.com/250x250'"/>
                            <h4><?php echo htmlspecialchars($vinyl['titolo']); ?></h4>
                            <p>Di: <?php echo htmlspecialchars($vinyl['artista']); ?></p>
                            <p>Vinile, <?php echo htmlspecialchars($vinyl['formato']); ?></p>
                            <p class="price">€<?php echo number_format($vinyl['prezzo'], 2, ',', '.'); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-results">
                    <h3>Nessun risultato trovato</h3>
                    <p>Non ci sono articoli che corrispondono ai tuoi criteri di ricerca.</p>
                    <p class="suggestion">Prova a modificare o rimuovere alcuni filtri.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updatePriceDisplay(value) {
            document.getElementById('price-display').textContent = '1€ - ' + value + '€';
        }
    </script>
</body>
</html>
