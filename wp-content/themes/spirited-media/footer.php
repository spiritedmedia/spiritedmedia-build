    <footer class="footer" role="contentinfo">
        <div class="section">
            <a href="<?php echo esc_url( get_site_url() ); ?>"><?php echo svg_logo( 'spiritedmedia-logo-white' ); ?></a>
            <ul class="footer--social-items">
                <li class="footer--social-item">
                    <a href="https://www.facebook.com/spiritedmediaco/" class="footer--social-link">
                        <?php echo svg_icon( 'facebook' ); ?>
                        <span class="screen-reader-text">Facebook</span>
                    </a>
                </li>
                <li class="footer--social-item">
                    <a href="https://twitter.com/spiritedmediaco" class="footer--social-link">
                        <?php echo svg_icon( 'twitter' ); ?>
                        <span class="screen-reader-text">Twitter</span>
                    </a>
                </li>
                <li class="footer--social-item">
                    <a href="https://www.linkedin.com/company/spirited-media-co." class="footer--social-link">
                        <?php echo svg_icon( 'linkedin' ); ?>
                        <span class="screen-reader-text">LinkedIn</span>
                    </a>
                </li>
            </ul>
        </div>
    </footer>
    <?php wp_footer(); ?>
</body>
</html>
