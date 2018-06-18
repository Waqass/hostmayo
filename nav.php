<nav class="hidden-sm hidden-xs bg-blue">
    <div class="container-fluid">
        <?php
                if(basename($_SERVER["SCRIPT_NAME"]) == 'vps-hosting.php') 
                {
                echo 
                    '<ul class="nav navbar-nav navbar-left smallnav">
                    <li><a><i class="fas fa-tag" aria-hidden="true"></i> 25% OFF ANY NEW SSD VPS! Coupon: <span style="color:gold;">SOLUS</span></a></li>
                    </ul>';
                } 
                elseif(basename($_SERVER["SCRIPT_NAME"]) == 'web-hosting.php')  
                {
                echo 
                    '<ul class="nav navbar-nav navbar-left smallnav">
                    <li><a><i class="fas fa-tag" aria-hidden="true"></i> 15% OFF ANY SSD WEB HOSTING! Coupon: <span style="color:gold;">HOSTMAYO</span></a></li>
                    </ul>';
                }
        ?>      

        <ul class="nav navbar-nav navbar-right smallnav">
          <li <?php if(basename($_SERVER["SCRIPT_NAME"]) == 'index.php') 
              { 
              echo 'class="inactive"> <a>';
              } 
              else
              {
                  echo '><a href="./">';
              }?>
           <i class="fas fa-home" aria-hidden="true"></i> Home</a></li> 
          <li><a href="./members/index.php?fuse=home&view=login"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login</a></li>
          <li><a href="https://hostmayo.com/blog/forums/"><i class="fab fa-wpforms" aria-hidden="true"></i> Forums</a></li>
          <li><a href="./blog/"><i class="far fa-newspaper" aria-hidden="true"></i> Blog</a></li>
                    <li <?php if(basename($_SERVER["SCRIPT_NAME"]) == 'contact-us.php') 
              { 
              echo 'class="inactive"> <a>';
              } 
              else
              {
                  echo '><a href="./contact-us.php">';
              }?><i class="far fa-life-ring" aria-hidden="true"></i> Contact Us</a></li>
          <li><a href="http://hostmayo.com/cpanel"><i class="far fa-user-circle" aria-hidden="true"></i> CPanel</a></li>
          <li><a href="https://vps.hostmayo.com"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> SolusVM Dallas</a></li>
          <li><a href="https://vps2.hostmayo.com"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> SolusVM Los Angels</a></li>
        </ul>
    </div>
</nav>
    <?php
            if(basename($_SERVER["SCRIPT_NAME"]) == 'index.php') 
            {
            echo '<nav id="mainNav" class="navbar navbar-default navbar-fixed-top">';
            } 
            else 
            {
            echo '<nav class="navbar navbar-default affixall">';
            }
    ?>
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" itemprop="url" href="index.php"><img src="img/logo.png" alt="Host Mayo Logo"/></a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
                <li class="hidden-md hidden-lg"><a href="./members/index.php?fuse=home&view=login><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login</a></li>
                <li class="hidden-md hidden-lg"><a href="https://hostmayo.com/blog/forums/"><i class="fab fa-wpforms" aria-hidden="true"></i> Forums</a></li>
                <li class="hidden-md hidden-lg"><a href="./blog/"><i class="far fa-newspaper" aria-hidden="true"></i> Blog</a></li>
                <li class="hidden-md hidden-lg"><a href="http://hostmayo.com/cpanel"><i class="far fa-user-circle" aria-hidden="true"></i> CPanel</a></li>
                <li class="hidden-md hidden-lg"><a href="https://vps.hostmayo.com"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> SolusVM Dallas</a></li>
                <li class="hidden-md hidden-lg"><a href="https://vps2.hostmayo.com"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> SolusVM Los Angels</a></li>
                <li>
                    <a href="./web-hosting.php">SSD Web Hosting</a>
                </li>
                <li>
                    <a href="./reseller-hosting.php">Reseller Hosting</a>
                </li>
                <li>
                    <a href="./vps-hosting.php">VPS</a>
                </li>
<!--                <li>
                    <a href="./vps-kvm-ssd-hosting.php">VPS KVM</a>
                </li>-->
                <li>
                    <a href="./dedicated-hosting.php">Dedicated Servers</a>
                </li>
                <li>
                    <a href="./members/order.php?step=0&productGroup=2">Domains</a>
                </li>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container-fluid -->
</nav>