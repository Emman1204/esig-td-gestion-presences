<?php

class User
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Trouver un utilisateur par email
     */
 public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT SPP_UTIL_ID, SPP_UTIL_NOM, SPP_UTIL_PRENOM, SPP_UTIL_EMAIL, SPP_UTIL_MDP, 'eleve' AS SPP_UTIL_ROLE
            FROM SPP_ELEVE
            WHERE SPP_UTIL_EMAIL = :email

            UNION

            SELECT SPP_UTIL_ID, SPP_UTIL_NOM, SPP_UTIL_PRENOM, SPP_UTIL_EMAIL, SPP_UTIL_MDP, 'enseignant' AS SPP_UTIL_ROLE
            FROM SPP_ENSEIGNANT
            WHERE SPP_UTIL_EMAIL = :email
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}
