<?php
class Classe
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Trouver une classe par son ID
     */
    public function findById(int $classeId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM SPP_CLASSE WHERE SPP_CLASSE_ID = :id");
        $stmt->execute(['id' => $classeId]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);

        return $classe ?: null;
    }

    /**
     * Lister toutes les classes (optionnel)
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM SPP_CLASSE ORDER BY SPP_CLASSE_NOM");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
