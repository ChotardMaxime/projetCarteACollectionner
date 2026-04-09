# Carte à Collectionner

A static front-end mockup for a Pokémon card collection website. Three pages built in pure HTML/CSS — no JavaScript, no backend. Cards are hardcoded for now; the project includes a database schema (`.drawio`) designed for a future PHP/MySQL backend.

---

## Pages

| Page | File | Description |
|------|------|-------------|
| Catalog | `index.html` | Grid of all cards with a filter dropdown |
| Profile | `profil.html` | User info block + personal card collection |
| Management | `gestion.html` | Form to add or delete a card (UI only) |

---

## Installation

No installation needed — it's static HTML.

1. Clone or copy the project folder anywhere.
2. Open `index.html` in your browser.

The only external dependency is Google Fonts (Cinzel + Lato), loaded via CDN in each page's `<head>`. It requires an internet connection to render correctly, but the layout won't break without it — it'll just fall back to serif/sans-serif.

Put your card images in an `img/` folder at the root. The current cards expect files named `dracaufeu.png`, `mewtwo.png`, `pikachu.png`, etc.

---

## Project structure

```
/
├── index.html          # Card catalog (main page)
├── profil.html         # User profile + personal collection
├── gestion.html        # Add / delete card form
├── style.css           # Single stylesheet for all pages
├── img/                # Card images (not included)
│   ├── dracaufeu.png
│   ├── mewtwo.png
│   └── ...
└── bdd_carteacollectionner.drawio   # DB schema for future backend
```

---

## Code overview

### CSS variables

All colors, fonts, and reusable values are defined as custom properties at the top of `style.css` under `:root`. Changing the color scheme means editing ~10 lines there instead of hunting through the whole file.

```css
--couleur_nav_active: #e8a020;
--couleur_card_fond: #ffffff;
--font_titre: 'Cinzel', serif;
```

### Card grid

The catalog uses CSS Grid with `repeat(4, 1fr)` — four columns by default, dropping to two on screens under 768px via a media query. Each `.card` has a hover effect: a subtle `translateY(-4px)` lift with a box shadow, and a `scale(1.05)` zoom on the card image.

### Active nav state

The current page is highlighted in the navbar by adding an `.actif` class directly on the nav `<div>` in the HTML. The CSS then targets `.voir_les_cartes.actif a`, `.mon_profil.actif a`, etc. to apply the orange color. Since there's no JS, this is handled manually per page.

### Gestion page

The management page is UI-only — the "Ajouter" and "Supprimer" buttons and the form fields have no JavaScript attached. The file upload input is hidden and triggered by a styled `<label>` pointing to it via `for="image"`, which is a common CSS-only trick to style file inputs.

### Database schema

`bdd_carteacollectionner.drawio` contains the relational schema planned for when the project gets a backend. Open it with [draw.io](https://app.diagrams.net/) or the VS Code Draw.io extension.

---

## Possible improvements

- Add JavaScript to make the filter dropdown actually sort/filter cards
- Wire up the gestion form with a PHP backend using the existing `.drawio` schema
- Pull card data dynamically from a DB instead of hardcoding each `.card` block in the HTML
- Add a login page and make the profile page user-specific
