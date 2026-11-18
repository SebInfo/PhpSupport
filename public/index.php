<?php
require __DIR__ . '/../vendor/autoload.php';

#require __DIR__."/../src/ListeTaches.php";
$taches=App\ListeTaches::toutes();
dump($taches);
?>
<h1>Mes tâches</h1>
<ul>
    <?php foreach($taches as $tache): ?>
        <li><?= htmlspecialchars($tache, ENT_QUOTES, "UTF-8") ?></li>
    <?php endforeach; ?>
</ul>