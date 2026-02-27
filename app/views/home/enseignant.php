<link rel="stylesheet" href="/public/css/enseignant.css">
<link rel="stylesheet" href="/public/css/globale.css">
<script src="../../../public/js/enseignant.js"></script>
<section class="eleves-section">

    <h2>Liste des élèves</h2>

    <?php if (!empty($eleves)): ?>

        <p class="classe-label">
            <strong>Classe :</strong>
            <?= htmlspecialchars($eleves[0]['SPP_CLASSE_NOM'] ?? '-') ?>
        </p>

        <table class="eleves-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($eleves as $e): ?>

                    <?php
                        $status = $e['status'] ?? null;
                        $isPointeAujourdhui = ($status === 'EN ATTENTE');
                    ?>

                    <tr
                        class="ligne-eleve <?= $isPointeAujourdhui ? 'eleve-pointe' : '' ?>"
                        data-eleve-id="<?= $e['eleve_id'] ?>"
                        data-nom="<?= htmlspecialchars($e['eleve_nom']) ?>"
                        data-prenom="<?= htmlspecialchars($e['eleve_prenom']) ?>"
                        data-status="<?= htmlspecialchars($status ?? 'Non pointé') ?>"
                    >
                        <td><?= htmlspecialchars($e['eleve_nom']) ?></td>
                        <td><?= htmlspecialchars($e['eleve_prenom']) ?></td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>

        <p>Aucun élève trouvé.</p>

    <?php endif; ?>


    <!-- ========================= -->
    <!-- MODAL -->
    <!-- ========================= -->

    <div id="eleveModal" class="modal">
        <div class="modal-content">

            <span id="closeModal" class="close">&times;</span>

            <h3>Détails de l'élève</h3>

            <p><strong>Prénom :</strong> <span id="modalPrenom"></span></p>
            <p><strong>Nom :</strong> <span id="modalNom"></span></p>

            <hr>

            <!-- 🔹 Pointage du jour -->
            <div id="modalPointage"></div>

            <hr>

            <!-- 🔹 Historique -->
            <div id="modalHistorique"></div>

        </div>
    </div>

</section>

