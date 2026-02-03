    <!-- Premier clic : marque le départ
    <button type="submit" id="btnPresence" name="btnPresence" value="marquer">Départ</button>

    <script>
        var btnPresence = document.getElementById("btnPresence");
        btnPresence.addEventListener("click", function() {
            if (this.textContent === "Départ")
                this.textContent = "Fin";
            else
                this.textContent = "Départ";
        });
    </script> -->

<h1>Ma présence</h1>

<div class="presence-container">
    <button id="presenceBtn"
            class="presence-btn"
            data-seance-id="<?= $seance['SPP_SEAN_ID'] ?>"
            data-state="<?= empty($seance['SPP_SEAN_HEURE_DEB']) ? 'start' : 'end' ?>">
        <?= empty($seance['SPP_SEAN_HEURE_DEB']) ? 'Départ' : 'Fin' ?>
    </button>

    <div id="timer" class="timer hidden">
        00:00:00
    </div>
</div>

<link rel="stylesheet" href="/public/css/eleve.css">
<script src="/public/js/presence.js" defer></script>


    <h1>Mes Séances</h1>

    <?php if (!empty($seances)): ?>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure Début</th>
                    <th>Heure Fin</th>
                    <th>Commentaire</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($seances as $seance): ?>
                    <tr>
                        <td><?= htmlspecialchars($seance['SPP_SEAN_DATE']) ?></td>
                        <td><?= htmlspecialchars($seance['SPP_SEAN_HEURE_DEB']) ?></td>
                        <td><?= htmlspecialchars($seance['SPP_SEAN_HEURE_FIN']) ?></td>
                        <td><?= htmlspecialchars($seance['SPP_SEAN_COMM']) ?></td>
                        <td>
                            <!-- Formulaire pour marquer la présence -->
                            <form method="post" action="">
                                <input type="hidden" name="seanId" value="<?= $seance['SPP_SEAN_ID'] ?>">
                                <input type="hidden" name="heure" value="<?= date('Y-m-d H:i:s') ?>">


                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucune séance trouvée.</p>
    <?php endif; ?>

    <?php
    // -------------------------------
    // Gestion temporaire du clic du bouton
    // -------------------------------
    // ⚠️ Pour l’instant on simule simplement l’action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['seanId'], $_POST['heure'])) {
        $seanId = $_POST['seanId'];
        $heure = $_POST['heure'];

        // On cherche la séance correspondante dans $seances
        foreach ($seances as $s) {
            if ($s['SPP_SEAN_ID'] == $seanId) {
                $seance = $s;
                break;
            }
        }

        if (!empty($seance)) {
            if (empty($seance['SPP_SEAN_HEURE_DEB'])) {
                echo "<pre>💡 Départ marqué pour la séance ID={$seanId} à {$heure}</pre>";
                // Ici, plus tard, tu feras :
                // $seanceModel->update($seanId, ['SPP_SEAN_HEURE_DEB' => $heure]);
            } elseif (empty($seance['SPP_SEAN_HEURE_FIN'])) {
                echo "<pre>💡 Fin marquée pour la séance ID={$seanId} à {$heure}</pre>";
                // Ici, plus tard, tu feras :
                // $seanceModel->update($seanId, ['SPP_SEAN_HEURE_FIN' => $heure]);
            } else {
                echo "<pre>✅ Présence déjà terminée pour cette séance.</pre>";
            }
        }
    }
    ?>