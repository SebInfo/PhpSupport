const formulaire = document.querySelector('#formulaire-tache');
const champTitre = document.querySelector('#titre');

function verifierTitre() {
    if (champTitre.value.trim() === '' && champTitre.value !== '') {
        champTitre.setCustomValidity('Le titre ne peut pas contenir uniquement des espaces.');
    } else {
        champTitre.setCustomValidity('');
    }
}

champTitre.addEventListener('input', verifierTitre);
formulaire.addEventListener('submit', verifierTitre);
