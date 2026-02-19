<link rel="stylesheet" href="/public/css/enseignant.css">
<link rel="stylesheet" href="/public/css/globale.css">

<section class="eleves-section">
    <h2>Liste des élèves</h2>

    <?php if (!empty($eleves)): ?>
        <p><strong>Classe : </strong><?= htmlspecialchars($eleves[0]['SPP_CLASSE_NOM'] ?? '-') ?></p>

        <table class="eleves-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($eleves as $e): ?>
                    <tr class="<?= ($e['status'] ?? '') === 'EN ATTENTE' ? 'eleve-pointé' : '' ?>">
                        <td><?= htmlspecialchars($e['eleve_nom']) ?></td>
                        <td><?= htmlspecialchars($e['eleve_prenom']) ?></td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun élève trouvé</p>
    <?php endif; ?>
    <div id="eleveModal" class="modal">
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>
        <h3>Détails de l'élève</h3>
        <p><strong>Prénom :</strong> <span id="modalPrenom"></span></p>
        <p><strong>Nom :</strong> <span id="modalNom"></span></p>
        <p><strong>Statut :</strong> <span id="modalStatus"></span></p>
        <!-- Tu pourras ajouter ici d'autres infos plus tard -->
    </div>
</div>
</section>




<script src="../../../public/js/enseignant.js"></script>