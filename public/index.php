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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $message = 'Le titre est valide. Il sera enregistré à l’étape suivante.';
        }
    }
}

$taches = $listeTaches->toutes();
?>
<h1>Mes tâches</h1>

<ul>
    <?php foreach ($taches as $tache): ?>
        <li><?= htmlspecialchars($tache['titre'], ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
</ul>

<?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" id="formulaire-tache">
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
