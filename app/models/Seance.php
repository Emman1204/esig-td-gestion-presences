<?php

class Seance
{
    private $db; // Connexion PDO

    /**
     * Constructeur
     * @param PDO $pdo : connexion à la base
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    // ==================================================
    // MÉTHODES DE LECTURE
    // ==================================================

    /**
     * Récupérer toutes les séances
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM SPP_SEANCE");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer toutes les séances pour un élève
     */
    public function findByEleve(int $eleveId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM SPP_SEANCE WHERE SPP_UTIL_ID = :eleveId"
        );
        $stmt->bindParam(':eleveId', $eleveId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie et crée les entrées SPP_ENSEI_SEAN pour un enseignant
     * lorsqu'il récupère les séances de ses élèves.
     *
     * @param int $enseignantId
     * @param array $seances Tableau des séances récupérées
     */



    public function insertEnseignantSeance(int $seanceId, int $eleveId)
    {
        // On récupère la classe de l'élève
        $stmtClasse = $this->db->prepare("
        SELECT classe.SPP_CLASSE_ID, ens.SPP_UTIL_ID AS enseignantId
        FROM SPP_EST_INSCRIT est
        JOIN SPP_CLASSE classe ON est.SPP_CLASSE_ID = classe.SPP_CLASSE_ID
        JOIN SPP_SUPERVISE sup ON sup.SPP_CLASSE_ID = classe.SPP_CLASSE_ID
        JOIN SPP_ENSEIGNANT ens ON ens.SPP_UTIL_ID = sup.SPP_UTIL_ID
        WHERE est.SPP_UTIL_ID = :eleveId
    ");
        $stmtClasse->bindParam(':eleveId', $eleveId, PDO::PARAM_INT);
        $stmtClasse->execute();
        $enseignants = $stmtClasse->fetchAll(PDO::FETCH_ASSOC);

        // Pour chaque enseignant, on crée l'entrée dans SPP_ENSEI_SEAN
        foreach ($enseignants as $e) {
            $stmtInsert = $this->db->prepare("
            INSERT INTO SPP_ENSEI_SEAN (SPP_UTIL_ID, SPP_SEAN_ID, SPP_ENS_SEAN_STATUS)
            VALUES (:enseignantId, :seanceId, 'EN ATTENTE')
            ON DUPLICATE KEY UPDATE SPP_ENS_SEAN_STATUS = SPP_ENS_SEAN_STATUS
        ");
            $stmtInsert->bindParam(':enseignantId', $e['enseignantId'], PDO::PARAM_INT);
            $stmtInsert->bindParam(':seanceId', $seanceId, PDO::PARAM_INT);
            $stmtInsert->execute();
        }
    }


    /**
     * Récupérer les séances d’un enseignant
     */
    // 1️⃣ Tous les élèves de la classe de l'enseignant
    public static function getElevesByEnseignant($enseignantId)
    {
        $db = Database::getInstance();

        // 1️⃣ Tous les élèves de la classe supervisée par l'enseignant
        $sqlEleves = "
        SELECT 
            c.SPP_CLASSE_NOM,
            e.SPP_UTIL_ID AS eleve_id,
            e.SPP_UTIL_NOM AS eleve_nom,
            e.SPP_UTIL_PRENOM AS eleve_prenom
        FROM SPP_SUPERVISE sc
        INNER JOIN SPP_CLASSE c ON c.SPP_CLASSE_ID = sc.SPP_CLASSE_ID
        INNER JOIN SPP_EST_INSCRIT ei ON ei.SPP_CLASSE_ID = c.SPP_CLASSE_ID
        INNER JOIN SPP_ELEVE e ON e.SPP_UTIL_ID = ei.SPP_UTIL_ID
        WHERE sc.SPP_UTIL_ID = :enseignantId
        ORDER BY e.SPP_UTIL_NOM ASC, e.SPP_UTIL_PRENOM ASC
    ";
        $stmt = $db->prepare($sqlEleves);
        $stmt->execute(['enseignantId' => $enseignantId]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2️⃣ Tous les élèves qui ont pointé aujourd'hui (EN ATTENTE)
        $sqlPointes = "
    SELECT DISTINCT s.SPP_UTIL_ID AS eleve_id
    FROM SPP_SEANCE s
    INNER JOIN SPP_EST_INSCRIT ei ON ei.SPP_UTIL_ID = s.SPP_UTIL_ID
    INNER JOIN SPP_SUPERVISE sc ON sc.SPP_CLASSE_ID = ei.SPP_CLASSE_ID
    INNER JOIN SPP_ENSEI_SEAN es ON es.SPP_SEAN_ID = s.SPP_SEAN_ID
    WHERE sc.SPP_UTIL_ID = :enseignantId
      AND DATE(s.SPP_SEAN_DATE) = CURDATE()
      AND es.SPP_ENS_SEAN_STATUS = 'EN ATTENTE'
";
        $stmt2 = $db->prepare($sqlPointes);
        $stmt2->execute(['enseignantId' => $enseignantId]);
        $elevesPointes = $stmt2->fetchAll(PDO::FETCH_COLUMN, 0);

        // 3️⃣ Marquer les élèves pointés
        foreach ($eleves as &$e) {
            if (in_array($e['eleve_id'], $elevesPointes)) {
                $e['status'] = 'EN ATTENTE'; // pour mettre en évidence
            } else {
                $e['status'] = ''; // pas pointé aujourd'hui
            }
        }

        return $eleves;
    }

    // 2️⃣ Élèves qui ont pointé aujourd'hui
    public static function getElevesPointesAujourdHui($enseignantId)
    {
        $db = Database::getInstance();

        $sql = "
        SELECT DISTINCT e.SPP_UTIL_ID AS eleve_id
        FROM SPP_SEANCE s
        INNER JOIN SPP_ENSEI_SEAN es ON es.SPP_SEAN_ID = s.SPP_SEAN_ID
        INNER JOIN SPP_ELEVE e ON e.SPP_UTIL_ID = es.SPP_UTIL_ID
        INNER JOIN SPP_EST_INSCRIT ei ON ei.SPP_UTIL_ID = e.SPP_UTIL_ID
        INNER JOIN SPP_SUPERVISE sc ON sc.SPP_CLASSE_ID = ei.SPP_CLASSE_ID
        WHERE sc.SPP_UTIL_ID = :enseignantId
        AND DATE(s.SPP_SEAN_DATE) = CURDATE()
        AND es.SPP_ENS_SEAN_STATUS = 'EN ATTENTE'
    ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['enseignantId' => $enseignantId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_column($result, 'eleve_id');
    }
    /**
     * Récupérer la séance du jour pour un élève
     * (utile pour afficher le bouton Départ / Fin)
     */
    public function findTodayByEleve(int $eleveId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM SPP_SEANCE
            WHERE SPP_UTIL_ID = :eleveId
              AND SPP_SEAN_DATE = CURDATE()
            ORDER BY SPP_SEAN_ID DESC
            LIMIT 1
        ");
        $stmt->execute(['eleveId' => $eleveId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ==================================================
    // MÉTHODES D’ÉCRITURE GÉNÉRALES
    // ==================================================

    /**
     * Insérer une séance
     * ⚠️ Utilisée surtout pour les tests / admin
     */
    public function insert(
        int $utilId,
        string $date,
        string $heureDeb,
        string $heureFin,
        ?string $comm = null
    ): bool {
        $sql = "INSERT INTO SPP_SEANCE
                (SPP_UTIL_ID, SPP_SEAN_DATE, SPP_SEAN_HEURE_DEB, SPP_SEAN_HEURE_FIN, SPP_SEAN_COMM)
                VALUES (:utilId, :date, :heureDeb, :heureFin, :comm)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':utilId', $utilId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':heureDeb', $heureDeb);
        $stmt->bindParam(':heureFin', $heureFin);
        $stmt->bindParam(':comm', $comm);

        return $stmt->execute();
    }

    /**
     * Mise à jour complète (admin / enseignant)
     */
    public function update(int $seanId, array $data): bool
    {
        $sql = "UPDATE SPP_SEANCE SET
                    SPP_UTIL_ID = :utilId,
                    SPP_SEAN_DATE = :date,
                    SPP_SEAN_HEURE_DEB = :heureDeb,
                    SPP_SEAN_HEURE_FIN = :heureFin,
                    SPP_SEAN_COMM = :comm
                WHERE SPP_SEAN_ID = :seanId";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':utilId', $data['SPP_UTIL_ID'], PDO::PARAM_INT);
        $stmt->bindParam(':date', $data['SPP_SEAN_DATE']);
        $stmt->bindParam(':heureDeb', $data['SPP_SEAN_HEURE_DEB']);
        $stmt->bindParam(':heureFin', $data['SPP_SEAN_HEURE_FIN']);
        $stmt->bindParam(':comm', $data['SPP_SEAN_COMM']);
        $stmt->bindParam(':seanId', $seanId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Supprimer une séance
     */
    public function delete(int $seanId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM SPP_SEANCE WHERE SPP_SEAN_ID = :seanId"
        );
        $stmt->bindParam(':seanId', $seanId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ==================================================
    // LOGIQUE DE PRÉSENCE (ÉLÈVE)
    // ==================================================

    /**
     * PREMIER CLIC → Départ
     * Met uniquement l'heure de début
     */
    public function updateHeureDebut(int $seanId, string $heureDebut): bool
    {
        $sql = "UPDATE SPP_SEANCE
            SET SPP_SEAN_HEURE_DEB = :heureDebut
            WHERE SPP_SEAN_ID = :seanId
            AND SPP_SEAN_HEURE_DEB IS NULL";

        $stmt = $this->db->prepare($sql);

        $success = $stmt->execute([
            ':heureDebut' => $heureDebut,
            ':seanId'     => $seanId
        ]);

        if ($success) {
            // 🔥 On lie la séance à l’enseignant
            $this->insererEnseignantsSeance($seanId);
        }

        return $success;
    }
    private function insererEnseignantsSeance(int $seanceId): void
    {
        $sql = "
        INSERT INTO SPP_ENSEI_SEAN (SPP_UTIL_ID, SPP_SEAN_ID, SPP_ENS_SEAN_STATUS)
        SELECT 
            sup.SPP_UTIL_ID,
            :seanceId,
            'EN ATTENTE'
        FROM SPP_SEANCE s
        JOIN SPP_EST_INSCRIT ei ON ei.SPP_UTIL_ID = s.SPP_UTIL_ID
        JOIN SPP_SUPERVISE sup ON sup.SPP_CLASSE_ID = ei.SPP_CLASSE_ID
        WHERE s.SPP_SEAN_ID = :seanceId
        ON DUPLICATE KEY UPDATE SPP_ENS_SEAN_STATUS = SPP_ENS_SEAN_STATUS
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['seanceId' => $seanceId]);
    }
    private function insererSeancePourEnseignants(int $seanceId, int $eleveId): void
    {
        $sql = "
        SELECT sup.SPP_UTIL_ID AS enseignant_id
        FROM SPP_EST_INSCRIT ei
        JOIN SPP_SUPERVISE sup ON sup.SPP_CLASSE_ID = ei.SPP_CLASSE_ID
        WHERE ei.SPP_UTIL_ID = :eleveId
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['eleveId' => $eleveId]);
        $enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($enseignants as $e) {
            $insert = $this->db->prepare("
            INSERT INTO SPP_ENSEI_SEAN (SPP_UTIL_ID, SPP_SEAN_ID, SPP_ENS_SEAN_STATUS)
            VALUES (:enseignantId, :seanceId, 'EN ATTENTE')
            ON DUPLICATE KEY UPDATE SPP_ENS_SEAN_STATUS = SPP_ENS_SEAN_STATUS
        ");

            $insert->execute([
                'enseignantId' => $e['enseignant_id'],
                'seanceId'     => $seanceId
            ]);
        }
    }



    /**
     * DEUXIÈME CLIC → Fin
     * Met uniquement l'heure de fin
     */
    public function updateHeureFin(int $seanId, string $heureFin): bool
    {
        $sql = "UPDATE SPP_SEANCE
                SET SPP_SEAN_HEURE_FIN = :heureFin
                WHERE SPP_SEAN_ID = :seanId
                AND SPP_SEAN_HEURE_DEB IS NOT NULL
                AND SPP_SEAN_HEURE_FIN IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':heureFin', $heureFin);
        $stmt->bindParam(':seanId', $seanId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * ⚠️ MÉTHODE HISTORIQUE (encore utilisable)
     * Gère Départ / Fin automatiquement
     * → moins explicite que les deux méthodes ci-dessus
     */
    public function marquerPresence(int $seanId, string $heure, ?string $comm = null): bool
    {
        // 1️⃣ Récupérer la séance
        $stmt = $this->db->prepare("
        SELECT SPP_SEAN_HEURE_DEB, SPP_SEAN_HEURE_FIN, SPP_UTIL_ID
        FROM SPP_SEANCE
        WHERE SPP_SEAN_ID = :seanId
    ");
        $stmt->execute(['seanId' => $seanId]);
        $seance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seance) return false;

        // 2️⃣ Marquer l'heure de début si vide
        if (empty($seance['SPP_SEAN_HEURE_DEB'])) {
            $ok = $this->updateHeureDebut($seanId, $heure);
            if (!$ok) return false;
        }

        // 3️⃣ Récupérer la classe de la séance via SPP_EST_INSCRIT
        $stmt = $this->db->prepare("
        SELECT e.SPP_UTIL_ID
        FROM SPP_EST_INSCRIT e
        JOIN SPP_SEANCE s ON s.SPP_SEAN_ID = :seanId
        JOIN SPP_CLASSE c ON e.SPP_CLASSE_ID = c.SPP_CLASSE_ID
        WHERE e.SPP_CLASSE_ID = s.SPP_UTIL_ID
    ");
        $stmt->execute(['seanId' => $seanId]);
        $eleves = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 4️⃣ Insérer pour chaque élève dans SPP_ENSEI_SEAN si pas déjà fait
        foreach ($eleves as $eleveId) {
            $this->insererSeancePourEnseignants($seanId, (int)$eleveId);
        }

        // 5️⃣ Marquer l'heure de fin si nécessaire
        if (empty($seance['SPP_SEAN_HEURE_FIN'])) {
            $this->updateHeureFin($seanId, $heure);
        }

        return true;
    }

    // public function marquerDepart($seanceId, $heure)
    // {
    //     $sql = "INSERT INTO SPP_SEANCE (SPP_SEAN_ID, SPP_SEAN_HEURE_DEB) VALUES (:id, :heure)";
    //     $stmt = $this->db->prepare($sql);
    //     return $stmt->execute([
    //         ':id' => $seanceId,
    //         ':heure' => $heure
    //     ]);
    // }

    public function marquerFin($seanceId, $heure)
    {
        $sql = "UPDATE SPP_SEANCE SET SPP_SEAN_HEURE_FIN = :heure WHERE SPP_SEAN_ID = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $seanceId,
            ':heure' => $heure
        ]);
    }

    /**
     * Crée une nouvelle séance pour un élève à une date donnée
     *
     * @param int $eleveId  ID de l'élève
     * @param string $date Date de la séance (YYYY-MM-DD)
     * @return int ID de la séance créée
     */
    public function creerSeance(int $eleveId, string $date): int
    {
        $sql = "
        INSERT INTO SPP_SEANCE (SPP_UTIL_ID, SPP_SEAN_DATE)
        VALUES (:eleveId, :date)
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':eleveId' => $eleveId,
            ':date'    => $date
        ]);

        // ✅ CECI est l'ID réel en base
        return (int) $this->db->lastInsertId();
    }
    /**
     * Met à jour le commentaire d'une séance
     */
    public function updateCommentaire(int $seanceId, string $commentaire): bool
    {
        $sql = "
        UPDATE spp_seance
        SET SPP_SEAN_COMM = :commentaire
        WHERE SPP_SEAN_ID = :seanceId
    ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':commentaire' => $commentaire,
            ':seanceId'    => $seanceId
        ]);
    }
    /**
     * Récupérer la séance en cours pour un élève
     */
    public function getCurrentSeance($eleveId)
    {
        $stmt = $this->db->prepare("
        SELECT * 
        FROM SPP_SEANCE 
        WHERE SPP_UTIL_ID = :eleveId 
          AND SPP_SEAN_DATE = CURDATE() 
          AND SPP_SEAN_HEURE_DEB IS NOT NULL
          AND SPP_SEAN_HEURE_FIN IS NULL
        ORDER BY SPP_SEAN_ID DESC 
        LIMIT 1
    ");
        $stmt->execute(['eleveId' => $eleveId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Récupérer toutes les séances supervisées par un enseignant
    public static function getSeancesByEnseignant($enseignantId)
    {
        $db = Database::getInstance();

        $sql = "
        SELECT 
            c.SPP_CLASSE_NOM,
            e.SPP_UTIL_ID AS eleve_id,
            e.SPP_UTIL_NOM AS eleve_nom,
            e.SPP_UTIL_PRENOM AS eleve_prenom
        FROM SPP_SUPERVISE sc
        INNER JOIN SPP_CLASSE c 
            ON c.SPP_CLASSE_ID = sc.SPP_CLASSE_ID
        INNER JOIN SPP_EST_INSCRIT ei 
            ON ei.SPP_CLASSE_ID = c.SPP_CLASSE_ID
        INNER JOIN SPP_ELEVE e 
            ON e.SPP_UTIL_ID = ei.SPP_UTIL_ID
        WHERE sc.SPP_UTIL_ID = :enseignantId
        ORDER BY e.SPP_UTIL_NOM ASC
    ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['enseignantId' => $enseignantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // Mettre à jour le statut d'une présence
    public static function updateStatutPresence($eleveId, $seanceId, $status)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO SPP_ENSEI_SEAN (SPP_UTIL_ID, SPP_SEAN_ID, SPP_ENS_SEAN_STATUS)
            VALUES (:enseignantId, :seanceId, :status)
            ON DUPLICATE KEY UPDATE SPP_ENS_SEAN_STATUS = :status
        ");
        return $stmt->execute([
            'enseignantId' => $_SESSION['user_id'],
            'seanceId'     => $seanceId,
            'status'       => $status
        ]);
    }
}
