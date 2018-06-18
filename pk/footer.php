<div class="footer">
    <div class="container">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <div class="company-info">
                    <h3>About company</h3>
                    <p>Host Mayo is one of the few companies providing ultra fast web hosting at very affordable rates. The company is working under the umbrella of <a href="mayo.php">Mayo.</a></p>
                    <p><span>E : </span><a href="mailto:admin@hostmayo.com">admin@hostmayo.com</a></p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <div class="nav_menu-1 nav_menu">
                    <h3>Support &amp; help</h3>
                    <div class="menu-support-help-container">
                    <ul id="menu-support-help" class="menu"><li class="menu-item"><a title="Support" rel=”nofollow” href="../members/index.php?fuse=support&controller=ticket&view=submitticket">Support</a></li>
                        <li class="menu-item"><a title="Blog" href="../blog/index.php">Blog</a></li>
                        <li class="menu-item"><a title="Refund & Usage Policy" href="./refund.php">Refund & Usage Policy</a></li>
                        <li class="menu-item"><a title="Privacy Policy" href="./privacy.php">Privacy Policy</a></li>
                        <li class="menu-item"><a title="frequently asked questions" href="./faq.php">FAQs</a></li>
                        <li class="menu-item"><a title="Web Hosting in Lahore" href="./webhosting-in-lahore.php">Web Hosting in Lahore</a></li>
                    </ul>
                    </div>
                    </div>
                    <div class="nav_menu-1 nav_menu">
                    <h3>Plans</h3>
                        <div class="menu-support-help-container">
                            <ul id="menu-support-help" class="menu">
                                <li class="menu-item"><a title="Web Hosting" rel=”nofollow” href="./web-hosting.php">Shared Hosting</a></li>
                                <li class="menu-item"><a title="VPS Hosting" href="./vps-hosting.php">VPS Hosting</a></li>
                                <li class="menu-item"><a title="Dedicatd Hosting" href="./dedicated-hosting.php">Dedicated Hosting</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <div class="nav_menu-2 nav_menu">
                    <h3>Control panel</h3>
                    <div class="menu-control-panel-container">
                    <ul id="menu-control-panel" class="menu"><li class="menu-item"><a title="Client Portal" rel=”nofollow” href="../members/index.php?fuse=home&view=login">Client Portal</a></li>
                    <li class="menu-item"><a title="CPanel Login" href="../cpanel">CPanel Login</a></li>
                    <li class="menu-item"><a title="FTP Login" href="ftp://ftp.hostmayo.com/">FTP Login</a></li>
                    </ul>
                    </div>
                    </div>
                    <div class="nav_menu-2 nav_menu">
                    <h3>Latest from Blog</h3>
                        <?php 
                         /* Short and sweet */
                         define('WP_USE_THEMES', false);
                         require('../blog/wp-blog-header.php');
                         ?>
                        <div class="menu-control-panel-container">
                            <ul id="menu-control-panel" class="menu">
                            <?php
                            // Get the last 3 posts.
                            global $post;
                            $args = array( 'posts_per_page' => 5 );
                            $myposts = get_posts( $args );

                            foreach( $myposts as $post ) :	setup_postdata($post); ?>
                            <li class="menu-item"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
                            <?php endforeach; ?>
                            </ul>
                         </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 last">
                    <div class="subscribe-form">
                        <h3>Subscribe newsletter</h3>
                        <?php include_once './subscribe.php';?>
                    </div>
                    <div class="ts_alaska_follow_us-1  TS_ALASKA_Follow_us">
                        <div class="ts-social-footer ">
                        <h3 class="title">Follow us</h3>
                        <a href="https://twitter.com/hostmayo" target="_blank"><span><i class="fa fa-twitter"></i></span></a>
                        <a href="https://www.facebook.com/hostmayoservers" target="_blank"><span><i class="fa fa-facebook"></i></span></a>
                        <a href="//plus.google.com/u/0/111671310264833997946?prsrc=3" rel="publisher" target="_top" style="text-decoration:none;"><span><i class="fa fa-google-plus"></i></span></a>
                        <a href="https://www.linkedin.com/company/host-mayo" target="_blank"><span><i class="fa fa-linkedin"></i></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
<footer>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <i class="fa fa-2x fa-cc-visa"></i>
                        <i class="fa fa-2x fa-cc-mastercard"></i>
                        <i class="fa fa-2x fa-cc-amex"></i>
                        <i class="fa fa-2x fa-cc-discover"></i>
                        <i class="fa fa-2x fa-cc-jcb"></i>
                        <i class="fa fa-2x fa-cc-paypal"></i>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-center">
                        <p>Copyright © 2016<a href="https://hostmayo.com/"> Hostmayo.com</a></p>
                    </div>
                </div>
            </div>
        </div>
</footer>

     <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="js/jquery.easing.min.js"></script>
    <script src="js/jquery.fittext.min.js"></script>
    <script src="js/wow.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/creative.min.js"></script>
    
    <!-- GO UP -->
    <script src="js/jquery.goup.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $.goup();
    });
    </script>