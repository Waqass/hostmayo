<!DOCTYPE HTML>
<html lang="en-US">
<head>
<meta name="viewport" content="width=device-width; initial-scale=1.0">
<meta charset="utf-8">
<title><?php bloginfo('name'); ?><?php wp_title(); ?></title>
<link href="<?php bloginfo('template_url'); ?>/css/bootstrap.min.css" rel="stylesheet" type="text/css">
<link href="<?php //bloginfo('template_url'); ?>/css/bootstrap-theme.min.css" rel="stylesheet" type="text/css">
<link href="<?php bloginfo('template_url'); ?>/css/style.css" rel="stylesheet" type="text/css">
<link href="<?php bloginfo('template_url'); ?>/css/menu.css" rel="stylesheet" type="text/css">
<link href="<?php bloginfo('template_url'); ?>/css/structure.css" rel="stylesheet" type="text/css">
<link href="<?php bloginfo('template_url'); ?>/css/iphone.css" media="screen and (max-device-width: 480px)" rel="stylesheet">

<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/css/colorbox.css" />
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/css/structure.css" media="screen" />
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/css/jquery-ui-1.10.3.custom.css" />


<script src="<?php bloginfo('template_url'); ?>/js/modernizr-custom-ck.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>//js/jquery-ck.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>//js/jquery.selectbox-0.2.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>//js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>//js/scripts.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>//js/jquery.colorbox-min.js"></script>

<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script> 
<script src="<?php bloginfo('template_url'); ?>/js/custom.js"></script>
<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
    
</head>
<body>
<div class="container wrap">
  <header>
    <div class="row">
      <div class="col-md-5">
        <div class="logo"><a href="<?php echo home_url( '/' ); ?>"><img src="<?php bloginfo('template_url'); ?>/images/logo.png" width="241" height="90" alt="" class="img-responsive"></a>
		</div>
		<div class="badge1"><span>From <br /> <strong>&pound; 3.99</strong> per month</span></div>
      </div>
      <div class="col-md-7">
        <div class="HeaderSuccessSlogan">
          <div class="HeaderSuccessContent"><span>Compare Discounted Premium</span> from the top life insurers<br>
            and even check our prices against other online services</div>
        </div>
      </div>
    </div>
    <!--End of row -->
    <div class="row">
      <div class="col-md-12">
        <nav class="MenuContainer">
          <div class="HeaderMenuUl">
            <?php wp_nav_menu(
		   	array(
			'theme_location' =>'Header menu',
			'container_class' => 'nav',
			'menu_class'      => 'nav',
			'items_wrap' => '<ul id="nav">%3$s' 
					)); ?> 
          </div>
        </nav>
        <div class="slicknav_menu">
            <div id="dl-menu" class="dl-menuwrapper">
  <button class="dl-trigger" onClick="javascript:$('.dl-menu').toggle('slow', function() { });">Open Menu</button>
  <ul class="dl-menu">
   <li class="current-page-item"><a href="#">Home</a></li>
              <li class=""><a href="#">Over 50 Life Insurance</a></li>
              <li class=""> <a href="#">Term  Life Insurance</a></li>
              <li class=""> <a href="#">Mortgage Life Insurance</a></li>
              <li class=""><a href="#">Whole Life Insurance</a></li>
  </ul>
</div></div>
      </div>
    </div>
    <!--End of row --> 
  </header>