<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

// Sécurité : redirection si non connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = (int) $_SESSION['id_utilisateur'];

// Messages flash
$success = $_SESSION['flash_success'] ?? '';
$info = $_SESSION['flash_info'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_info']);

// Infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

// Cartes de la collection
$stmt = $pdo->prepare("
    SELECT c.*, inv.date_acquisition
    FROM inventaire inv
    JOIN carte c ON c.id = inv.id_carte
    WHERE inv.id_utilisateur = ?
    ORDER BY inv.date_acquisition DESC
");
$stmt->execute([$id_utilisateur]);
$mes_cartes = $stmt->fetchAll();

// Statistiques
$nb_cartes = count($mes_cartes);
$valeur_total = array_sum(array_column($mes_cartes, 'valeur'));

$page_active = 'profil';
$titre_page = 'Mon Profil - CARTE A COLLECTIONNER';
require_once 'includes/header.php';
?>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-contenu {
        background: var(--couleur_fond);
        border: 1px solid var(--couleur_card_bordure);
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        max-width: 440px;
        width: 95%;
    }

    .modal-titre {
        font-family: var(--font_corps);
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--couleur_texte_principal);
        margin-bottom: 20px;
    }

    #pack-canvas canvas {
        display: block;
        margin: 0 auto;
        border-radius: 4px;
    }

    .btn-ouvrir-pack {
        display: inline-block;
        padding: 14px 48px;
        background-color: var(--couleur_nav_active);
        color: var(--couleur_nav_texte);
        border: none;
        border-radius: 4px;
        font-family: var(--font_corps);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: 0.04em;
        transition: background-color 0.2s ease, transform 0.1s ease;
    }

    .btn-ouvrir-pack:hover {
        background-color: #d4911a;
        transform: translateY(-2px);
    }

    .pack-zone {
        text-align: center;
        padding: 30px 0 10px;
        margin-bottom: 20px;
    }
</style>

<main>

    <?php if ($success): ?>
        <div class="message_succes"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
        <div
            style="background:#fff3cd;color:#856404;padding:12px 20px;border-radius:4px;border:1px solid #ffeeba;margin-bottom:24px;font-family:var(--font_corps);font-size:.95rem;">
            <?= htmlspecialchars($info) ?>
        </div>
    <?php endif; ?>

    <!-- BLOC PROFIL -->
    <div class="profil_bloc">
        <div class="profil_photo">
            <?php if (!empty($utilisateur['photo_url'])): ?>
                <img src="<?= htmlspecialchars($utilisateur['photo_url']) ?>" alt="Avatar">
            <?php else: ?>
                <span class="profil_photo_icon">&#128247;</span>
            <?php endif; ?>
        </div>
        <div class="profil_infos">
            <div>
                <p><span class="profil_label">Nom de profil : </span><?= htmlspecialchars($utilisateur['nom_profil']) ?>
                </p>
                <p style="margin-top:10px;"><span class="profil_label">Rôle :
                    </span><?= htmlspecialchars($utilisateur['role']) ?></p>
            </div>
            <div>
                <p><span class="profil_label">Nombre de cartes : </span><?= $nb_cartes ?></p>
                <p style="margin-top:10px;"><span class="profil_label">Valeur collection :
                    </span><?= number_format($valeur_total, 2, ',', ' ') ?> €</p>
            </div>
        </div>
    </div>

    <!-- BOUTON OUVRIR UN PAQUET -->
    <div class="pack-zone">
        <button class="btn-ouvrir-pack" onclick="ouvrirModalPack()">Ouvrir un paquet</button>
    </div>

    <!-- COLLECTION -->
    <div class="profil_cartes_header">
        <span class="filtre_label">Ma Collection</span>
        <hr class="profil_separateur">
    </div>

    <div class="grille_cartes">
        <?php if (empty($mes_cartes)): ?>
            <p style="grid-column:1/-1;text-align:center;color:var(--couleur_texte_secondaire);">
                Votre collection est vide. Ouvrez un paquet pour commencer !
            </p>
        <?php else: ?>
            <?php foreach ($mes_cartes as $carte): ?>
                <div class="card">
                    <div class="card_nom"><?= htmlspecialchars($carte['nom']) ?></div>
                    <div class="card_image">
                        <img src="<?= htmlspecialchars($carte['image_url'] ?? '') ?>"
                            alt="<?= htmlspecialchars($carte['nom']) ?>">
                    </div>
                    <div class="card_valeur"><?= number_format($carte['valeur'], 2, ',', ' ') ?> €</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<!-- =========================================
     MODAL PACK OPENING
     ========================================= -->
