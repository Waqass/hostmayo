<section id="footer">
  <div class="container">
    <div class="row">
      <div class="footercontent">
        <div class="footertop">
          <div class="column1 col-xs-12 col-sm-12 col-md-4">
            <div class="outer">
              <div class="title-f">Why Use Lifecover only</div>
              <?php wp_nav_menu(
		   	array(
			'theme_location' =>'Footer menu',
			'container_class' => 'nav',
			'menu_class'      => 'nav',
			'items_wrap' => '<ul id="nav">%3$s' 
					)); ?> 
            </div>
          </div>
          <div class="column2 col-xs-12 col-sm-12 col-md-4">
            <div class="outer">
              <div class="title-f">More about life insurance</div>
              <?php wp_nav_menu(
		   	array(
			'theme_location' =>'Footer menu2',
			'container_class' => 'nav',
			'menu_class'      => 'nav',
			'items_wrap' => '<ul id="nav">%3$s' 
					)); ?> 
            </div>
          </div>
          <div class="column2 col-xs-12 col-sm-12 col-md-4">
            <div class="outer">
              <div class="title-f">Lifecover Only </div>
               <?php wp_nav_menu(
		   	array(
			'theme_location' =>'Footer menu3',
			'container_class' => 'nav',
			'menu_class'      => 'nav',
			'items_wrap' => '<ul id="nav">%3$s' 
					)); ?> 
            </div>
          </div>
        </div>
        	
        	<div class="footerbottom">
            <div class="col-xs-12 col-sm-12 col-md-6">
            <span class="copyrights">Copyrights &copy; 2013 Lifecoveronly. All rights reserved</span>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-4 pull-right">
          	<div class="footer_private"><a href="?page_id=18">Privacy Policy</a> | <a href="?page_id=14">Terms &amp; Condition</a></div>
            </div>
        </div>
      </div>
    </div>
    <!--End of row --> 
    </div>
  </section>
  <!--End of footer --> 


<script src="<?php bloginfo('template_url'); ?>/js/jquery-1.10.2.js"></script> 
<script src="<?php bloginfo('template_url'); ?>/js/bootstrap.min.js"></script>
</body>
</html>
