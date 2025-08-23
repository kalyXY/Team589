<?php
/**
 * Test d'intégration de la fonctionnalité auto-increment dans settings.php
 * Scolaria - Team589
 */

echo "<h1>Test d'intégration - Auto-Increment dans Settings</h1>";

// Vérifier que le fichier settings.php existe
if (!file_exists('settings.php')) {
    echo "<p style='color: red;'>❌ Le fichier settings.php n'existe pas</p>";
    exit;
}

// Vérifier que le contenu a été ajouté
$settingsContent = file_get_contents('settings.php');

$checks = [
    'Onglet Base de données' => strpos($settingsContent, 'database-tab') !== false,
    'Action reset_auto_increment' => strpos($settingsContent, 'reset_auto_increment') !== false,
    'Table auto-increment data' => strpos($settingsContent, '$autoIncrementData') !== false,
    'Bouton réorganiser' => strpos($settingsContent, 'Réorganiser tous les IDs') !== false,
    'Alert warning' => strpos($settingsContent, 'alert-warning') !== false
];

echo "<h2>Vérification des éléments intégrés</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Élément</th>";
echo "<th>Statut</th>";
echo "</tr>";

foreach ($checks as $element => $found) {
    $status = $found ? "✅ Trouvé" : "❌ Manquant";
    $color = $found ? "green" : "red";
    echo "<tr>";
    echo "<td>$element</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Vérifier la structure des onglets
echo "<h2>Structure des onglets</h2>";
$tabs = [
    'school-tab' => 'Informations école',
    'system-tab' => 'Configuration système', 
    'security-tab' => 'Sécurité & utilisateurs',
    'database-tab' => 'Base de données',
    'account-tab' => 'Compte administrateur'
];

echo "<ul>";
foreach ($tabs as $tabId => $tabName) {
    $found = strpos($settingsContent, $tabId) !== false;
    $status = $found ? "✅" : "❌";
    echo "<li>$status <strong>$tabName</strong> (ID: $tabId)</li>";
}
echo "</ul>";

// Instructions pour tester
echo "<h2>Instructions pour tester</h2>";
echo "<ol>";
echo "<li>Connectez-vous en tant qu'administrateur</li>";
echo "<li>Allez dans <strong>Paramètres</strong> (menu Système)</li>";
echo "<li>Cliquez sur l'onglet <strong>Base de données</strong></li>";
echo "<li>Vérifiez que le tableau affiche l'état des auto-increments</li>";
echo "<li>Si des 'trous' sont détectés, cliquez sur <strong>Réorganiser tous les IDs</strong></li>";
echo "</ol>";

echo "<h2>Liens utiles</h2>";
echo "<p><a href='settings.php' target='_blank'>🔗 Ouvrir settings.php</a></p>";
echo "<p><a href='admin_reset_auto_increment.php' target='_blank'>🔗 Page autonome admin_reset_auto_increment.php</a></p>";

echo "<hr>";
echo "<p><strong>Note :</strong> La fonctionnalité est maintenant intégrée dans les paramètres de l'application. Vous pouvez supprimer le fichier <code>admin_reset_auto_increment.php</code> si vous le souhaitez, car tout est maintenant accessible via l'onglet 'Base de données' dans les paramètres.</p>";
?>
