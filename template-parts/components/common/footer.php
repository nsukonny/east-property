<?php
/**
 * Footer template part
 */
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-wrapper">
                <div class="footer-inner">
                    <div class="footer-info">
                        <div class="subscribe">
                            <label for="subs">
                                <span>Keep Yourself Up to Date</span>
                                <input type="email" id="subs" placeholder="Your email address">
                            </label>

                            <!---- Use button component ---->
                            <button class="button orange xl">
                                <img src="<?php echo THEME_URL; ?>/assets/img/bell.svg" width="16" height="16"
                                     alt="Subscribe">
                                Subscribe
                            </button>
                        </div>
                        <a class="footer-logo" href="/">
                            <img class="footer-logo" src="<?php echo THEME_URL; ?>/assets/img/logo.svg"
                                 width="132"
                                 height="50" alt="Vector logotype">
                        </a>
                        <div class="footer-block">
                            <span>Address</span>
                            <address>
                                Sheikh Zayed Road, Building 25 <br>
                                Al Quoz 3 <br>
                                Dubai
                            </address>
                        </div>
                        <div class="footer-block">
                            <span>Live Support?</span>
                            <a href="mailto:info@eastpropoerty.com">
                                info@eastpropoerty.com
                            </a>
                        </div>
                    </div>
                    <div class="footer-menu">
                        <nav>
                            <ul class="menu">
                                <li class="menu-item">
                                    <span>Popular Search</span>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Apartment for Sale</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Houses for Sale</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Villas for Sale</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Offices for Sale</a>
                                </li>
                            </ul>
                            <ul class="menu">
                                <li class="menu-item">
                                    <span>Discovery</span>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Dubai</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Abu Dhabi</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Palm Jumeirah</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Dubai Hills</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Business bay</a>
                                </li>
                            </ul>
                        </nav>
                        <nav>
                            <ul class="menu">
                                <li class="menu-item">
                                    <span>Quick Links</span>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Terms of Use</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Privacy Policy</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Pricing Plans</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Our Services</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Contact</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">Careers</a>
                                </li>
                                <li class="menu-item">
                                    <a href="#">FAQs</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                <div class="copyright">
                    <span>© East Property – All rights reserved</span>
                </div>
            </div>
        </div>
    </footer>

<?php
wp_footer();