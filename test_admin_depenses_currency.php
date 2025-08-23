<?php
/**
 * Test pour vérifier le changement de devise dans admin_depenses.php
 * Scolaria - Team589
 */

echo "<h1>Test de changement de devise - admin_depenses.php</h1>";

// Vérifier le fichier PHP principal
echo "<h2>1. Vérification du fichier admin_depenses.php</h2>";
$phpContent = file_get_contents('admin_depenses.php');
$euroCount = substr_count($phpContent, '€');
$dollarCount = substr_count($phpContent, '$');
$faEuroCount = substr_count($phpContent, 'fa-euro');
$faDollarCount = substr_count($phpContent, 'fa-dollar');

echo "<p><strong>Symboles € trouvés :</strong> $euroCount</p>";
echo "<p><strong>Symboles $ trouvés :</strong> $dollarCount</p>";
echo "<p><strong>Classes fa-euro trouvées :</strong> $faEuroCount</p>";
echo "<p><strong>Classes fa-dollar trouvées :</strong> $faDollarCount</p>";

if ($euroCount > 0) {
    echo "<p style='color: red;'>❌ Il y a encore des symboles € dans le fichier PHP !</p>";
} else {
    echo "<p style='color: green;'>✅ Aucun symbole € trouvé dans le fichier PHP</p>";
}

// Vérifier le fichier JavaScript
echo "<h2>2. Vérification du fichier JavaScript</h2>";
$jsContent = file_get_contents('assets/js/admin-depenses.js');
$eurCount = substr_count($jsContent, "'EUR'");
$usdCount = substr_count($jsContent, "'USD'");

echo "<p><strong>Occurrences de 'EUR' :</strong> $eurCount</p>";
echo "<p><strong>Occurrences de 'USD' :</strong> $usdCount</p>";

if ($eurCount > 0) {
    echo "<p style='color: red;'>❌ Il y a encore des références à 'EUR' dans le JavaScript !</p>";
} else {
    echo "<p style='color: green;'>✅ Aucune référence à 'EUR' trouvée dans le JavaScript</p>";
}

// Afficher la fonction formatCurrency
echo "<h2>3. Fonction formatCurrency actuelle</h2>";
if (preg_match('/function formatCurrency\([^)]*\)\s*\{[^}]*\}/s', $jsContent, $matches)) {
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($matches[0]);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Fonction formatCurrency non trouvée</p>";
}

// Vérifier les labels dans le HTML
echo "<h2>4. Vérification des labels dans le HTML</h2>";
$labels = [
    'Montant ($)',
    'fa-dollar-sign',
    'Total Dépenses',
    'Ce mois',
    'Cette année'
];

foreach ($labels as $label) {
    if (strpos($phpContent, $label) !== false) {
        echo "<p style='color: green;'>✅ Label trouvé : '$label'</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Label non trouvé : '$label'</p>";
    }
}

// Instructions pour l'utilisateur
echo "<h2>5. Instructions</h2>";
echo "<p>Si vous voyez encore des symboles € dans la page :</p>";
echo "<ol>";
echo "<li><strong>Videz le cache de votre navigateur</strong> (Ctrl+F5 ou Ctrl+Shift+R)</li>";
echo "<li><strong>Ouvrez les outils de développement</strong> (F12) et allez dans l'onglet 'Network'</li>";
echo "<li><strong>Cochez 'Disable cache'</strong> et rechargez la page</li>";
echo "<li><strong>Vérifiez que le fichier admin-depenses.js</strong> est bien chargé avec la nouvelle version</li>";
echo "</ol>";

echo "<h2>6. Test de la fonction JavaScript</h2>";
echo "<p>Ouvrez la console du navigateur (F12) et tapez :</p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "formatCurrency(1234.56)";
echo "</pre>";
echo "<p>Cela devrait afficher : <strong>1 234,56 $</strong> (et non pas €)</p>";

echo "<hr>";
echo "<p><a href='admin_depenses.php' target='_blank'>🔗 Ouvrir admin_depenses.php</a></p>";
echo "<p><a href='assets/js/admin-depenses.js' target='_blank'>🔗 Voir le fichier JavaScript</a></p>";
?>
