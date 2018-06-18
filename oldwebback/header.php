<?php $basename = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF']))-4);?>
<!DOCTYPE HTML>
<html>
<head>
<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "Organization",
  "url": "http://www.hostmayo.com",
  "logo": "http://www.hostmayo.com/images/logob.png",
  "name" : "Host Mayo",
  "sameAs" : [
    "http://www.facebook.com/hostmayoservers",
    "http://www.twitter.com/hostmayo",
    "http://plus.google.com/+Hostmayo"
    ],
   "contactPoint" : [{
    "@type" : "ContactPoint",
    "telephone" : "+923228879833",
    "contactType" : "customer service"
  }]
}
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="Experience fastest web hosting on earth starting just form 1$/month. All plans include SSDs, cPanel, cloudLinux & litespeed web server!">
<meta name="keywords" content="web hosting, listspeed Web hosting, register domain, purchase domain, 
cheap hosting, one dollar hosting, fast hosting, wordpress hosting, ultra fast hosting, buy domain" />
<meta name="google-site-verification" content="FjRN_DvEBE4AAdTdD0AXC_HWNHNhynrWuZ4z2QjpGKs" />
<meta name="msvalidate.01" content="85EE7A20922416714B3ED614BDB270BF" />
<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<link href="css/popup.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<link href='http://fonts.googleapis.com/css?family=Lato:100,300,400,700,900' rel='stylesheet' type='text/css'>
<link rel="icon" type="image/png" href="ico.png" />
</head>
<body>
<div <?php if ($basename == 'index') echo 'class="header"'; else echo 'class="about_header"';?>>
    <div class="header_top">
        <ul class="nav navbar-nav navbar-right hidden-xs hidden-sm">
            <li class="">
                <a class="page-scroll"><i class="fa fa-phone-square"> 24/7 Support</i></a>
            </li>
            <li class="">
                <a href="https://www.facebook.com/hostmayoservers"><i class="fa fa-facebook-square"> Facebook</i></a>
            </li>
            <li class="">
                <a href="mailto:admin@hostmayo.com"><i class="fa fa-envelope-square"> Admin@hostmayo.com</i></a>
            </li>
            <li class="">
                <a class="page-scroll" href="contactus.php"><i class="fa fa-commenting"> Contact Us</i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="container">
        <div class="header_bottom"> 
            <div class="col-xs-3 logo">
               <a itemprop="url" href="index.php"><img src="images/logo.png" alt="Host Mayo Logo"/></a>
            </div>
            <div class="col-xs-9 header_nav">
                <div class="col-sm-9 menu col-xs-12">
                       <!-- Static navbar -->
                    <nav class="navbar">
                      <div class="container-fluid">
                        <div class="navbar-header">
                          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                            <img src="images/nav_icon.png" alt="" />
                          </button>
                        </div>
                        <div id="navbar" class="navbar-collapse collapse">
                          <ul class="nav navbar-nav">
                            <li class="current active"><a href="index.php">Home</a></li>
                            <li><a href="aboutus.php">Why Us</a></li>
                            <li><a href="./community/index.php">Community</a></li>
                            <li><a href="./members/index.php?fuse=support&controller=ticket&view=submitticket">Support</a></li>								
                          </ul>
                        </div><!--/.nav-collapse -->
                      </div><!--/.container-fluid -->
                    </nav>
                </div>
                <div class="col-sm-3 header_but hidden-xs">
                    <menu class="cl-effect-8" id="cl-effect-8">
                           <a href="./members/index.php?fuse=home&view=login">Client Area</a>
                    </menu>	
                </div>
            </div>
        </div>
   </div><?php if ($basename == 'index') { ?>
            <div class="header_bot_grid hidden-xs hidden-sm">
                <h1>Experience Fastest Web Hosting On Earth</h1>
                <div class="header-btns">
                        <a class="plans btn btn-primary1 btn-normal btn-inline" href="#plans">View Plans</a>
                    <a class="plans btn btn-primary2 btn-normal btn-inline" href="./members/order.php">Buy Now</a>
                </div>
                <span> </span>
            </div>
        <?php } ?>
</div>