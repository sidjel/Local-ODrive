<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container-fluid px-0">
        <div class="row mx-0">
            <div class="col-12">
                <p class="mb-2">&copy; 2024 LocalO'drive - Tous droits réservés.</p>
                <p class="mb-3">Contactez-nous : contact@localodrive.fr</p>
                <div class="social-links mb-3">
                    <a href="#" class="text-white mx-2"><i class="fa fa-facebook"></i></a>
                    <a href="#" class="text-white mx-2"><i class="fa fa-twitter"></i></a>
                    <a href="#" class="text-white mx-2"><i class="fa fa-instagram"></i></a>
                </div>
                <div>
                    <a href="<?php echo isset($gitUrl) ? $gitUrl : 'https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive'; ?>" 
                       target="_blank" 
                       class="text-white text-decoration-none">
                        <i class="fa fa-github"></i> Voir le projet sur Git
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Reset des styles de base */
html, body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    width: 100%;
    overflow-x: hidden;
}

/* Style du footer */
footer {
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    padding-left: 0;
    padding-right: 0;
    box-sizing: border-box;
}

/* Styles pour les liens sociaux */
.social-links a {
    transition: opacity 0.3s ease;
}

.social-links a:hover {
    opacity: 0.8;
}

/* Media queries pour le responsive */
@media (max-width: 768px) {
    footer {
        padding: 1rem 0;
    }
    
    .social-links {
        margin-bottom: 1rem;
    }
}
</style>
