<?php include '../includes/header.php'; ?>

<section class="py-5" style="padding-top: 120px !important;">
    <div class="container">
        
        <h1 class="display-title mb-5 text-center"><?php echo $lang === 'en' ? 'Legal Information' : 'Informations Légales'; ?></h1>

        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="d-flex flex-column gap-2 sticky-top" style="top: 120px;">
                    <a href="formation" class="btn btn-apple-glass text-start"><?php echo t('formations'); ?></a>
                    <a href="#privacy" class="btn btn-apple-glass text-start"><?php echo $lang === 'en' ? 'Privacy Policy' : 'Confidentialité'; ?></a>
                    <a href="#terms" class="btn btn-apple-glass text-start"><?php echo $lang === 'en' ? 'Terms of Sale' : 'CGV'; ?></a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                
                <!-- ENTITY INFO -->
                <div id="mentions" class="bento-card mb-5 scroll-reveal">
                    <h3 class="text-white mb-4"><?php echo $lang === 'en' ? '1. Legal Notice' : '1. Mentions Légales'; ?></h3>
                    <div class="text-secondary">
                        <?php if ($lang === 'en'): ?>
                            <p><strong>Site Editor:</strong><br>
                            SlapIA Inc.<br>
                            Email: contact@javabien.ovh</p>
                            
                            <p><strong>Hosting Provider:</strong><br>
                            Synology NAS / Local Host<br>
                            Infrastructure managed by SlapIA IT Team.</p>
                        <?php
else: ?>
                            <p><strong>Éditeur du site :</strong><br>
                            SlapIA Inc.<br>
                            Email : contact@javabien.ovh</p>
                            
                            <p><strong>Hébergement :</strong><br>
                            Synology NAS / Local Host<br>
                            Infrastructure gérée par l'équipe technique SlapIA.</p>
                        <?php
endif; ?>
                    </div>
                </div>

                <!-- PRIVACY POLICY -->
                <div id="privacy" class="bento-card mb-5 scroll-reveal">
                    <h3 class="text-white mb-4"><?php echo $lang === 'en' ? '2. Privacy & Data Policy' : '2. Politique de Confidentialité'; ?></h3>
                    <div class="text-secondary">
                        <?php if ($lang === 'en'): ?>
                            <div class="alert alert-dark border border-secondary border-opacity-25" role="alert">
                                <i class="fas fa-shield-alt text-primary me-2"></i> <strong>Your Privacy is our Priority.</strong>
                            </div>
                            
                            <h5 class="text-white mt-4">Data Storage</h5>
                            <p>We do not sell your personal data. All information collected (names, emails) is stored securely within our private <strong>Notion workspace</strong>, used solely for administrative and educational follow-up.</p>
                            
                            <h5 class="text-white mt-4">Cookies & Tracking</h5>
                            <p><strong>No tracking cookies are used on this site.</strong> We do not collect behavioral data for advertising purposes. You browse anonymously without being tracked by third-party pixels.</p>
                            
                            <h5 class="text-white mt-4">Data Usage</h5>
                            <p>Your data is used strictly to:</p>
                            <ul>
                                <li>Send you course materials.</li>
                                <li>Contact you regarding your training progress.</li>
                                <li>Provide technical support.</li>
                            </ul>
                        <?php
else: ?>
                            <div class="alert alert-dark border border-secondary border-opacity-25" role="alert">
                                <i class="fas fa-shield-alt text-primary me-2"></i> <strong>Votre vie privée est notre priorité.</strong>
                            </div>
                            
                            <h5 class="text-white mt-4">Stockage des Données</h5>
                            <p>Nous ne vendons pas vos données personnelles. Toutes les informations collectées (noms, emails) sont stockées de manière sécurisée directement dans notre espace privé <strong>Notion</strong>, utilisé uniquement pour le suivi administratif et pédagogique.</p>
                            
                            <h5 class="text-white mt-4">Cookies & Traçage</h5>
                            <p><strong>Aucun cookie de traçage publicitaire n'est utilisé sur ce site.</strong> Nous ne collectons aucune donnée comportementale à des fins publicitaires. Vous naviguez de manière anonyme sans être pisté par des pixels tiers.</p>
                            
                            <h5 class="text-white mt-4">Usage des Données</h5>
                            <p>Vos données servent strictement à :</p>
                            <ul>
                                <li>Vous envoyer les supports de cours.</li>
                                <li>Vous contacter pour le suivi de votre formation.</li>
                                <li>Assurer le support technique.</li>
                            </ul>
                        <?php
endif; ?>
                    </div>
                </div>

                <!-- TERMS OF SALE (CGV) -->
                <div id="terms" class="bento-card mb-5 scroll-reveal">
                    <h3 class="text-white mb-4"><?php echo $lang === 'en' ? '3. Terms of Sale (CGV)' : '3. Conditions Générales de Vente (CGV)'; ?></h3>
                    <div class="text-secondary">
                        <?php if ($lang === 'en'): ?>
                            <p><strong>Services:</strong> SlapIA provides online training and personalized coaching in Artificial Intelligence.</p>
                            <p><strong>Payment:</strong> Payment is due before the start of the training session. Payments are processed securely.</p>
                            <p><strong>Cancellation:</strong> Cancellations made less than 48 hours before a scheduled session may not be refundable.</p>
                            <p><strong>Intellectual Property:</strong> All course materials are the property of SlapIA and may not be redistributed without permission.</p>
                        <?php
else: ?>
                            <p><strong>Services :</strong> SlapIA fournit des formations en ligne et un accompagnement personnalisé en Intelligence Artificielle.</p>
                            <p><strong>Paiement :</strong> Le règlement est dû avant le début de la session de formation. Les paiements sont sécurisés.</p>
                            <p><strong>Annulation :</strong> Toute annulation effectuée moins de 48 heures avant une session programmée pourra ne pas être remboursée.</p>
                            <p><strong>Propriété Intellectuelle :</strong> Tous les supports de cours restent la propriété exclusive d'SlapIA et ne peuvent être redistribués sans autorisation.</p>
                        <?php
endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
