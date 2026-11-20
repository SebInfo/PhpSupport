<?php
require __DIR__ . '/../vendor/autoload.php';

$taches = App\ListeTaches::toutes();
?>
<h1>Mes tâches</h1>

<ul>
    <?php foreach ($taches as $tache): ?>
        <li><?= htmlspecialchars($tache, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
</ul>

<form method="post" id="formulaire-tache">
    <label for="titre">Nouvelle tâche</label>
    <input type="text" id="titre" name="titre" required maxlength="120">
    <button type="submit">Ajouter</button>
</form>

<script src="validation.js"></script>
