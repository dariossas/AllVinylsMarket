<?php
// Include functions file
require_once 'functions.php';

// Get user state
$isLoggedIn = isLoggedIn();
$userId = getCurrentUserId();

// Get favorites if user is logged in
$favorites = $isLoggedIn ? getFavoriteListings($conn, $userId) : array();

// Define mapCondizione function
function mapCondizione($condizione) {
    $map = [
        'nuovo_pellicola' => 'Nuovo con pellicola',
        'nuovo' => 'Nuovo',
        'buone' => 'Buone Condizioni',
        'usato' => 'Usato',
        'molto_usato' => 'Molto usato'
    ];
    
    return isset($map[$condizione]) ? $map[$condizione] : $condizione;
}

// Get filters from URL parameters

$condizione = isset($_GET['condizione']) ? $_GET['condizione'] : '';
$maxPrice = isset($_GET['max_price']) ? intval($_GET['max_price']) : 1000;
$query = isset($_GET['q']) ? $_GET['q'] : '';

// Build query based on filters
$whereConditions = array();
$params = array();
$types = '';

if (!empty($categoria)) {
    $whereConditions[] = "LOWER(categoria) = LOWER(?)";
    $params[] = $categoria;
    $types .= 's';
}

if (!empty($tipo)) {
    $whereConditions[] = "LOWER(formato) = LOWER(?)";
    $params[] = $tipo;
    $types .= 's';
}

if (!empty($condizione)) {
    $whereConditions[] = "LOWER(condizioni) = LOWER(?)";
    $params[] = $condizione;
    $types .= 's';
}

