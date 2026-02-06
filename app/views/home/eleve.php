<section>
    <div class="main-container">

        <!-- -------------------------------
             Titre et bouton Départ/Fin
        --------------------------------->
        <div class="presence-container">
            <button type="button"
                id="btnPresence"
                data-seance-id="<?= $seanceEnCours['SPP_SEAN_ID'] ?? 0 ?>"
                data-heure-deb="<?= $seanceEnCours['SPP_SEAN_HEURE_DEB'] ?? '' ?>">
                <?= empty($seanceEnCours) ? 'Départ' : 'Fin' ?>
            </button>


            <div id="timer" class="timer <?= empty($seanceEnCours) ? 'hidden' : '' ?>">
                00:00:00
            </div>

        </div>


        <!-- -------------------------------
             Liens CSS et JS
        --------------------------------->
        <link rel="stylesheet" href="/public/css/eleve.css">
        <script src="/public/js/presence.js" defer></script>
        <script src="/public/js/seance.js" defer></script>

        <!-- -------------------------------
             Tableau des séances
        --------------------------------->
        <h1>Mes Séances</h1>

        <table id="tableSeances" border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure Début</th>
                    <th>Heure Fin</th>
                    <th>Temps de présence</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($seances)): ?>
                    <?php
                    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                    ?>
                    <?php foreach ($seances as $s): ?>
                        <?php
                        $timestamp = strtotime($s['SPP_SEAN_DATE']);
                        $nomJour = $jours[date('w', $timestamp)];
                        $dateFormatee = $nomJour . ' ' . date('d.m.Y', $timestamp);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($dateFormatee) ?></td>
                            <td><?= $s['SPP_SEAN_HEURE_DEB'] ?? '-' ?></td>
                            <td><?= $s['SPP_SEAN_HEURE_FIN'] ?? '-' ?></td>
                            <td><?= $s['temps_presence'] ?? '-' ?></td>
                            <td>
                                <div class="comment-display">
                                    <?php if (!empty($s['SPP_SEAN_COMM'])): ?>
                                        <?= htmlspecialchars($s['SPP_SEAN_COMM']) ?>
                                        <button type="button" class="edit-comment-btn" title="Modifier">✏️</button>
                                    <?php else: ?>
                                        <button type="button" class="edit-comment-btn" title="Ajouter un commentaire">✏️</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Aucune séance enregistrée</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <?php
    // -------------------------------
    // Gestion temporaire du clic du bouton
    // ⚠️ Pour l’instant on simule simplement l’action
    // -------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['seanId'], $_POST['heure'])) {
        $seanId = $_POST['seanId'];
        $heure = $_POST['heure'];

        foreach ($seances as $s) {
            if ($s['SPP_SEAN_ID'] == $seanId) {
                $seance = $s;
                break;
            }
        }

        if (!empty($seance)) {
            if (empty($seance['SPP_SEAN_HEURE_DEB'])) {
                echo "<pre>💡 Départ marqué pour la séance ID={$seanId} à {$heure}</pre>";
            } elseif (empty($seance['SPP_SEAN_HEURE_FIN'])) {
                echo "<pre>💡 Fin marquée pour la séance ID={$seanId} à {$heure}</pre>";
            } else {
                echo "<pre>✅ Présence déjà terminée pour cette séance.</pre>";
            }
        }
    }
    ?>
</section>