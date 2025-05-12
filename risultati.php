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
        /* Container per i filtri */
        .filtri-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background-color: #f8f8f8;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Stile dropdown categorie */
        .category-dropdown {
            position: relative;
            display: inline-block;
        }

        .category-btn {
            background-color: #fff;
            color: #333;
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-btn:hover {
            background-color: #f1f1f1;
            border-color: #bb1e10;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 10;
            border-radius: 8px;
            margin-top: 5px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s ease;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: #bb1e10;
        }

        .category-dropdown:hover .dropdown-content {
            display: block;
        }

        /* Stile filtro prezzo */
        .price-filter {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-grow: 1;
            max-width: 400px;
            background-color: #fff;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid #ddd;
        }

        .price-filter input[type="range"] {
            flex-grow: 1;
            accent-color: #bb1e10;
        }

        .price-display {
            min-width: 100px;
            text-align: center;
            font-weight: bold;
        }

        .apply-filter {
            background-color: #bb1e10;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .apply-filter:hover {
            background-color: #a01a0e;
        }

        /* Stile tag filtri attivi */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .filter-tag {
            background-color: #f1f1f1;
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            border: 1px solid #ddd;
        }

        .filter-tag span {
            font-weight: bold;
            color: #666;
        }

        .clear-filters {
            color: #bb1e10;
            text-decoration: none;
            margin-left: 10px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .clear-filters:hover {
            color: #a01a0e;
            text-decoration: underline;
        }

        /* Stile risultati */
        .results-container {
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f1f1;
        }

        .results-title {
            font-size: 22px;
            color: #333;
            font-weight: 600;
        }

        .results-count {
            font-size: 16px;
            color: #666;
            font-weight: normal;
            margin-left: 8px;
        }

        /* Stile griglia articoli */
        .esplora-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .vinyl-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #fff;
            overflow: hidden;
        }
        
        .vinyl-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .vinyl-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .vinyl-item img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        
        .vinyl-item:hover img {
            transform: scale(1.05);
        }
        
        .vinyl-item h4 {
            margin: 10px 0 5px;
            font-size: 16px;
            color: #333;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .vinyl-item p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .vinyl-item .price {
            font-weight: bold;
            color: #bb1e10;
            font-size: 18px;
            margin-top: 10px;
        }
        
        /* Stile risultati vuoti */
        .empty-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            background-color: #f9f9f9;
            border-radius: 10px;
            border: 1px dashed #ddd;
        }
        
        .empty-results h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-results p {
            color: #888;
            margin-bottom: 5px;
        }
        
        .suggestion {
            color: #bb1e10;
            font-weight: bold;
            margin-top: 15px;
        }

        /* Media query per responsive design */
        @media (max-width: 768px) {
            .esplora-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filtri-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .price-filter {
                max-width: 100%;
            }
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
    
    <div class="results-container">
        <div class="results-header">
            <h1 class="results-title">
                <?php echo htmlspecialchars($filterTitle); ?>
                <span class="results-count">(<?php echo count($filteredListings); ?> risultati)</span>
            </h1>
        </div>
        
        <div class="filtri-container">
            <div class="category-dropdown">
                <div class="category-btn">Condizioni</div>
                <div class="dropdown-content">
                    <a href="risultati.php?condizione=nuovo_pellicola<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($categoria) ? '&categoria='.urlencode($categoria) : ''; ?><?php echo !empty($tipo) ? '&tipo='.urlencode($tipo) : ''; ?>">Nuovo con pellicola</a>
                    <a href="risultati.php?condizione=nuovo<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($categoria) ? '&categoria='.urlencode($categoria) : ''; ?><?php echo !empty($tipo) ? '&tipo='.urlencode($tipo) : ''; ?>">Nuovo</a>
                    <a href="risultati.php?condizione=buone<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($categoria) ? '&categoria='.urlencode($categoria) : ''; ?><?php echo !empty($tipo) ? '&tipo='.urlencode($tipo) : ''; ?>">Buone Condizioni</a>
                    <a href="risultati.php?condizione=usato<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($categoria) ? '&categoria='.urlencode($categoria) : ''; ?><?php echo !empty($tipo) ? '&tipo='.urlencode($tipo) : ''; ?>">Usato</a>
                    <a href="risultati.php?condizione=molto_usato<?php echo !empty($query) ? '&q='.urlencode($query) : ''; ?><?php echo !empty($categoria) ? '&categoria='.urlencode($categoria) : ''; ?><?php echo !empty($tipo) ? '&tipo='.urlencode($tipo) : ''; ?>">Molto usato</a>
                </div>
            </div>
            
            <form action="risultati.php" method="GET" class="price-filter">
                <?php if (!empty($query)): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                <?php endif; ?>
                <?php if (!empty($categoria)): ?>
                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
                <?php endif; ?>
                <?php if (!empty($tipo)): ?>
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
                <?php endif; ?>
                <?php if (!empty($condizione)): ?>
                    <input type="hidden" name="condizione" value="<?php echo htmlspecialchars($condizione); ?>">
                <?php endif; ?>
                <label for="price-range">Prezzo:</label>
                <input type="range" id="price-range" name="max_price" min="1" max="1000" value="<?php echo $maxPrice; ?>" oninput="updatePriceDisplay(this.value)">
                <span class="price-display" id="price-display">1€ - <?php echo $maxPrice; ?>€</span>
                <button type="submit" class="apply-filter">Applica</button>
            </form>
        </div>
        
        <?php if (!empty($activeFilters)): ?>
            <div class="active-filters">
                <span>Filtri attivi:</span>
                <?php foreach ($activeFilters as $label => $value): ?>
                    <div class="filter-tag">
                        <span><?php echo htmlspecialchars($label); ?>:</span> 
                        <?php echo htmlspecialchars($value); ?>
                    </div>
                <?php endforeach; ?>
                <a href="risultati.php<?php echo !empty($query) ? '?q='.urlencode($query) : ''; ?>" class="clear-filters">Cancella filtri</a>
            </div>
        <?php endif; ?>
        
        <div class="esplora-grid">
            <?php if (!empty($filteredListings)): ?>
                <?php foreach ($filteredListings as $vinyl): ?>
                    <div class="vinyl-item">
                        <a href="annuncio.php?id=<?php echo $vinyl['id_annuncio']; ?>">
                            <img src="<?php echo htmlspecialchars($vinyl['immagine_copertina']); ?>" 
                                 alt="<?php echo htmlspecialchars($vinyl['titolo']); ?>" 
                                 onerror="this.src='https://via.placeholder.com/220x220'"/>
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
        // Funzione per aggiornare il display del prezzo
        function updatePriceDisplay(value) {
            document.getElementById('price-display').textContent = '1€ - ' + value + '€';
        }
    </script>
</body>
</html>
