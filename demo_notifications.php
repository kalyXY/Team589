<?php
/**
 * Page de démonstration du système de notifications
 * Teste toutes les fonctionnalités du système de notifications toast
 */

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = 'demo';
$pageTitle = 'Démonstration Notifications';
$showSidebar = true;

ob_start();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-bell"></i> Démonstration du Système de Notifications</h2>
            
            <div class="card shadow-lg">
                <div class="card-body">
                    <h5 class="card-title">Test des différents types de notifications</h5>
                    <p class="text-muted">Cliquez sur les boutons ci-dessous pour tester les notifications toast avec animations.</p>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <button class="btn btn-success w-100" onclick="showSuccess('Opération réussie ! L\'utilisateur a été créé avec succès.')">
                                <i class="fas fa-check-circle"></i> Succès
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-danger w-100" onclick="showError('Erreur critique ! Impossible de supprimer l\'utilisateur.')">
                                <i class="fas fa-exclamation-triangle"></i> Erreur
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning w-100" onclick="showWarning('Attention ! Le stock est faible pour cet article.')">
                                <i class="fas fa-exclamation-circle"></i> Avertissement
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info w-100" onclick="showInfo('Information : La sauvegarde automatique est activée.')">
                                <i class="fas fa-info-circle"></i> Information
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>Test des durées personnalisées</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100" onclick="showNotification('Notification courte (2s)', 'success', 2000)">
                                <i class="fas fa-clock"></i> 2 secondes
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100" onclick="showNotification('Notification longue (10s)', 'info', 10000)">
                                <i class="fas fa-clock"></i> 10 secondes
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100" onclick="showNotification('Notification permanente', 'warning', 0)">
                                <i class="fas fa-infinity"></i> Permanente
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>Test des notifications multiples</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-outline-secondary w-100" onclick="showMultipleNotifications()">
                                <i class="fas fa-layer-group"></i> Notifications multiples
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-secondary w-100" onclick="showNotificationSequence()">
                                <i class="fas fa-stream"></i> Séquence de notifications
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>Test des messages longs</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-outline-dark w-100" onclick="showLongMessage()">
                                <i class="fas fa-align-left"></i> Message long
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-dark w-100" onclick="showHTMLMessage()">
                                <i class="fas fa-code"></i> Message avec HTML
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4 shadow-lg">
                <div class="card-body">
                    <h5 class="card-title">Fonctions disponibles</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Fonctions principales :</h6>
                            <ul class="list-unstyled">
                                <li><code>showSuccess(message, duration)</code> - Notification de succès</li>
                                <li><code>showError(message, duration)</code> - Notification d'erreur</li>
                                <li><code>showWarning(message, duration)</code> - Notification d'avertissement</li>
                                <li><code>showInfo(message, duration)</code> - Notification d'information</li>
                                <li><code>showNotification(message, type, duration)</code> - Notification personnalisée</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Types disponibles :</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge bg-success">success</span> - Opération réussie</li>
                                <li><span class="badge bg-danger">danger</span> - Erreur critique</li>
                                <li><span class="badge bg-warning text-dark">warning</span> - Avertissement</li>
                                <li><span class="badge bg-info">info</span> - Information</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonctions de démonstration
function showMultipleNotifications() {
    showSuccess('Première notification - Succès');
    setTimeout(() => showError('Deuxième notification - Erreur'), 500);
    setTimeout(() => showWarning('Troisième notification - Avertissement'), 1000);
    setTimeout(() => showInfo('Quatrième notification - Information'), 1500);
}

function showNotificationSequence() {
    const messages = [
        { type: 'success', message: 'Étape 1 : Connexion établie' },
        { type: 'info', message: 'Étape 2 : Chargement des données' },
        { type: 'warning', message: 'Étape 3 : Validation en cours' },
        { type: 'success', message: 'Étape 4 : Opération terminée' }
    ];
    
    messages.forEach((msg, index) => {
        setTimeout(() => {
            showNotification(msg.message, msg.type, 3000);
        }, index * 1000);
    });
}

function showLongMessage() {
    const longMessage = `Ceci est un message très long pour tester le comportement des notifications toast avec du texte qui s'étend sur plusieurs lignes. 
    
La notification devrait s'adapter automatiquement à la taille du contenu et rester lisible même avec beaucoup de texte.

Cette fonctionnalité est particulièrement utile pour afficher des messages d'erreur détaillés ou des instructions complexes.`;
    
    showInfo(longMessage, 8000);
}

function showHTMLMessage() {
    const htmlMessage = `
        <strong>Message avec formatage HTML</strong><br>
        • Point 1 : <span style="color: #28a745;">Succès</span><br>
        • Point 2 : <span style="color: #dc3545;">Erreur</span><br>
        • Point 3 : <span style="color: #ffc107;">Avertissement</span>
    `;
    
    // Note: Pour des raisons de sécurité, le HTML est échappé par défaut
    showWarning('Le HTML est automatiquement échappé pour la sécurité', 5000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>
