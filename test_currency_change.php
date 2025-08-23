<?php
/**
 * Test complet du changement de devise (€ vers $)
 * Scolaria - Team589
 */

echo "<h1>Test complet du changement de devise - € vers $</h1>";

// 1. Vérification des fichiers PHP
echo "<h2>1. Vérification des fichiers PHP</h2>";
$phpFiles = [
    'admin_depenses.php',
    'dashboard.php',
    'finances.php',
    'reports.php',
    'pos.php',
    'clients.php',
    'alerts.php',
    'stocks.php',
    'invoice.php',
    'reports_export_pdf.php',
    'dashboard_directeur.php',
    'dashboard_caissier.php'
];

$totalEuroPHP = 0;
$totalDollarPHP = 0;

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $euroCount = substr_count($content, '€');
        $dollarCount = substr_count($content, '$');
        $totalEuroPHP += $euroCount;
        $totalDollarPHP += $dollarCount;
        
        $status = $euroCount > 0 ? "❌" : "✅";
        echo "<p>$status <strong>$file</strong> : $euroCount €, $dollarCount $</p>";
    }
}

echo "<p><strong>Total PHP :</strong> $totalEuroPHP €, $totalDollarPHP $</p>";

// 2. Vérification des fichiers JavaScript
echo "<h2>2. Vérification des fichiers JavaScript</h2>";
$jsFiles = [
    'assets/js/admin-depenses.js',
    'assets/js/main.js',
    'assets/js/alerts.js',
    'assets/js/stocks.js',
    'assets/js/pos.js'
];

$totalEuroJS = 0;
$totalDollarJS = 0;

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $eurCount = substr_count($content, "'EUR'");
        $usdCount = substr_count($content, "'USD'");
        $totalEuroJS += $eurCount;
        $totalDollarJS += $usdCount;
        
        $status = $eurCount > 0 ? "❌" : "✅";
        echo "<p>$status <strong>$file</strong> : $eurCount EUR, $usdCount USD</p>";
    }
}

echo "<p><strong>Total JavaScript :</strong> $totalEuroJS EUR, $totalDollarJS USD</p>";

// 3. Vérification spécifique des fonctions formatCurrency
echo "<h2>3. Vérification des fonctions formatCurrency</h2>";

// admin-depenses.js
$adminDepensesJS = file_get_contents('assets/js/admin-depenses.js');
if (preg_match('/function formatCurrency\([^)]*\)\s*\{[^}]*\}/s', $adminDepensesJS, $matches)) {
    $function = $matches[0];
    $usesUSD = strpos($function, "'USD'") !== false;
    $usesEUR = strpos($function, "'EUR'") !== false;
    
    $status = $usesUSD && !$usesEUR ? "✅" : "❌";
    echo "<p>$status <strong>admin-depenses.js</strong> : " . ($usesUSD ? "USD" : "EUR") . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 12px;'>";
    echo htmlspecialchars($function);
    echo "</pre>";
}

// main.js
$mainJS = file_get_contents('assets/js/main.js');
if (preg_match('/formatCurrency\(amount\)\s*\{[^}]*\}/s', $mainJS, $matches)) {
    $function = $matches[0];
    $usesUSD = strpos($function, "'USD'") !== false;
    $usesEUR = strpos($function, "'EUR'") !== false;
    
    $status = $usesUSD && !$usesEUR ? "✅" : "❌";
    echo "<p>$status <strong>main.js</strong> : " . ($usesUSD ? "USD" : "EUR") . "</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 12px;'>";
    echo htmlspecialchars($function);
    echo "</pre>";
}

// 4. Vérification des icônes FontAwesome
echo "<h2>4. Vérification des icônes FontAwesome</h2>";
$faEuroCount = 0;
$faDollarCount = 0;

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $faEuroCount += substr_count($content, 'fa-euro');
        $faDollarCount += substr_count($content, 'fa-dollar');
    }
}

echo "<p><strong>Icônes fa-euro :</strong> $faEuroCount</p>";
echo "<p><strong>Icônes fa-dollar :</strong> $faDollarCount</p>";

// 5. Résumé et instructions
echo "<h2>5. Résumé</h2>";
$allGood = ($totalEuroPHP == 0) && ($totalEuroJS == 0) && ($faEuroCount == 0);

if ($allGood) {
    echo "<p style='color: green; font-size: 18px;'>🎉 <strong>TOUS LES SYMBOLES EURO ONT ÉTÉ REMPLACÉS PAR DES DOLLARS !</strong></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>⚠️ <strong>IL RESTE DES SYMBOLES EURO À CORRIGER</strong></p>";
}

echo "<h2>6. Instructions pour l'utilisateur</h2>";
echo "<p>Si vous voyez encore des symboles € dans l'interface :</p>";
echo "<ol>";
echo "<li><strong>Videz le cache du navigateur</strong> (Ctrl+F5 ou Ctrl+Shift+R)</li>";
echo "<li><strong>Ouvrez les outils de développement</strong> (F12)</li>";
echo "<li><strong>Allez dans l'onglet 'Network'</strong></li>";
echo "<li><strong>Cochez 'Disable cache'</strong></li>";
echo "<li><strong>Rechargez la page</strong></li>";
echo "</ol>";

echo "<h2>7. Test de la fonction JavaScript</h2>";
echo "<p>Dans la console du navigateur (F12), testez :</p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "formatCurrency(1234.56)";
echo "</pre>";
echo "<p>Résultat attendu : <strong>1 234,56 $</strong></p>";

echo "<hr>";
echo "<p><a href='admin_depenses.php' target='_blank'>🔗 Tester admin_depenses.php</a></p>";
echo "<p><a href='dashboard.php' target='_blank'>🔗 Tester dashboard.php</a></p>";
echo "<p><a href='finances.php' target='_blank'>🔗 Tester finances.php</a></p>";
?>
