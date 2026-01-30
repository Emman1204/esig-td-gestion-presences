<?php

// Classe qui représente la table SPP_ENSEIGNANT
class Enseignant
{
    /**
     * @var PDO
     * Stocke la connexion à la base de données
     */
    private PDO $db;

    /**
     * Constructeur de la classe
     * On injecte l'objet PDO depuis l'extérieur
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère tous les enseignants de la base de données
     *
     * @return array
     */
    public function findAll(): array
    {
        // Requête SQL pour sélectionner tous les enseignants
        $sql = "SELECT * FROM SPP_ENSEIGNANT";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        // Exécution de la requête
        $stmt->execute();

        // Retourne toutes les lignes sous forme de tableau associatif
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
