<?php
require __DIR__ . '/../vendor/autoload.php';

/**
 * Échappe un texte avant de l'insérer dans le HTML.
 */
function e(string $texte): string
{
    return htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$configuration = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    $configuration['dsn'],
    $configuration['utilisateur'],
    $configuration['mot_de_passe'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$listeTaches = new App\ListeTaches($pdo);
$titre = '';
$erreur = '';
$message = '';

if (isset($_GET['message']) && $_GET['message'] === 'ajoutee') {
    $message = 'La tâche a été ajoutée.';
}

if (isset($_GET['message']) && $_GET['message'] === 'terminee') {
    $message = 'La tâche a été terminée.';
}

if (isset($_GET['message']) && $_GET['message'] === 'supprimee') {
    $message = 'La tâche a été supprimée.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $brut = $_POST['titre'] ?? '';
        $titre = is_string($brut) && mb_check_encoding($brut, 'UTF-8')
            ? mb_trim($brut, null, 'UTF-8')
            : '';

        if ($titre === '' || mb_strlen($titre, 'UTF-8') > 120) {
            http_response_code(422);
            $erreur = 'Le titre doit contenir entre 1 et 120 caractères.';
        } else {
            $listeTaches->ajouter($titre);
            header('Location: index.php?message=ajoutee', true, 303);
            exit;
        }
    } elseif (
        !isset($_POST['id'])
        || !is_string($_POST['id'])
        || !ctype_digit($_POST['id'])
        || (int) $_POST['id'] < 1
    ) {
        $message = 'L’identifiant de la tâche est invalide.';
    } elseif ($action === 'terminer') {
        $listeTaches->terminer((int) $_POST['id']);
        header('Location: index.php?message=terminee', true, 303);
        exit;
    } elseif ($action === 'supprimer') {
        $listeTaches->supprimer((int) $_POST['id']);
        header('Location: index.php?message=supprimee', true, 303);
        exit;
    } else {
        $message = 'L’action demandée est invalide.';
    }
}

$taches = $listeTaches->toutes();
?>
<h1>Mes tâches</h1>

<ul>
    <?php foreach ($taches as $tache): ?>
        <li>
            <?= e($tache['titre']) ?>

            <?php if ((int) $tache['terminee'] === 1): ?>
                <strong>Terminée</strong>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="action" value="terminer">
                    <input type="hidden" name="id" value="<?= (int) $tache['id'] ?>">
                    <button type="submit">Terminer</button>
                </form>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id" value="<?= (int) $tache['id'] ?>">
                <button type="submit">Supprimer</button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($message !== ''): ?>
    <p><?= e($message) ?></p>
<?php endif; ?>

<form method="post" id="formulaire-tache">
    <input type="hidden" name="action" value="ajouter">
    <label for="titre">Nouvelle tâche</label>
    <?php if ($erreur !== ''): ?>
        <p role="alert"><?= e($erreur) ?></p>
    <?php endif; ?>
    <input
        type="text"
        id="titre"
        name="titre"
        value="<?= e($titre) ?>"
        required
        maxlength="120"
    >
    <button type="submit">Ajouter</button>
</form>

<script src="validation.js"></script>
