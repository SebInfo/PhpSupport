<?php

namespace App;

use PDO;

class ListeTaches
{
    public function __construct(private PDO $pdo)
    {
    }

    public function toutes(): array
    {
        $requete = $this->pdo->query(
            'SELECT id, titre, terminee FROM taches ORDER BY id'
        );

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ajouter(string $titre): void
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO taches (titre, terminee) VALUES (:titre, 0)'
        );
        $requete->execute(['titre' => $titre]);
    }

    public function terminer(int $id): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE taches SET terminee = 1 WHERE id = :id'
        );
        $requete->execute(['id' => $id]);
    }

    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare(
            'DELETE FROM taches WHERE id = :id'
        );
        $requete->execute(['id' => $id]);
    }
}
