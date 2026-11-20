<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Accès aux données de la table clients.
 */
class ListeClients
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, nom: string, prenom: string, email: string, telephone: ?string}>
     */
    public function tous(): array
    {
        $requete = $this->pdo->query(
            'SELECT id, nom, prenom, email, telephone FROM clients ORDER BY nom, prenom, id'
        );

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array{id: int, nom: string, prenom: string, email: string, telephone: ?string}|null
     */
    public function trouver(int $id): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT id, nom, prenom, email, telephone FROM clients WHERE id = :id'
        );
        $requete->execute(['id' => $id]);

        $client = $requete->fetch(PDO::FETCH_ASSOC);

        return $client === false ? null : $client;
    }

    /**
     * @param array{nom: string, prenom: string, email: string, telephone: ?string} $client
     */
    public function creer(array $client): void
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO clients (nom, prenom, email, telephone)
             VALUES (:nom, :prenom, :email, :telephone)'
        );
        $requete->execute($client);
    }

    /**
     * @param array{nom: string, prenom: string, email: string, telephone: ?string} $client
     */
    public function modifier(int $id, array $client): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE clients
             SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone
             WHERE id = :id'
        );
        $requete->execute($client + ['id' => $id]);
    }

    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare('DELETE FROM clients WHERE id = :id');
        $requete->execute(['id' => $id]);
    }
}
