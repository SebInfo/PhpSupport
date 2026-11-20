<?php
require __DIR__ . '/../vendor/autoload.php';

$configuration = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    $configuration['dsn'],
    $configuration['utilisateur'],
    $configuration['mot_de_passe'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$listeTaches = new App\ListeTaches($pdo);
$titreSaisi = '';
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
        if (!isset($_POST['titre']) || !is_string($_POST['titre'])) {
            $message = 'Le titre envoyé est invalide.';
        } elseif (!mb_check_encoding($_POST['titre'], 'UTF-8')) {
            $message = 'Le titre doit être encodé en UTF-8.';
        } else {
            $titreSaisi = trim($_POST['titre']);
            $longueurTitre = mb_strlen($titreSaisi, 'UTF-8');

            if ($longueurTitre < 1 || $longueurTitre > 120) {
                $message = 'Le titre doit contenir entre 1 et 120 caractères.';
            } else {
                $listeTaches->ajouter($titreSaisi);
                header('Location: index.php?message=ajoutee', true, 303);
                exit;
            }
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
            <?= htmlspecialchars($tache['titre'], ENT_QUOTES, 'UTF-8') ?>

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
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" id="formulaire-tache">
    <input type="hidden" name="action" value="ajouter">
    <label for="titre">Nouvelle tâche</label>
    <input
        type="text"
        id="titre"
        name="titre"
        value="<?= htmlspecialchars($titreSaisi, ENT_QUOTES, 'UTF-8') ?>"
        required
        maxlength="120"
    >
    <button type="submit">Ajouter</button>
</form>

<script src="validation.js"></script>
