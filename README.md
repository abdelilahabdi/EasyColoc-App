# EasyColoc - Plateforme de Gestion de Colocation

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11">
    <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2">
    <img src="https://img.shields.io/badge/Tailwind-3.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
    <img src="https://img.shields.io/badge/Status-Production%20Ready-success?style=for-the-badge" alt="Production Ready">
</p>

## 📖 À Propos

**EasyColoc** est une application web moderne de gestion de colocation qui permet de suivre les dépenses communes et de répartir automatiquement les dettes entre membres. L'objectif est d'éviter les calculs manuels et d'avoir une vision claire de "qui doit quoi à qui".

### ✨ Fonctionnalités Principales

- 🏠 **Gestion de Colocations** : Création, invitation de membres, gestion des rôles
- 💰 **Suivi des Dépenses** : Ajout de dépenses avec catégories personnalisables
- 🧮 **Calculs Automatiques** : Balances individuelles et simplification des dettes
- ✅ **Système de Paiements** : Enregistrement des règlements avec modal intuitive
- ⭐ **Réputation** : Système de gamification (+10 points par paiement)
- 📊 **Dashboard Professionnel** : Interface moderne avec Tailwind CSS
- 🔒 **Sécurité Maximale** : CSRF, XSS, SQL Injection, Policies strictes
- 📱 **Responsive Design** : Adapté mobile, tablette et desktop

---

## 🚀 Installation

### Prérequis

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL

### Étapes d'Installation

```bash
# 1. Cloner le repository
git clone https://github.com/votre-username/easycoloc.git
cd easycoloc

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances JavaScript
npm install

# 4. Copier le fichier d'environnement
cp .env.example .env

# 5. Générer la clé d'application
php artisan key:generate

# 6. Configurer la base de données dans .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=easycoloc
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Exécuter les migrations
php artisan migrate

# 8. Compiler les assets
npm run build

# 9. Lancer le serveur de développement
php artisan serve
```

Accédez à l'application sur `http://localhost:8000`

---

## 📚 Documentation

### Fichiers de Documentation

- **[IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md)** : Documentation complète de l'implémentation
- **[VERIFICATION_GUIDE.md](VERIFICATION_GUIDE.md)** : Guide de vérification et déploiement
- **[CHANGELOG.md](CHANGELOG.md)** : Historique des modifications
- **[TESTS_RAPIDES.md](TESTS_RAPIDES.md)** : Tests essentiels à effectuer

### Architecture

**EasyColoc** suit strictement l'architecture **MVC (Model-View-Controller)** de Laravel :

- **Models** : Logique métier et relations Eloquent
- **Controllers** : Orchestration des requêtes
- **Views** : Présentation avec Blade + Tailwind CSS
- **Policies** : Autorisations et contrôle d'accès
- **Services** : Logique métier complexe (BalanceService, ReputationService, etc.)

---

## 🎯 Fonctionnalités Détaillées

### 1. Système de Rôles

- **Global Admin** : Premier utilisateur inscrit, accès aux statistiques et modération
- **Owner** : Créateur de la colocation, gestion complète
- **Member** : Membre standard, peut ajouter des dépenses

### 2. Gestion des Colocations

- Création de colocation (owner automatique)
- Invitation par email avec token unique
- **Restriction** : Une seule colocation active par utilisateur
- Départ d'un membre (left_at)
- Retrait d'un membre par l'owner
- Annulation de la colocation

### 3. Dépenses et Catégories

- Ajout de dépenses (titre, montant, date, catégorie, payeur)
- Catégories personnalisables par colocation
- Historique chronologique avec filtre par mois
- Suppression par owner ou créateur

### 4. Calculs Financiers

#### Algorithme de Calcul des Balances
```php
// Pour chaque membre :
Balance = Total payé - Part équitable

// Avec soustraction des settlements déjà effectués
Balance finale = Balance - Settlements nets
```

#### Simplification des Dettes (Algorithme Greedy)
- Minimise le nombre de transactions
- Complexité : O(n log n)
- Précision : 2 décimales (round)

### 5. Système de Réputation

- **+10 points** : Paiement validé (Settlement)
- **+1 point** : Départ sans dette
- **-1 point** : Départ avec dette
- Badge coloré selon le score :
  - 🟢 Vert : ≥ 50 points
  - 🔵 Bleu : ≥ 20 points
  - ⚪ Gris : < 20 points

### 6. Interface Utilisateur

#### Dashboard Principal (8 Cards)

1. **Membres & Réputation** : Liste des membres avec badge étoile
2. **Bilan Financier** : Total payé, Part équitable, Solde pour chaque membre
3. **Qui doit quoi à qui** : Vue simplifiée des dettes
4. **Historique des dépenses** : Avec filtre par mois
5. **Ajouter une dépense** : Formulaire avec validation
6. **Inviter un membre** : Par email (owner uniquement)
7. **Marquer comme payé** : Modal pour enregistrer les settlements
8. **Gestion des catégories** : CRUD catégories (owner uniquement)

---

