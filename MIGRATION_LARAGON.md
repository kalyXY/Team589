# 🚀 Migration de XAMPP vers Laragon - Scolaria

## 📋 **Prérequis**

1. **Laragon installé** sur votre système
2. **Fichier `sql/scolaria.sql`** présent dans le projet
3. **Projet Scolaria** copié dans le dossier `www` de Laragon

## 🔧 **Étapes de migration**

### **1. Préparation de Laragon**

1. **Ouvrir Laragon**
2. **Cliquer sur "Start All"** pour démarrer Apache et MySQL
3. **Vérifier que les services sont verts** (MySQL et Apache)

### **2. Copier le projet**

1. **Copier le dossier `scolaria`** dans le répertoire `www` de Laragon
2. **Chemin typique :** `C:\laragon\www\scolaria\`

### **3. Import de la base de données**

#### **Option A : Script automatique (Recommandé)**

1. **Ouvrir votre navigateur**
2. **Aller sur :** `http://localhost/scolaria/import_laragon.php`
3. **Suivre les instructions** à l'écran
4. **Cliquer sur "Créer la base de données"**

#### **Option B : Import manuel via phpMyAdmin**

1. **Ouvrir phpMyAdmin :** `http://localhost/phpmyadmin/`
2. **Créer une nouvelle base de données** nommée `scolaria`
3. **Sélectionner la base `scolaria`**
4. **Aller dans l'onglet "Importer"**
5. **Choisir le fichier `sql/scolaria.sql`**
6. **Cliquer sur "Exécuter"**

### **4. Vérification de la configuration**

1. **Tester la connexion :** `http://localhost/scolaria/check_database.php`
2. **Vérifier que toutes les tables sont créées**
3. **Tester l'application :** `http://localhost/scolaria/`

## ⚙️ **Configuration Laragon par défaut**

| Paramètre | Valeur |
|-----------|--------|
| **Hôte** | localhost |
| **Port** | 3306 |
| **Utilisateur** | root |
| **Mot de passe** | (vide) |
| **Base de données** | scolaria |

## 🔍 **Dépannage**

### **Problème : MySQL ne démarre pas**

**Solutions :**
1. **Vérifier qu'aucun autre service MySQL n'est en cours**
2. **Redémarrer Laragon**
3. **Vérifier les logs dans Laragon**

### **Problème : Erreur de connexion à la base**

**Solutions :**
1. **Vérifier que la base `scolaria` existe**
2. **Vérifier les paramètres dans `config/config.php`**
3. **Tester avec le script de diagnostic**

### **Problème : Page non trouvée**

**Solutions :**
1. **Vérifier que le projet est dans `C:\laragon\www\scolaria\`**
2. **Vérifier qu'Apache est démarré**
3. **Vérifier l'URL :** `http://localhost/scolaria/`

## 📁 **Structure des fichiers**

```
C:\laragon\www\scolaria\
├── config\
│   ├── config.php          # Configuration Laragon
│   └── db.php              # Classe de connexion DB
├── sql\
│   └── scolaria.sql        # Base de données
├── import_laragon.php      # Script d'import
├── check_database.php      # Script de diagnostic
└── ... (autres fichiers)
```

## 🎯 **Test final**

1. **Accéder à l'application :** `http://localhost/scolaria/`
2. **Se connecter avec :**
   - **Utilisateur :** admin
   - **Mot de passe :** admin123
3. **Vérifier que toutes les fonctionnalités marchent**

## 🔄 **Migration des données existantes**

Si vous avez des données dans XAMPP que vous voulez migrer :

1. **Exporter la base XAMPP** via phpMyAdmin
2. **Importer dans Laragon** via le script ou phpMyAdmin
3. **Vérifier l'intégrité des données**

## 📞 **Support**

En cas de problème :
1. **Consulter les logs Laragon**
2. **Utiliser le script de diagnostic**
3. **Vérifier la configuration**

---

**🎉 Félicitations ! Votre application Scolaria est maintenant configurée pour Laragon !**
