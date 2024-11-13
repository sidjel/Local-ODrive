document.addEventListener("DOMContentLoaded", function() {
    const learnMoreBtn = document.getElementById('learnMoreBtn');
    if (learnMoreBtn) {
        learnMoreBtn.addEventListener('click', function() {
            alert('Merci de votre intérêt ! Plus d\'informations seront disponibles bientôt.');
        });
    }
});