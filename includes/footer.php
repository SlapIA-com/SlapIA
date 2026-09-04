<footer class="site-footer">
  <div class="container">
    <div class="footer__top">
      <div class="footer__brand">
        <a href="/index.php" class="logo"><img src="/assets/img/brand/logo.svg" alt="" class="logo__mark"> Slapia</a>
        <p><?php echo t('footer.tagline'); ?></p>
      </div>
      <div class="footer__col">
        <h4><?php echo t('footer.courses_heading'); ?></h4>
        <ul>
          <li><a href="/formations.php"><?php echo t('footer.courses_all'); ?></a></li>
          <li><a href="/formations.php#niveau-1"><?php echo t('footer.course_link_1'); ?></a></li>
          <li><a href="/formations.php#niveau-2"><?php echo t('footer.course_link_2'); ?></a></li>
          <li><a href="/formations.php#niveau-3"><?php echo t('footer.course_link_3'); ?></a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4><?php echo t('footer.services_heading'); ?></h4>
        <ul>
          <li><a href="/services-pc.php#montage"><?php echo t('footer.service_link_1'); ?></a></li>
          <li><a href="/services-pc.php#devis"><?php echo t('footer.service_link_2'); ?></a></li>
          <li><a href="/services-pc.php#diagnostic"><?php echo t('footer.service_link_3'); ?></a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4><?php echo t('footer.company_heading'); ?></h4>
        <ul>
          <li><a href="/a-propos.php"><?php echo t('footer.link_about'); ?></a></li>
          <li><a href="/tarifs.php"><?php echo t('footer.link_pricing'); ?></a></li>
          <li><a href="/contact.php"><?php echo t('footer.link_contact'); ?></a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4><?php echo t('footer.contact_heading'); ?></h4>
        <ul>
          <li><a href="mailto:contact@slapia.com">contact@slapia.com</a></li>
        </ul>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© <?php echo date('Y'); ?> <?php echo t('footer.copyright'); ?></span>
      <span>
        <a href="/mentions-legales.php"><?php echo t('footer.legal_mentions'); ?></a> ·
        <a href="/confidentialite.php"><?php echo t('footer.legal_privacy'); ?></a> ·
        <a href="/cgv.php"><?php echo t('footer.legal_cgv'); ?></a>
      </span>
    </div>
  </div>
</footer>

<script src="<?php echo assetUrl('/assets/js/main.js'); ?>"></script>
</body>
</html>
