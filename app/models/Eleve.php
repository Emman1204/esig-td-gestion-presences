<?php

// On déclare la classe Eleve
// Cette classe représente la table SPP_ELEVE
class Eleve
{
    /**
     * @var PDO
     * Stocke la connexion à la base de données
     */
    private PDO $db;

    /**
     * Constructeur de la classe
     * Il reçoit l'objet PDO depuis l'extérieur
     * (principe d'injection de dépendance)
     */
    public function __construct(PDO $db)
    {
        // On stocke la connexion PDO dans la propriété $db
        $this->db = $db;
    }

    /**
     * Récupère tous les élèves de la base de données
     *
     * @return array
     */
    public function findAll(): array
    {
        // Requête SQL simple
        // On sélectionne tous les champs de la table SPP_ELEVE
        $sql = "SELECT * FROM SPP_ELEVE";

        // On prépare la requête (sécurité + performance)
        $stmt = $this->db->prepare($sql);

        // On exécute la requête
        $stmt->execute();

        // On récupère toutes les lignes sous forme de tableau associatif
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
