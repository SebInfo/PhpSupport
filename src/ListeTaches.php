<?php

namespace App;

use Medoo\Medoo;
use PDO;

class ListeTaches
{
    private Medoo $baseDeDonnees;

    public function __construct(PDO $pdo)
    {
        $this->baseDeDonnees = new Medoo([
            'type' => 'mysql',
            'pdo' => $pdo,
        ]);
    }

    public function toutes(): array
    {
        return $this->baseDeDonnees->select(
            'taches',
            ['id', 'titre', 'terminee'],
            ['ORDER' => ['id' => 'ASC']]
        ) ?? [];
    }

    public function ajouter(string $titre): void
    {
        $this->baseDeDonnees->insert('taches', [
            'titre' => $titre,
            'terminee' => 0,
        ]);
    }

    public function terminer(int $id): void
    {
        $this->baseDeDonnees->update(
            'taches',
            ['terminee' => 1],
            ['id' => $id]
        );
    }

    public function supprimer(int $id): void
    {
        $this->baseDeDonnees->delete('taches', ['id' => $id]);
    }
}
