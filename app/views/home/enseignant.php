<section>

    <!-- Bouton Toggle -->
    <button id="toggleFilter">Voir tous les élèves</button>

    <!-- Zone dynamique qui contient le tableau -->
    <div id="listePresences">

        <!-- Tableau des séances assignées -->
        <table id="tableSeancesProf" border="1" cellpadding="5"> <!-- Changement d'ID -->
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure Début</th>
                    <th>Heure Fin</th>
                    <th>Classe</th>
                    <th>Présences</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($seances)): ?>
                    <?php
                    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

                    $seances_grouped = [];
                    foreach ($seances as $s) {
                        $id = $s['SPP_SEAN_ID'] ?? null;
                        if (!$id) continue;

                        if (!isset($seances_grouped[$id])) {
                            $seances_grouped[$id] = [
                                'SPP_SEAN_ID' => $id,
                                'SPP_SEAN_DATE' => $s['SPP_SEAN_DATE'] ?? null,
                                'SPP_SEAN_HEURE_DEB' => $s['SPP_SEAN_HEURE_DEB'] ?? null,
                                'SPP_SEAN_HEURE_FIN' => $s['SPP_SEAN_HEURE_FIN'] ?? null,
                                'SPP_CLASSE_NOM' => $s['SPP_CLASSE_NOM'] ?? '-',
                                'eleves' => []
                            ];
                        }

                        $seances_grouped[$id]['eleves'][] = [
                            'id' => $s['eleve_id'] ?? null,
                            'nom' => $s['eleve_nom'] ?? '',
                            'prenom' => $s['eleve_prenom'] ?? '',
                            'status' => $s['SPP_ENS_SEAN_STATUS'] ?? 'EN ATTENTE'
                        ];
                    }
                    ?>

                    <?php foreach ($seances_grouped as $s): ?>
                        <?php
                        $timestamp = strtotime($s['SPP_SEAN_DATE']);
                        $nomJour = $timestamp ? $jours[date('w', $timestamp)] : '';
                        $dateFormatee = $timestamp ? $nomJour . ' ' . date('d.m.Y', $timestamp) : '-';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($dateFormatee) ?></td>
                            <td><?= htmlspecialchars($s['SPP_SEAN_HEURE_DEB'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['SPP_SEAN_HEURE_FIN'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['SPP_CLASSE_NOM']) ?></td>
                            <td>
                                <table border="1" cellpadding="3">
                                    <thead>
                                        <tr>
                                            <th>Élève</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($s['eleves'] as $e): ?>
                                            <tr class="ligne-eleve" data-status="<?= $e['status'] ?>">
                                                <td><?= htmlspecialchars(trim($e['prenom'] . ' ' . $e['nom'])) ?></td>
                                                <td>
                                                    <select class="status-eleve"
                                                        data-eleve-id="<?= $e['id'] ?>"
                                                        data-seance-id="<?= $s['SPP_SEAN_ID'] ?>">
                                                        <?php
                                                        $options = ['EN ATTENTE', 'PRESENT', 'ABSENT', 'EXCUSE', 'RETARD'];
                                                        $current = $e['status'] ?? 'EN ATTENTE';
                                                        foreach ($options as $opt):
                                                        ?>
                                                            <option value="<?= $opt ?>" <?= $opt === $current ? 'selected' : '' ?>>
                                                                <?= $opt ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button class="btn-valider-status"
                                                        data-eleve-id="<?= $e['id'] ?>"
                                                        data-seance-id="<?= $s['SPP_SEAN_ID'] ?>">
                                                        Confirmer
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="5">Aucun pointage d'élève</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>


</section>

<script src="../../../public/js/enseignant.js"></script>