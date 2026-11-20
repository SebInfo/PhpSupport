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
}
