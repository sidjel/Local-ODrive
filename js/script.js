document.addEventListener("DOMContentLoaded", function () {
    const learnMoreBtn = document.getElementById('learnMoreBtn');
    if (learnMoreBtn) {
        learnMoreBtn.addEventListener('click', function () {
            alert('Merci de votre intérêt ! Plus d\'informations seront disponibles bientôt.');
        });
    }
});

document.body.style.cursor = "wait"; // Active le sablier

fetch('URL_DE_TON_API')
    .then(response => response.json())
    .then(data => {
        console.log(data);
    })
    .finally(() => {
        document.body.style.cursor = "default"; // Retour au curseur normal
    });