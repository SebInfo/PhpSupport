const formulaire = document.querySelector('#formulaire-tache');
const champTitre = document.querySelector('#titre');

function verifierTitre() {
   if (champTitre.value.trim() === '') {
        champTitre.setCustomValidity('Saisissez un titre.');
    } else {
        champTitre.setCustomValidity('');
    }
}

champTitre.addEventListener('input', verifierTitre);
formulaire.addEventListener('submit', verifierTitre);
