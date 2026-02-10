<?php include '../includes/header.php'; ?>

<section class="py-5 mt-5">
    <div class="container">
        
        <!-- Profile Hero -->
        <div class="row align-items-center mb-5 pb-5 border-bottom border-light border-opacity-10">
            <div class="col-lg-5 mb-4 mb-lg-0 text-center text-lg-start">
                <div class="position-relative d-inline-block">
                    <!-- Placeholder for profile picture -->
                    <div class="rounded-circle p-1 bg-gradient-to-br from-primary to-purple d-inline-block mb-4">
                        <img src="https://media.licdn.com/dms/image/v2/D4E03AQH5AN2bQQZUmg/profile-displayphoto-scale_200_200/B4EZxB5vFqHgAY-/0/1770632181103?e=1772064000&v=beta&t=HlsFwMEbWP2R-5vASKRrxq2ElWNQqAHU7JJKXaIwOH4" alt="Thomas Lapierre" class="rounded-circle shadow-lg" style="width: 160px; height: 160px; border: 4px solid var(--bg-deep);">
                    </div>
                </div>
                <h1 class="display-title mb-2" style="font-size: 3rem;">Thomas Lapierre</h1>
                <p class="text-gradient-purple fw-bold fs-4 mb-3"><?php echo $lang === 'en' ? 'AI & Automation Expert' : 'Expert IA & Automatisation'; ?></p>
                <div class="d-flex gap-3 justify-content-center justify-content-lg-start mt-4">
                    <a href="https://www.linkedin.com/in/lapierre-thomas/" target="_blank" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fab fa-linkedin me-2"></i> LinkedIn
                    </a>
                </div>
            </div>
            
            <div class="col-lg-6 offset-lg-1">
                <div class="bento-card p-4 p-md-5">
                    <h3 class="text-white mb-4"><?php echo $lang === 'en' ? 'My Background' : 'Mon Parcours'; ?></h3>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div>
                                <h5 class="text-white h6 mb-1"><?php echo $lang === 'en' ? 'Technical Expertise' : 'Expertise Technique'; ?></h5>
                                <p class="text-secondary small m-0"><?php echo $lang === 'en' ? 'Complete mastery of the "Modern Data" stack: Make, n8n, and Notion DATA API.' : 'Maîtrise complète de la stack "Modern Data" : Make, n8n, et API DATA Notion.'; ?></p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div>
                                <h5 class="text-white h6 mb-1"><?php echo $lang === 'en' ? 'Practical Pedagogy' : 'Pédagogie de Terrain'; ?></h5>
                                <p class="text-secondary small m-0"><?php echo $lang === 'en' ? 'Certified trainer, I have mentored +50 profiles, from beginners to CTOs.' : 'Formateur certifié, j\'ai accompagné +50 profils, du débutant au CTO.'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certifications Hoshin Section -->
        <div class="row align-items-center mb-5 pb-5">
            <div class="col-lg-12 text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill mb-3"><?php echo $lang === 'en' ? 'Certified Excellence' : 'Excellence Certifiée'; ?></span>
                <h2 class="display-4 fw-bold text-white">Certifié HOSHIN Partners</h2>
                <p class="text-secondary mx-auto mt-3" style="max-width: 700px;">
                    <?php echo $lang === 'en' ? 'Trained in operational excellence and AI by <strong>Dr. Mohamed KOUJILI</strong>, Master Black Belt Lean Six Sigma.' : 'Formé à l\'excellence opérationnelle et à l\'IA par le <strong>Dr. Mohamed KOUJILI</strong>, Master Black Belt Lean Six Sigma.'; ?>
                </p>
            </div>

            <div class="col-md-6 mb-4 mb-md-0">
                <div class="bento-card p-5 h-100 text-center shadow-lg" style="border-width: 1px; --glass-border: rgba(41, 151, 255, 0.3);">
                    <div class="icon-box mx-auto text-white mb-4" style="width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.04);">
                        <i class="fas fa-user-astronaut fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="https://media.licdn.com/dms/image/v2/D4D2DAQHJNmGfpCrp2w/profile-treasury-document-images_800/B4DZwdTY_IHMAc-/1/1770018150201?e=1770854400&v=beta&t=n2IJGkhd-EUlUg-jZ0CeB5fFx4mhC8wcR3ZT8u-1SDs" alt="Certification Niveau 1" style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo $lang === 'en' ? 'The Augmented Employee' : 'Le Collaborateur Augmenté'; ?></h3>
                    <p class="text-primary fw-bold text-uppercase small tracking-wide mb-4"><?php echo $lang === 'en' ? 'Certification Level 1' : 'Certification Niv. 1'; ?></p>
                    <p class="text-secondary mb-4">
                        <?php echo $lang === 'en' ? 'Master AI tools to boost individual productivity. Automate daily tasks and enhance cognitive abilities.' : 'Maîtrise des outils d\'IA pour booster la productivité individuelle. Automatisation des tâches quotidiennes et augmentation des capacités cognitives.'; ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bento-card p-5 h-100 text-center" style="background: linear-gradient(145deg, rgba(20,20,20,0.6), rgba(40,40,40,0.4));">
                    <div class="icon-box mx-auto text-white bg-gradient-to-br from-purple to-pink mb-4" style="width: 70px; height: 70px; border-radius: 50%; background-color: #bf5af2;">
                        <i class="fas fa-rocket fs-2"></i>
                    </div>
                    <div class="cert-image mb-3">
                        <img src="https://media.licdn.com/dms/image/v2/D4D2DAQFAZ8mycPyVag/profile-treasury-document-images_800/B4DZwdScgZIUAc-/1/1770017902538?e=1770854400&v=beta&t=otZlz3fJz65gsenqReI5t4QKSAAbl43oMk43RcRD_8Q" alt="Certification Niveau 2" style="max-width:100%; height:auto; border-radius:8px;">
                    </div>
                    <h3 class="text-white mb-2"><?php echo $lang === 'en' ? 'The Augmented Enterprise' : 'L\'Entreprise Augmentée'; ?></h3>
                    <p class="text-white-50 fw-bold text-uppercase small tracking-wide mb-4"><?php echo $lang === 'en' ? 'Certification Level 2 (Advanced)' : 'Certification Niv. 2 (Avancé)'; ?></p>
                    <p class="text-secondary mb-4">
                        <?php echo $lang === 'en' ? 'Strategic deployment of AI at organizational scale. Transformation of business processes and integration of AI in the value chain.' : 'Déploiement stratégique de l\'IA à l\'échelle organisationnelle. Transformation des processus métiers et intégration de l\'IA dans la chaîne de valeur.'; ?>
                    </p>
                    <div class="d-inline-block px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white small">
                        <i class="fas fa-check-circle me-2 text-success"></i>Nyon, Dec 2025
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="text-center pb-5">
            <div class="bento-card d-inline-block p-5 text-center">
                <h3 class="text-white mb-3"><?php echo $lang === 'en' ? 'Interested in collaborating?' : 'Envie de collaborer ?'; ?></h3>
                <p class="text-secondary mb-4"><?php echo $lang === 'en' ? 'Let\'s discuss your project and see how the Hoshin approach can help you.' : 'Discutons de votre projet et voyons comment l\'approche Hoshin peut vous aider.'; ?></p>
                <a href="/contact" class="btn-apple"><?php echo $lang === 'en' ? 'Get in Touch' : 'Prendre Contact'; ?> <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        </div>

    </div>
</section>

<?php include '../includes/footer.php'; ?>
