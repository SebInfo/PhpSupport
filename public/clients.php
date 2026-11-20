<?php

declare(strict_types=1);

use App\ListeClients;

require __DIR__ . '/../vendor/autoload.php';

header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

$estHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('formulaire_pro');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $estHttps,
    'samesite' => 'Lax',
]);
session_start();

if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/**
 * Échappe une valeur avant son affichage HTML.
 */
function e(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirige après une écriture afin d'éviter la répétition d'un POST.
 */
function retourListe(string $message): never
{
    header('Location: clients.php?message=' . rawurlencode($message), true, 303);
    exit;
}

/**
 * @param array<string, mixed> $source
 */
function idValide(array $source, string $cle = 'id'): ?int
{
    $id = $source[$cle] ?? null;

    if (!is_string($id)) {
        return null;
    }

    $id = filter_var($id, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);

    return $id === false ? null : $id;
}

/**
 * @param array<string, mixed> $source
 * @return array{0: array{nom: string, prenom: string, email: string, telephone: ?string}, 1: array<string, string>}
 */
function validerClient(array $source): array
{
    $normaliser = static function (mixed $valeur): string {
        if (!is_string($valeur) || !mb_check_encoding($valeur, 'UTF-8')) {
            return '';
        }

        return mb_trim($valeur, null, 'UTF-8');
    };

    $client = [
        'nom' => $normaliser($source['nom'] ?? null),
        'prenom' => $normaliser($source['prenom'] ?? null),
        'email' => mb_strtolower($normaliser($source['email'] ?? null), 'UTF-8'),
        'telephone' => $normaliser($source['telephone'] ?? null),
    ];
    $erreurs = [];
    $nomValide = static fn (string $valeur): bool => preg_match(
        "/\\A[\\p{L}][\\p{L}\\p{M}' -]*\\z/u",
        $valeur
    ) === 1;

    foreach (['nom' => 'nom', 'prenom' => 'prénom'] as $cle => $libelle) {
        if (
            $client[$cle] === ''
            || mb_strlen($client[$cle], 'UTF-8') > 100
            || !$nomValide($client[$cle])
        ) {
            $erreurs[$cle] = 'Le ' . $libelle . ' doit contenir de 1 à 100 lettres.';
        }
    }

    if (
        $client['email'] === ''
        || mb_strlen($client['email'], 'UTF-8') > 254
        || filter_var($client['email'], FILTER_VALIDATE_EMAIL) === false
    ) {
        $erreurs['email'] = 'Saisissez une adresse e-mail valide.';
    }

    if (
        $client['telephone'] !== ''
        && (
            mb_strlen($client['telephone'], 'UTF-8') > 25
            || preg_match('/\\A\\+?[0-9 .()-]{6,25}\\z/', $client['telephone']) !== 1
        )
    ) {
        $erreurs['telephone'] = 'Le téléphone doit contenir de 6 à 25 chiffres ou séparateurs.';
    }

    if ($client['telephone'] === '') {
        $client['telephone'] = null;
    }

    return [$client, $erreurs];
}

$configuration = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    $configuration['dsn'],
    $configuration['utilisateur'],
    $configuration['mot_de_passe'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
$clients = new ListeClients($pdo);
$client = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => null];
$erreurs = [];
$erreurGenerale = '';
$idEdition = null;
$messages = [
    'cree' => 'Le client a été créé.',
    'modifie' => 'Le client a été modifié.',
    'supprime' => 'Le client a été supprimé.',
];
$message = isset($_GET['message']) && is_string($_GET['message'])
    ? ($messages[$_GET['message']] ?? '')
    : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? null;
    $jetonCsrf = $_POST['csrf'] ?? null;

    if (
        !is_string($action)
        || !is_string($jetonCsrf)
        || !hash_equals($_SESSION['csrf'], $jetonCsrf)
    ) {
        http_response_code(403);
        $erreurGenerale = 'La requête a expiré ou est invalide. Rechargez la page puis réessayez.';
    } elseif ($action === 'creer' || $action === 'modifier') {
        [$client, $erreurs] = validerClient($_POST);
        $idEdition = $action === 'modifier' ? idValide($_POST) : null;

        if ($action === 'modifier' && $idEdition === null) {
            http_response_code(400);
            $erreurGenerale = 'Identifiant client invalide.';
        } elseif ($erreurs !== []) {
            http_response_code(422);
        } else {
            try {
                if ($action === 'creer') {
                    $clients->creer($client);
                    retourListe('cree');
                }

                $clients->modifier($idEdition, $client);
                retourListe('modifie');
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                http_response_code(500);
                $erreurGenerale = $exception->getCode() === '23000'
                    ? 'Cette adresse e-mail est déjà associée à un client.'
                    : 'Une erreur technique est survenue. Réessayez plus tard.';
            }
        }
    } elseif ($action === 'supprimer') {
        $id = idValide($_POST);

        if ($id === null) {
            http_response_code(400);
            $erreurGenerale = 'Identifiant client invalide.';
        } else {
            try {
                $clients->supprimer($id);
                retourListe('supprime');
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                http_response_code(500);
                $erreurGenerale = 'Une erreur technique est survenue. Réessayez plus tard.';
            }
        }
    } else {
        http_response_code(400);
        $erreurGenerale = 'Action non autorisée.';
    }
} elseif (isset($_GET['id'])) {
    $idEdition = idValide($_GET);

    if ($idEdition === null || ($clientTrouve = $clients->trouver($idEdition)) === null) {
        http_response_code(404);
        $erreurGenerale = 'Client introuvable.';
        $idEdition = null;
    } else {
        $client = $clientTrouve;
    }
}