<div id="modal-pack" class="modal-overlay" style="display:none;">
    <div class="modal-contenu">
        <h3 class="modal-titre">Ouverture du paquet</h3>
        <div id="pack-canvas"></div>
        <div id="pack-erreur" class="message_erreur" style="display:none;margin-top:16px;"></div>
        <div style="margin-top:20px;">
            <button id="btn-fermer-pack" class="btn_supprimer" style="display:none;" onclick="fermerModalPack()">
                Fermer
            </button>
        </div>
    </div>
</div>

<!-- p5.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.9.0/p5.min.js"></script>

<script>
    // =========================================
    // SKETCH P5.JS — ANIMATION PACK OPENING
    // =========================================
    const packSketch = (p) => {

        const PW = 130, PH = 180;
        let cx, cy;
        let state = 'idle';
        let frameN = 0;
        let topOff = 0;
        let botOff = 0;
        let cardImg = null;
        let cardData = null;
        let pts = [];
        let closeCalled = false;

        p.setup = () => {
            const cnv = p.createCanvas(360, 380);
            cnv.parent('pack-canvas');
            cx = p.width / 2;
            cy = p.height / 2;
            p.textFont('Georgia, serif');
        };

        p.draw = () => {
            p.background(220, 227, 234);

            if (state === 'idle') {
                const pulse = 1 + Math.sin(p.frameCount * 0.05) * 0.015;
                p.push();
                p.translate(cx, cy);
                p.scale(pulse);
                drawPack();
                p.pop();

            } else if (state === 'opening') {
                frameN++;
                const t = Math.min(frameN / 55, 1);
                const ease = easeInOut(t);
                topOff = ease * 160;
                botOff = ease * 160;

                p.push();
                p.translate(cx, cy);
                drawPackHalves();
                p.pop();

                if (frameN === 3) spawnPts();
                updatePts();
                drawPts();

                if (t >= 1) { state = 'revealing'; frameN = 0; }

            } else if (state === 'revealing') {
                frameN++;
                const t = Math.min(frameN / 50, 1);

                p.push();
                p.translate(cx, cy);
                drawCard(t);
                p.pop();

                updatePts();
                drawPts();

                if (t >= 1 && !closeCalled) {
                    closeCalled = true;
                    state = 'done';
                    const btn = document.getElementById('btn-fermer-pack');
                    if (btn) btn.style.display = 'inline-block';
                }

            } else if (state === 'done') {
                p.push();
                p.translate(cx, cy);
                drawCard(1);
                p.pop();
            }
        };

        // ---- Helpers ----

        function easeInOut(t) {
            return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        }

        function drawPack() {
            // Ombre
            p.noStroke();
            p.fill(0, 0, 0, 25);
            p.rect(-PW / 2 + 3, -PH / 2 + 3, PW, PH, 10);

            // Corps
            p.fill(45, 55, 72);
            p.stroke(232, 160, 32);
            p.strokeWeight(2);
            p.rect(-PW / 2, -PH / 2, PW, PH, 10);

            // Etiquette
            p.noStroke();
            p.fill(232, 160, 32);
            p.textAlign(p.CENTER, p.CENTER);
            p.textSize(10);
            p.text('PAQUET', 0, -PH / 2 + 16);

            // Séparateur
            p.stroke(232, 160, 32, 100);
            p.strokeWeight(0.5);
            p.line(-PW / 2 + 14, -PH / 2 + 28, PW / 2 - 14, -PH / 2 + 28);

            // Symbole ?
            p.noStroke();
            p.fill(232, 160, 32);
            p.textSize(58);
            p.textAlign(p.CENTER, p.CENTER);
            p.text('?', 0, 8);
        }

        function drawPackHalves() {
            // Moitié haute
            p.fill(45, 55, 72);
            p.stroke(232, 160, 32);
            p.strokeWeight(2);
            p.rect(-PW / 2, -PH / 2 - topOff, PW, PH / 2, 10, 10, 0, 0);

            p.noStroke();
            p.fill(232, 160, 32);
            p.textAlign(p.CENTER, p.CENTER);
            p.textSize(10);
            p.text('PAQUET', 0, -PH / 2 - topOff + 16);

            // Moitié basse
            p.fill(45, 55, 72);
            p.stroke(232, 160, 32);
            p.strokeWeight(2);
            p.rect(-PW / 2, botOff, PW, PH / 2, 0, 0, 10, 10);
        }

        function drawCard(t) {
            const sx = easeInOut(Math.min(t, 1));

            p.push();
            p.scale(sx, 1);

            // Ombre
            p.noStroke();
            p.fill(0, 0, 0, 20 * sx);
            p.rect(-PW / 2 + 3, -PH / 2 + 3, PW, PH, 10);

            if (t <= 0.5) {
                // Dos de la carte
                p.fill(45, 55, 72);
                p.stroke(232, 160, 32);
                p.strokeWeight(2);
                p.rect(-PW / 2, -PH / 2, PW, PH, 10);

                p.noStroke();
                p.fill(232, 160, 32);
                p.textSize(40);
                p.textAlign(p.CENTER, p.CENTER);
                p.text('★', 0, 0);

            } else if (cardData) {
                // Face avant
                p.fill(255);
                p.stroke(232, 160, 32);
                p.strokeWeight(2);
                p.rect(-PW / 2, -PH / 2, PW, PH, 10);

                const iW = PW - 16;
                const iH = Math.floor((PH - 16) * 0.58);
                const iX = -PW / 2 + 8;
                const iY = -PH / 2 + 8;

                // Zone image
                if (cardImg) {
                    p.noStroke();
                    p.image(cardImg, iX, iY, iW, iH);
                } else {
                    p.fill(200, 210, 219);
                    p.noStroke();
                    p.rect(iX, iY, iW, iH, 4);
                }

                const textBase = iY + iH;

                // Nom
                p.noStroke();
                p.fill(45, 55, 72);
                p.textSize(11);
                p.textAlign(p.CENTER, p.CENTER);
                p.textStyle(p.BOLD);
                p.text(cardData.nom, 0, textBase + 14);

                // Badge rareté
                const rc = rarityColor(cardData.rarete);
                p.fill(rc[0], rc[1], rc[2]);
                p.noStroke();
                p.rect(-38, textBase + 24, 76, 15, 7);
                p.fill(255);
                p.textSize(9);
                p.textStyle(p.NORMAL);
                p.text(cardData.rarete, 0, textBase + 32);

                // Valeur
                p.noStroke();
                p.fill(232, 160, 32);
                p.textSize(12);
                p.textStyle(p.BOLD);
                p.text(parseFloat(cardData.valeur).toFixed(2) + ' €', 0, textBase + 50);
            }

            p.pop();
        }

        function rarityColor(r) {
            const map = {
                'commune': [113, 128, 150],
                'peu commune': [45, 106, 79],
                'rare': [26, 82, 118],
                'ultra rare': [125, 60, 152]
            };
            return map[r] || [113, 128, 150];
        }

        function spawnPts() {
            for (let i = 0; i < 20; i++) {
                const angle = p.random(p.TWO_PI);
                const speed = p.random(1.5, 4.5);
                pts.push({
                    x: cx,
                    y: cy,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 1,
                    life: 1,
                    size: p.random(3, 8),
                    gold: p.random() > 0.4
                });
            }
        }

        function updatePts() {
            for (let i = pts.length - 1; i >= 0; i--) {
                pts[i].x += pts[i].vx;
                pts[i].y += pts[i].vy;
                pts[i].vy += 0.1;
                pts[i].life -= 0.022;
                if (pts[i].life <= 0) pts.splice(i, 1);
            }
        }

        function drawPts() {
            p.noStroke();
            for (const pt of pts) {
                const a = pt.life * 255;
                pt.gold ? p.fill(232, 160, 32, a) : p.fill(255, 255, 255, a);
                p.ellipse(pt.x, pt.y, pt.size * pt.life);
            }
        }

        // ---- API publique ----

        p.openPack = (data) => {
            cardData = data;
            cardImg = null;
            if (data.image_url) {
                p.loadImage(
                    data.image_url,
                    (img) => { cardImg = img; },
                    () => { cardImg = null; }
                );
            }
            state = 'opening';
            frameN = 0;
            topOff = 0;
            botOff = 0;
            pts = [];
            closeCalled = false;
        };

        p.resetSketch = () => {
            state = 'idle';
            frameN = 0;
            topOff = 0;
            botOff = 0;
            cardImg = null;
            cardData = null;
            pts = [];
            closeCalled = false;
        };
    };

    // =========================================
    // INITIALISATION DU SKETCH (une seule fois)
    // =========================================
    let packP5 = null;

    document.addEventListener('DOMContentLoaded', () => {
        packP5 = new p5(packSketch);
    });

    // =========================================
    // GESTION DU MODAL
    // =========================================
    function ouvrirModalPack() {
        const modal = document.getElementById('modal-pack');
        const erreur = document.getElementById('pack-erreur');
        const btnFermer = document.getElementById('btn-fermer-pack');

        modal.style.display = 'flex';
        erreur.style.display = 'none';
        btnFermer.style.display = 'none';

        if (packP5) packP5.resetSketch();

        fetch('pack.php', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.succes) {
                    if (packP5) packP5.openPack(data.carte);
                } else {
                    erreur.textContent = data.message;
                    erreur.style.display = 'block';
                    btnFermer.style.display = 'inline-block';
                }
            })
            .catch(() => {
                erreur.textContent = 'Erreur de connexion. Veuillez réessayer.';
                erreur.style.display = 'block';
                btnFermer.style.display = 'inline-block';
            });
    }

    function fermerModalPack() {
        document.getElementById('modal-pack').style.display = 'none';
        if (packP5) packP5.resetSketch();
        location.reload();
    }
</script>

<?php require_once 'includes/footer.php'; ?>