if ($maxPrice > 0) {
    $whereConditions[] = "prezzo <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

if (!empty($query)) {
    $whereConditions[] = "(LOWER(titolo) LIKE LOWER(?) OR LOWER(artista) LIKE LOWER(?) OR LOWER(descrizione) LIKE LOWER(?))";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

// Function to get filtered listings
function getFilteredListings($conn, $whereConditions, $params, $types) {
    $listings = array();
    
    $sql = "SELECT * FROM ANNUNCI";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Check for sort parameter
    if (isset($_GET['sort'])) {
        switch ($_GET['sort']) {
            case 'price_asc':
                $sql .= " ORDER BY prezzo ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY prezzo DESC";
                break;
            default:
                $sql .= " ORDER BY data_caricamento DESC";
                break;
        }
    } else {
        $sql .= " ORDER BY data_caricamento DESC";
    }
    
    $stmt = $conn->prepare($sql);
    
    // Verifica se la preparazione della query è riuscita
    if ($stmt === false) {
        // Gestione dell'errore
        echo "<div class='debug-error'>Errore nella preparazione della query: " . $conn->error . "</div>";
        return $listings; // Restituisce array vuoto in caso di errore
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    // Verifica se l'esecuzione è riuscita
    if ($stmt->error) {
        echo "<div class='debug-error'>Errore nell'esecuzione della query: " . $stmt->error . "</div>";
    }
    
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }
    
    $stmt->close();
    
    return $listings;
}

// Function to get filter title
function getFilterTitle($categoria, $tipo, $condizione) {
    $title = "Risultati";
    
    if (!empty($categoria)) {
        $title .= " - " . ucfirst($categoria);
        
        if (!empty($tipo)) {
            // Convert tipo with underscores to spaces for display
            $tipoDisplay = str_replace('_', ' ', $tipo);
            $title .= " ($tipoDisplay)";
        }
    }
    
    if (!empty($condizione)) {
        $condizioneTxt = mapCondizione($condizione);
        $title .= " - $condizioneTxt";
    }
    
    return $title;
}

// Get active filters for display
function getActiveFilters($categoria, $tipo, $condizione, $maxPrice, $query) {
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
    
    if (!empty($query)) {
        $filters['Ricerca'] = '"' . $query . '"';
    }
    
    return $filters;
}

// Debug function to show SQL query with parameters
function debugQuery($whereConditions, $params) {
    $sql = "SELECT * FROM ANNUNCI";
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY data_caricamento DESC";
    
    // Replace ? with actual parameters
    $i = 0;
    $debugSql = $sql;
    while (strpos($debugSql, '?') !== false && isset($params[$i])) {
        $replacement = is_numeric($params[$i]) ? $params[$i] : "'" . $params[$i] . "'";
        $pos = strpos($debugSql, '?');
        $debugSql = substr_replace($debugSql, $replacement, $pos, 1);
        $i++;
    }
    
    return $debugSql;
}

// Get listings based on filters
$filteredListings = getFilteredListings($conn, $whereConditions, $params, $types);
$activeFilters = getActiveFilters( $condizione, $maxPrice, $query);
$filterTitle = getFilterTitle($condizione);

// Generate debug SQL
$debugSql = debugQuery($whereConditions, $params);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($filterTitle); ?> - AllVinylsMarket</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Stili per il menu di navigazione stile Vinted */
        .navigation-menu {
            background-color: #fff;
            border-bottom: 1px solid #e5e5e5;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .menu-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 15px;
        }
        
        .category-dropdown {
            position: relative;
            margin-right: 5px;
        }
        
        .category-btn {
            padding: 8px 14px;
            border: none;
            background-color: transparent;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .category-btn:hover {
            background-color: #f5f5f5;
        }
        
        .category-btn:after {
            content: '▼';
            margin-left: 5px;
            font-size: 8px;
            color: #888;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 220px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 8px;
            padding: 10px 0;
            left: 0;
            top: 100%;
            margin-top: 5px;
        }
        
        .category-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .dropdown-content a:hover {
            background-color: #f8f8f8;
        }
        
        .filter-divider {
            height: 24px;
            width: 1px;
            background-color: #e5e5e5;
            margin: 0 5px;
        }
        
        .price-filter {
            display: flex;
            align-items: center;
            margin-left: auto;
            background-color: #f8f8f8;
            border-radius: 25px;
            padding: 5px 15px;
        }
        
        .price-filter label {
            margin-right: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }
        
        .price-filter input[type="range"] {
            width: 150px;
            accent-color: #bb1e10;
        }
        
        .price-display {
            margin-left: 10px;
            font-weight: 600;
            min-width: 90px;
            color: #333;
            font-size: 14px;
        }
        
        .apply-filter {
            background-color: #bb1e10;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 25px;
            margin-left: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background-color 0.2s;
        }
        
        .apply-filter:hover {
            background-color: #a01a0e;
        }
        
        /* Stile per la pagina dei risultati */
        .results-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .results-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        
        .results-count {
            color: #666;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .filter-tag {
            background-color: #f0f0f0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .filter-tag span {
            font-weight: 600;
            margin-right: 5px;
        }
        
        .clear-filters {
            margin-left: 10px;
            color: #bb1e10;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
        }
        
        .sort-options select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }
        
        .empty-results {
            grid-column: span 5;
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .empty-results h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-results p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .empty-results .suggestion {
            font-size: 14px;
            color: #888;
        }
        
        /* Debug styles */
        .debug-section {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
        }
        
        .debug-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .debug-sql {
            background-color: #333;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        .debug-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        /* Responsive menu */
        @media (max-width: 768px) {
            .menu-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .price-filter {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-divider {
                display: none;
            }
            
            .results-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .empty-results {
                grid-column: span 3;
            }
        }
        
        @media (max-width: 576px) {
            .results-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .empty-results {
                grid-column: span 2;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .sort-options {
                width: 100%;
                justify-content: flex-end;
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
               <b> <input type="text" name="q" placeholder="🔍 Cerca prodotti" value="<?php echo htmlspecialchars($query); ?>"> </b>
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
    
    
            
         
            
            <!-- Categoria CONDIZIONI -->
            <div class="category-dropdown">
                <div class="category-btn">CONDIZIONI</div>
                <div class="dropdown-content">
                    <a href="risultati.php?categoria=<?php echo urlencode($categoria); ?>&tipo=<?php echo urlencode($tipo); ?>&condizione=nuovo_pellicola&max_price=<?php echo $maxPrice; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?>">Nuovo con pellicola</a>
                    <a href="risultati.php?categoria=<?php echo urlencode($categoria); ?>&tipo=<?php echo urlencode($tipo); ?>&condizione=nuovo&max_price=<?php echo $maxPrice; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?>">Nuovo</a>
                    <a href="risultati.php?categoria=<?php echo urlencode($categoria); ?>&tipo=<?php echo urlencode($tipo); ?>&condizione=buone&max_price=<?php echo $maxPrice; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?>">Buone Condizioni</a>
                    <a href="risultati.php?categoria=<?php echo urlencode($categoria); ?>&tipo=<?php echo urlencode($tipo); ?>&condizione=usato&max_price=<?php echo $maxPrice; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?>">Usato</a>
                    <a href="risultati.php?categoria=<?php echo urlencode($categoria); ?>&tipo=<?php echo urlencode($tipo); ?>&condizione=molto_usato&max_price=<?php echo $maxPrice; ?><?php echo !empty($query) ? '&q=' . urlencode($query) : ''; ?>">Molto usato</a>
                    </div>
            </div>
            
            <!-- Filtro prezzo -->
            <form action="risultati.php" method="GET" class="price-filter">
                <!-- Mantieni i parametri esistenti quando si applica il filtro prezzo -->
                <?php if (!empty($categoria)): ?>
                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
                <?php endif; ?>
                
                <?php if (!empty($tipo)): ?>
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
                <?php endif; ?>
                
                <?php if (!empty($condizione)): ?>
                    <input type="hidden" name="condizione" value="<?php echo htmlspecialchars($condizione); ?>">
                <?php endif; ?>
                
                <?php if (!empty($query)): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                <?php endif; ?>
                
                <label for="price-range">Prezzo:</label>
                <input type="range" id="price-range" name="max_price" min="1" max="1000" value="<?php echo $maxPrice; ?>" oninput="updatePriceDisplay(this.value)">
                <span class="price-display" id="price-display">1€ - <?php echo $maxPrice; ?>€</span>
                <button type="submit" class="apply-filter">Applica</button>
            </form>
        </div>
    </div>
    
    <div class="results-container">
        <div class="results-header">
            <div>
                <h1 class="results-title"><?php echo htmlspecialchars($filterTitle); ?> <span class="results-count">(<?php echo count($filteredListings); ?>)</span></h1>
                
                <?php if (!empty($activeFilters)): ?>
                    <div class="active-filters">
                        <?php foreach($activeFilters as $key => $value): ?>
                            <div class="filter-tag">
                                <span><?php echo htmlspecialchars($key); ?>:</span> <?php echo htmlspecialchars($value); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="risultati.php" class="clear-filters">Cancella tutti i filtri</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sort-options">
                <form action="" method="GET" id="sort-form">
                    <!-- Mantieni tutti i parametri esistenti quando si cambia l'ordinamento -->
                    <?php if (!empty($categoria)): ?>
                        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($tipo)): ?>
                        <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($condizione)): ?>
                        <input type="hidden" name="condizione" value="<?php echo htmlspecialchars($condizione); ?>">
                    <?php endif; ?>
                    
                    <?php if ($maxPrice < 1000): ?>
                        <input type="hidden" name="max_price" value="<?php echo $maxPrice; ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($query)): ?>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                    <?php endif; ?>
                    
                    <select name="sort" onchange="document.getElementById('sort-form').submit()">
                        <option value="recent" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'recent') ? 'selected' : ''; ?>>Più recenti</option>
                        <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Prezzo crescente</option>
                        <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Prezzo decrescente</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="results-grid">
            <?php if (count($filteredListings) > 0): ?>
                <?php foreach ($filteredListings as $listing): ?>
                    <div class="vinyl-item">
                        <a href="annuncio.php?id=<?php echo $listing['id_annuncio']; ?>">
                            <img src="<?php echo $listing['immagine_copertina']; ?>" alt="<?php echo htmlspecialchars($listing['titolo']); ?>" onerror="this.src='https://via.placeholder.com/150x150'"/>
                            <h4><?php echo htmlspecialchars($listing['titolo']); ?></h4>
                            <p>Di: <?php echo htmlspecialchars($listing['artista']); ?></p>
                            <p><?php echo ucfirst(htmlspecialchars($listing['categoria'])); ?>, <?php echo htmlspecialchars($listing['formato']); ?></p>
                            <p class="price">€<?php echo number_format($listing['prezzo'], 2, ',', '.'); ?></p>
                            
                            <?php if ($isLoggedIn): ?>
                                <div class="favorite-btn" data-id="<?php echo $listing['id_annuncio']; ?>" onclick="toggleFavorite(event, this, <?php echo $listing['id_annuncio']; ?>)">
                                    <?php if (in_array($listing['id_annuncio'], array_column($favorites, 'id_annuncio'))): ?>
                                        <span class="favorite-icon filled">❤️</span>
                                    <?php else: ?>
                                        <span class="favorite-icon">🤍</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-results">
                    <h3>Nessun risultato trovato</h3>
                    <p>Prova a modificare i filtri o a effettuare una nuova ricerca.</p>
                    <div class="suggestion">
                        Suggerimenti:
                        <ul>
                            <li>Controlla l'ortografia delle parole chiave</li>
                            <li>Utilizza termini più generici</li>
                            <li>Riduci il numero di filtri applicati</li>
                            <li>Aumenta l'intervallo di prezzo</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div class="debug-section">
        <div class="debug-title">Debug SQL:</div>
        <div class="debug-sql"><?php echo $debugSql; ?></div>
    </div>
    <?php endif; ?>
    
    <script>
        // Funzione per aggiornare il display del prezzo
        function updatePriceDisplay(value) {
            document.getElementById('price-display').textContent = '1€ - ' + value + '€';
        }
        
        // Funzione per gestire i preferiti
        function toggleFavorite(event, element, listingId) {
            event.preventDefault();
            event.stopPropagation();
            
            // Send AJAX request to add/remove from favorites
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'listing_id=' + listingId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const iconElement = element.querySelector('.favorite-icon');
                    if (data.status === 'added') {
                        iconElement.innerHTML = '❤️';
                        iconElement.classList.add('filled');
                    } else {
                        iconElement.innerHTML = '🤍';
                        iconElement.classList.remove('filled');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
    
    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>