$listeClients = $clients->tous();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des clients</title>
</head>
<body>
    <header>
        <h1>Gestion des clients</h1>
        <nav aria-label="Navigation principale">
            <a href="index.php">Tâches</a>
            <a href="clients.php">Clients</a>
        </nav>
    </header>

    <?php if ($message !== ''): ?>
        <p role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($erreurGenerale !== ''): ?>
        <p role="alert"><?= e($erreurGenerale) ?></p>
    <?php endif; ?>

    <section aria-labelledby="titre-liste-clients">
        <h2 id="titre-liste-clients">Clients enregistrés</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Prénom</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Téléphone</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listeClients as $clientListe): ?>
                    <tr>
                        <td><?= e($clientListe['nom']) ?></td>
                        <td><?= e($clientListe['prenom']) ?></td>
                        <td><?= e($clientListe['email']) ?></td>
                        <td><?= e($clientListe['telephone'] ?? '') ?></td>
                        <td>
                            <a href="clients.php?id=<?= (int) $clientListe['id'] ?>">Modifier</a>
                            <form method="post" data-confirmation="Supprimer ce client ?">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= (int) $clientListe['id'] ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section aria-labelledby="titre-formulaire-client">
        <h2 id="titre-formulaire-client">
            <?= $idEdition === null ? 'Nouveau client' : 'Modifier le client' ?>
        </h2>

        <form method="post" id="formulaire-client" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="<?= $idEdition === null ? 'creer' : 'modifier' ?>">
            <?php if ($idEdition !== null): ?>
                <input type="hidden" name="id" value="<?= $idEdition ?>">
            <?php endif; ?>

            <p><small>Les champs marqués d’un astérisque sont obligatoires.</small></p>

            <p>
                <label for="nom">Nom *</label>
                <?php if (isset($erreurs['nom'])): ?>
                    <span id="erreur-nom" role="alert"><?= e($erreurs['nom']) ?></span>
                <?php endif; ?>
                <input
                    id="nom"
                    name="nom"
                    type="text"
                    value="<?= e($client['nom']) ?>"
                    autocomplete="family-name"
                    maxlength="100"
                    required
                    aria-invalid="<?= isset($erreurs['nom']) ? 'true' : 'false' ?>"
                    <?= isset($erreurs['nom']) ? 'aria-describedby="erreur-nom"' : '' ?>
                >
            </p>

            <p>
                <label for="prenom">Prénom *</label>
                <?php if (isset($erreurs['prenom'])): ?>
                    <span id="erreur-prenom" role="alert"><?= e($erreurs['prenom']) ?></span>
                <?php endif; ?>
                <input
                    id="prenom"
                    name="prenom"
                    type="text"
                    value="<?= e($client['prenom']) ?>"
                    autocomplete="given-name"
                    maxlength="100"
                    required
                    aria-invalid="<?= isset($erreurs['prenom']) ? 'true' : 'false' ?>"
                    <?= isset($erreurs['prenom']) ? 'aria-describedby="erreur-prenom"' : '' ?>
                >
            </p>

            <p>
                <label for="email">E-mail *</label>
                <?php if (isset($erreurs['email'])): ?>
                    <span id="erreur-email" role="alert"><?= e($erreurs['email']) ?></span>
                <?php endif; ?>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="<?= e($client['email']) ?>"
                    autocomplete="email"
                    maxlength="254"
                    required
                    aria-invalid="<?= isset($erreurs['email']) ? 'true' : 'false' ?>"
                    <?= isset($erreurs['email']) ? 'aria-describedby="erreur-email"' : '' ?>
                >
            </p>

            <p>
                <label for="telephone">Téléphone</label>
                <?php if (isset($erreurs['telephone'])): ?>
                    <span id="erreur-telephone" role="alert"><?= e($erreurs['telephone']) ?></span>
                <?php endif; ?>
                <input
                    id="telephone"
                    name="telephone"
                    type="tel"
                    value="<?= e($client['telephone'] ?? '') ?>"
                    autocomplete="tel"
                    inputmode="tel"
                    maxlength="25"
                    aria-invalid="<?= isset($erreurs['telephone']) ? 'true' : 'false' ?>"
                    <?= isset($erreurs['telephone']) ? 'aria-describedby="erreur-telephone"' : '' ?>
                >
            </p>

            <button type="submit">
                <?= $idEdition === null ? 'Créer le client' : 'Enregistrer les modifications' ?>
            </button>
            <?php if ($idEdition !== null): ?>
                <a href="clients.php">Annuler</a>
            <?php endif; ?>
        </form>
    </section>

    <script src="clients.js" defer></script>
</body>
</html>
