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
     * Récupérer les séances d’un enseignant
     */
    public function findByEnseignant(int $enseignantId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*
             FROM SPP_SEANCE s
             JOIN SPP_ENSEI_SEAN es ON s.SPP_SEAN_ID = es.SPP_SEAN_ID
             WHERE es.SPP_UTIL_ID = :enseignantId"
        );
        $stmt->bindParam(':enseignantId', $enseignantId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt->bindParam(':heureDebut', $heureDebut);
        $stmt->bindParam(':seanId', $seanId, PDO::PARAM_INT);

        return $stmt->execute();
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
        $stmt = $this->db->prepare(
            "SELECT SPP_SEAN_HEURE_DEB, SPP_SEAN_HEURE_FIN
             FROM SPP_SEANCE
             WHERE SPP_SEAN_ID = :seanId"
        );
        $stmt->execute(['seanId' => $seanId]);
        $seance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seance) {
            return false;
        }

        if (empty($seance['SPP_SEAN_HEURE_DEB'])) {
            return $this->updateHeureDebut($seanId, $heure);
        }

        if (empty($seance['SPP_SEAN_HEURE_FIN'])) {
            return $this->updateHeureFin($seanId, $heure);
        }

        return false;
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
}

/*
--------------------------------------------------
NOTES PÉDAGOGIQUES IMPORTANTES
--------------------------------------------------
✔ SPP_SEANCE = table de présence
✔ 1 clic = 1 action claire
✔ updateHeureDebut() / updateHeureFin() = idéal pour AJAX
✔ marquerPresence() conservée pour compatibilité
✔ Le Controller décide QUOI appeler
✔ La Vue ne contient AUCUNE logique métier
--------------------------------------------------
*/
