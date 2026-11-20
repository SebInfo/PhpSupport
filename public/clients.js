const formulaireClient = document.querySelector('#formulaire-client');

if (formulaireClient !== null) {
    const champsTexte = [
        formulaireClient.querySelector('#nom'),
        formulaireClient.querySelector('#prenom'),
    ];
    const telephone = formulaireClient.querySelector('#telephone');

    function verifierNom(champ) {
        const valeur = champ.value.trim();
        const estValide = /^[\p{L}][\p{L}\p{M}' -]*$/u.test(valeur);
        champ.setCustomValidity(
            estValide ? '' : 'Saisissez un nom ou un prénom composé de lettres.'
        );
    }

    function verifierTelephone() {
        const valeur = telephone.value.trim();
        const estValide = valeur === '' || /^\+?[0-9 .()-]{6,25}$/.test(valeur);
        telephone.setCustomValidity(
            estValide ? '' : 'Saisissez un numéro de téléphone valide.'
        );
    }

    champsTexte.forEach((champ) => {
        champ.addEventListener('input', () => verifierNom(champ));
    });
    telephone.addEventListener('input', verifierTelephone);

    formulaireClient.addEventListener('submit', (evenement) => {
        champsTexte.forEach(verifierNom);
        verifierTelephone();

        if (!formulaireClient.checkValidity()) {
            evenement.preventDefault();
            formulaireClient.reportValidity();
        }
    });
}

document.querySelectorAll('[data-confirmation]').forEach((formulaireSuppression) => {
    formulaireSuppression.addEventListener('submit', (evenement) => {
        if (!window.confirm(formulaireSuppression.dataset.confirmation)) {
            evenement.preventDefault();
        }
    });
});
