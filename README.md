# Carte à Collectionner

A PHP/MySQL web application for managing a Pokémon card collection. Users can log in, browse all available cards, manage their personal collection, and — if admin — add or delete cards from the catalog.

---

## Features

| Feature | Non connecté | Utilisateur | Admin |
|---------|-------------|-------------|-------|
| Browse catalog | ✅ | ✅ | ✅ |
| Filter by value / name / rarity | ✅ | ✅ | ✅ |
| View profile + collection stats | ❌ | ✅ | ✅ |
| Add / remove cards from collection | ❌ | ✅ | ✅ |
| Add / delete cards from catalog | ❌ | ❌ | ✅ |

---

## Test accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | Max | 050127 |
| Utilisateur | Dresseur | 050127 |

---

## Installation

### Requirements

- PHP 8.0+
- MySQL / MariaDB
- A local server: [XAMPP](https://www.apachefriends.org/) or [Laragon](https://laragon.org/) (recommended)

### Setup

1. Clone or copy the project into your web server's root folder (`htdocs/` for XAMPP, `www/` for Laragon).

2. Import the database:
   - Open phpMyAdmin
   - Create a database named `carte_a_collectionner`
   - Import `carte_a_collectionner.sql`

3. Check the DB connection in `includes/db.php`:
   ```php
   $host     = 'localhost';
   $dbname   = 'carte_a_collectionner';
   $user     = 'root';
   $password = '';
   ```
   Adjust if your MySQL credentials differ.

4. Put your card images in an `img/` folder at the root. The SQL data expects files named `dracaufeu.png`, `mewtwo.png`, etc.

5. Open your browser at `http://localhost/carte-a-collectionner/`.

---

## Project structure

```
/
├── index.php           # Card catalog with live filter
├── login.php           # Login form
├── logout.php          # Session destruction + redirect
├── profil.php          # User profile + personal collection management
├── gestion.php         # Add / delete cards from catalog (admin)
├── style.css           # Single stylesheet for all pages
├── img/                # Card images
│   ├── dracaufeu.png
│   └── ...
├── includes/
│   ├── db.php          # PDO connection
│   ├── header.php      # HTML head + nav (session-aware)
│   └── footer.php      # Footer + closing tags
└── carte_a_collectionner.sql   # DB schema + test data
```

---

## Database schema

3 tables: `utilisateur`, `carte`, `inventaire`.

- `utilisateur` — id, nom_profil, mot_de_passe (bcrypt), role, date_creation, photo_url
- `carte` — id, nom, valeur, image_url, rarete (`ENUM`: commune / peu commune / rare / ultra rare)
- `inventaire` — id, id_utilisateur (FK), id_carte (FK), date_acquisition

`inventaire` is the junction table between users and cards. A user can own the same card multiple times — each row is one physical copy.

---

## Code overview

### Auth and session

Login matches by `nom_profil` (not email) and verifies the password with `password_verify()` against a bcrypt hash. On success, `$_SESSION` stores `id_utilisateur`, `nom_profil`, and `role`. `logout.php` does a full cleanup: empties `$_SESSION`, expires the session cookie with `setcookie()`, then calls `session_destroy()`.

### Active nav state

`header.php` is shared across all pages. Each page sets a `$page_active` variable before including it (`$page_active = 'index'`, `'profil'`, `'gestion'`). The header uses this to inject the `.actif` class on the right nav item, keeping the active highlight logic in one place.

### Filter on the catalog

`index.php` reads a `filtre` GET parameter and builds the SQL query dynamically. Rarity ordering uses MySQL's `FIELD()` function to sort by a custom priority (`ultra rare` first down to `commune`) rather than alphabetically.

### Collection management (profil.php)

Adding and removing cards both POST to the same page. The action is determined by a hidden `name="action"` field (`ajouter_collection` vs `supprimer_collection`). After any write, the page redirects to itself (PRG pattern) with a flash message stored in `$_SESSION` to avoid form resubmission on refresh.

When removing a card with duplicates in the collection, the query uses `LIMIT 1` to only remove one copy at a time.

### Image upload (gestion.php)

On card creation, the uploaded file is renamed to a slug based on the card name (`strtolower(str_replace(' ', '_', $nom))`) and saved to `img/`. On card deletion, `unlink()` removes the image file from disk before the DB row is deleted.

---

## Possible improvements

- Restrict `gestion.php` with a role check (currently no server-side guard — only the UI hides the link)
- Add a registration page instead of inserting users directly via SQL
- Paginate the catalog for large card sets
- Add a search bar on the catalog page
- Let users upload a profile picture