## 🔒 Sécurité

### Protections Implémentées

- ✅ **CSRF Protection** : Tous les formulaires avec `@csrf`
- ✅ **XSS Protection** : Échappement automatique Blade `{{ }}`
- ✅ **SQL Injection** : Requêtes préparées via Eloquent ORM
- ✅ **Validation Serveur** : Form Requests + méthode `validate()`
- ✅ **Validation Client** : HTML5 (required, type, pattern)
- ✅ **Policies** : Autorisations strictes pour chaque action
- ✅ **Middleware BannedUser** : Déconnexion automatique des utilisateurs bannis

### Policies

- **ColocationPolicy** : view, update, delete, inviteMember, removeMember
- **ExpensePolicy** : create, delete
- **CategoryPolicy** : create, update, delete (avec vérification membership actif)

---

## 🧪 Tests

### Tests Essentiels (5 minutes)

Consultez [TESTS_RAPIDES.md](TESTS_RAPIDES.md) pour la liste complète.

```bash
# Lancer les tests unitaires
php artisan test

# Vérifier les routes
php artisan route:list

# Vérifier les migrations
php artisan migrate:status
```

### Tests Manuels Recommandés

1. ✅ Premier utilisateur = Global Admin
2. ✅ Une seule colocation active
3. ✅ Calculs financiers corrects
4. ✅ Settlements et réputation
5. ✅ Invitations fonctionnelles
6. ✅ Blocage utilisateurs bannis
7. ✅ Responsive design
8. ✅ Sécurité des policies

---

## 📊 Base de Données

### Schéma Principal

```
users
├── id
├── name
├── email
├── password
├── reputation (default: 0)
├── role (enum: 'admin', 'user')
├── is_admin (boolean)
└── is_banned (boolean)

colocations
├── id
├── name
├── owner_id (FK users)
└── status (enum: 'active', 'cancelled')

colocation_user (pivot)
├── colocation_id (FK)
├── user_id (FK)
├── role (enum: 'owner', 'member')
├── joined_at
└── left_at

expenses
├── id
├── colocation_id (FK)
├── payer_id (FK users)
├── category_id (FK)
├── title
├── amount (decimal:2)
└── expense_date

settlements
├── id
├── colocation_id (FK)
├── sender_id (FK users)
├── receiver_id (FK users)
├── amount (decimal:2)
├── settlement_date
└── status
```

---

## 🛠️ Technologies Utilisées

### Backend
- **Laravel 11** : Framework PHP
- **Eloquent ORM** : Gestion de la base de données
- **Laravel Breeze** : Authentification
- **Policies & Gates** : Autorisations

### Frontend
- **Blade** : Moteur de templates
- **Tailwind CSS 3** : Framework CSS
- **JavaScript Natif** : Interactions (modal, filtres)
- **SVG Icons** : Icônes personnalisées

### Base de Données
- **MySQL** / **PostgreSQL**
- **Migrations** : Versionning de la BDD

---

## 📈 Performance

### Optimisations Implémentées

- ✅ **Eager Loading** : `with(['users', 'expenses.category'])` pour éviter N+1
- ✅ **Services en Singleton** : Réutilisation des instances
- ✅ **Algorithme Greedy** : O(n log n) pour simplification des dettes
- ✅ **Caching** : Config, routes et vues en production
- ✅ **Indexes** : Clés étrangères et colonnes fréquemment requêtées

---

## 🚀 Déploiement en Production

Consultez [VERIFICATION_GUIDE.md](VERIFICATION_GUIDE.md) pour le guide complet.

### Commandes Essentielles

```bash
# Optimiser pour la production
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🤝 Contribution

### Workflow Git

```bash
# Créer une branche pour votre fonctionnalité
git checkout -b feature/ma-fonctionnalite

# Faire vos modifications
git add .
git commit -m "feat: description de la fonctionnalité"

# Pousser vers le repository
git push origin feature/ma-fonctionnalite

# Créer une Pull Request
```

### Conventions de Commits

- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `docs:` Documentation
- `style:` Formatage du code
- `refactor:` Refactorisation
- `test:` Ajout de tests
- `chore:` Tâches de maintenance

---

## 📝 Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📞 Support

### Documentation
- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Tailwind CSS](https://tailwindcss.com/docs)
- [IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md)

### Contact
Pour toute question ou suggestion, ouvrez une issue sur GitHub.

---

## 🎉 Remerciements

- **Laravel** pour le framework exceptionnel
- **Tailwind CSS** pour le système de design
- **Communauté Open Source** pour les contributions

---

<p align="center">
    <strong>EasyColoc - Gérez votre colocation en toute simplicité ! 🏠💰</strong>
</p>

<p align="center">
    <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=for-the-badge" alt="Version 1.0.0">
    <img src="https://img.shields.io/badge/Conformit%C3%A9-100%25-success?style=for-the-badge" alt="Conformité 100%">
    <img src="https://img.shields.io/badge/Production-Ready-brightgreen?style=for-the-badge" alt="Production Ready">
</p>
