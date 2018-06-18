<div class="footer">
    <div class="container">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <div class="company-info text-center">
                        <h3>About company</h3>
                        <p>Host Mayo is one of the few companies that provide fastest web hosting at very affordable rates. We do things most other hosting companies would not even consider possible. The company is working under the umbrella of Mayo.</p>
                        <div class="nav_menu-1 nav_menu hidden-xs hidden-sm">
                        <div class="menu-support-help-container">
                            <ul id="menu-support-help" class="menu">
                                <li class="menu-item"><a title="Mayo Group" href="./mayo.php">Mayo</a></li>
                                <li class="menu-item"><a title="Vision Statement" href="./vision.php">Vision</a></li>
                                <li class="menu-item"><a title="Mission Statement" href="./mission.php">Mission</a></li>
                                <li class="menu-item"><a title="Reviews & Awards" href="./reviews-awards.php">Reviews & Awards</a></li>
                            </ul>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 hidden-xs hidden-sm">
                    <div class="nav_menu-1 nav_menu">
                    <h3>Support &amp; help</h3>
                    <div class="menu-support-help-container">
                        <ul id="menu-support-help" class="menu">
                            <li class="menu-item"><a title="Support" rel=”nofollow” href="./members/index.php?fuse=support&controller=ticket&view=submitticket">Support</a></li>
                            <li class="menu-item"><a title="Community" href="https://hostmayo.com/blog/forums/">Forums</a></li>
                            <li class="menu-item"><a title="Blog" href="./blog/index.php">Blog</a></li>
                            <li class="menu-item"><a title="Refund & Usage Policy" href="./refund.php">Refund & Usage Policy</a></li>
                            <li class="menu-item"><a title="Privacy Policy" href="./privacy.php">Privacy Policy</a></li>
                            <li class="menu-item"><a title="frequently asked questions" href="./faq.php">FAQs</a></li>
                        </ul>
                    </div>
                    </div>
                    <div class="nav_menu-1 nav_menu">
                    <h3>Products</h3>
                        <div class="menu-support-help-container">
                            <ul id="menu-support-help" class="menu">
                                <li class="menu-item"><a title="Web Hosting" rel=”nofollow” href="./web-hosting.php">SSD Web Hosting</a></li>
                                <li class="menu-item"><a title="Reseller Hosting" href="./reseller-hosting.php">Reseller Hosting</a></li>
                                <li class="menu-item"><a title="VPS" href="./vps-hosting.php">VPS</a></li>
                                <li class="menu-item"><a title="Dedicatd Hosting" href="./dedicated-hosting.php">Dedicated Servers</a></li>
                                <li class="menu-item"><a title="Domains" href="./members/order.php?step=0&productGroup=2">Domains</a></li>
                                <li class="menu-item"><a title="Domains" href="./premiumdomains.php">Premium Domains</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 hidden-xs hidden-sm">
                    <div class="nav_menu-2 nav_menu">
                    <h3>Control panel</h3>
                    <div class="menu-control-panel-container">
                        <ul id="menu-control-panel" class="menu">
                            <li class="menu-item"><a title="Client Portal" rel=”nofollow” href="./members/index.php?fuse=home&view=login">Client Portal</a></li>
                            <li class="menu-item"><a title="CPanel Login" href="./cpanel">CPanel Login</a></li>
                            <li class="menu-item"><a title="FTP Login" href="ftp://ftp.hostmayo.com/">FTP Login</a></li>
                        </ul>
                    </div>
                    </div>
                    <div class="nav_menu-2 nav_menu">
                    <h3>Latest from Blog</h3>
                        <?php 
                         /* Short and sweet */
                         define('WP_USE_THEMES', false);
                         require('blog/wp-blog-header.php');
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
                    <div class="subscribe-form hidden-xs hidden-sm">
                        <h3>Subscribe newsletter</h3>
                        <?php include_once './subscribe.php';?>
                    </div>
                    <div class="ts_alaska_follow_us-1  TS_ALASKA_Follow_us">
                        <div class="ts-social-footer text-center">
                        <h3 class="title">Follow us</h3>
                        <a href="https://twitter.com/hostmayo" target="_blank"><span><i class="fab fa-twitter"></i></span></a>
                        <a href="https://www.facebook.com/hostmayoservers" target="_blank"><span><i class="fab fa-facebook-f"></i></span></a>
                        <a href="//plus.google.com/u/0/111671310264833997946?prsrc=3" rel="publisher" target="_top" style="text-decoration:none;"><span><i class="fab fa-google-plus-g"></i></span></a>
                        <a href="https://www.linkedin.com/company/host-mayo" target="_blank"><span><i class="fab fa-linkedin-in"></i></span></a>
                        </div>
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
                        <p>Copyright © 2015-18<a href="https://hostmayo.com/"> Hostmayo.com</a></p>
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
    <script src="js/wow.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/creative.min.js"></script>
    
    <script type="text/javascript">
    $(document).ready(function(){
        $.goup();
    });
    </script>
        <!--Start of Tawk.to Script-->
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/5574520ce93587bc57f6d215/default';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    <!--End of Tawk.to Script-->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-34351594-2', 'auto');
        ga('send', 'pageview');

    </script>