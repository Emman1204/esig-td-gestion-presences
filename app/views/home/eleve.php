<div class="main-container">

    <!-- -------------------------------
         Titre et bouton Départ/Fin
    --------------------------------->
    
    <div class="presence-container">
        <!-- 
            data-seance-id vaut soit l'ID existant de la séance du jour,
            soit 0 si aucune séance n'existe encore
        -->
        <button type="button"
            id="btnPresence"
            name="btnPresence"
            data-seance-id="<?= $seance['SPP_SEAN_ID'] ?? 0 ?>">
            Départ
        </button>
        <div id="timer" class="timer hidden">00:00:00</div>
    </div>

    <!-- -------------------------------
         Liens CSS et JS
    --------------------------------->
    <link rel="stylesheet" href="/public/css/eleve.css">
    <script src="../../../public/js/presence.js" defer></script>
    <script src="../../../public/js/seance.js" defer></script>

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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rempli dynamiquement en JS -->
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
