<?php /* Template Name: Thanks */
get_header();
?>
<!--End of Header -->

<section id="midwrap">
  <div class="row firstcolumn">
    <div class="main_left_part col-xs-12 col-sm-12  <?php if(!is_page( 18 )) if (!is_page( 14 )) { ?>col-md-7<?php }?>">
      <?php
			   if(have_posts()) : 
				  while(have_posts()) : 
					 the_post(); 
					 $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
			?>
      <div class="bannerouter-inner">
        <div class="infoclass">
        <div class="aa_right" style="text-align:left; font-size:26px; line-height:22px; width:100%"><span>
            <?php the_title(); ?>
            </span></div>
          <div class="aa_left" style="width:165px;">
              <?PHP 
            if ( has_post_thumbnail() ) {
                the_post_thumbnail();
                } 
               ?>
          </div> 
          <div class="infoleft" style="width:99%; padding:0px;">
            <p> </p>
            <?php 
            the_content();
            error_reporting(E_PARSE);
            $name= 'Enquiry by '.$_POST['firstname'];
            $email= $_POST['email'];
            $headers = "From: $email\n";
            $headers .= "Reply-To: ". $email . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            //$headers .= "Bcc: waqasskhalid@gmail.com\r\n";
            $header .= "Mailed-by: LifeCoverOnly\r\n";
            $message = '<html><body>';
            $message = '<table width="400px" rules="all" style="border: 1px solid black;" cellpadding="10">';
            $message .= "<tr><td><strong>Cover amount:</strong></td><td>" . $_POST['value'] . "</td></tr>";
            $message .= "<tr><td><strong>Whom is the cover for:</strong> </td><td>" . $_POST['covered'] . "</td></tr>";
            $message .= "<tr><td><strong>Type of cover:</strong> </td><td>" . strip_tags($_POST['coverage']) . "</td></tr>";
            $message .= "<tr><td><strong>Cover critical illnesses:</strong> </td><td>" . strip_tags($_POST['illness']) . "</td></tr>";
            $message .= "<tr><td><strong>First Name:</strong> </td><td>" . strip_tags($_POST['firstname']) . "</td></tr>";
            $message .= "<tr><td><strong>Surname</strong> </td><td>" . strip_tags($_POST['surname']) . "</td></tr>";
            $dob1=$_POST['dob_day'];
            $dob2=$_POST['dob_month'];
            $dob3=$_POST['dob_year'];
            $full="$dob1-$dob2-$dob3";
            $message .= "<tr><td><strong>Date of Birth:</strong> </td><td>" . $full . "</td></tr>";
            $message .= "<tr><td><strong>Telephone:</strong> </td><td>" . strip_tags($_POST['tel']) . "</td></tr>";
            $message .= "<tr><td><strong>Email Address:</strong> </td><td>" . strip_tags($_POST['email']) . "</td></tr>";            
            $message .= "</body></html>";
            $send_contact=mail('Shahid@crescent-consultants.com',$name,$message,$headers);
            //Welcome Email
            $email= $_POST['email'];
            $headers = "From: Admin\n";
            $headers .= "Reply-To: support@lifecoveronly.co.uk\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $header .= "Mailed-by: LifeCoverOnly\r\n";
            $message = '<html><body>';
            //$message = '<table width="400px" rules="all" style="border: 0px solid black;" cellpadding="10">';
            $message .= "<strong>Welcome To lifecoveronly.Co.UK!</strong>";
            $message .= "<br>We have received your enquiry. We will revert back to you shortly.";           
            $message .= "</body></html>";
            $send_contact=mail($email,'Welcome to lifecoveronly!',$message,$headers);
            //Page Display            
            echo '<br>Cover amount: ';
            echo $_POST['value'];
            echo '<br> Whom is the cover for: ';
            echo $_POST['covered'];
            echo '<br>Type of cover: ';
            echo $_POST['coverage'];
            echo '<br>Cover critical illnesses: ';
            echo $_POST['illness'];
            echo '<br>First Name: ';
            echo $_POST['firstname'];
            echo '<br>Surname: ';
            echo $_POST['surname'];
            echo '<br>Date of Birth: ';
            echo $_POST['dob_day'];
            echo '-';
            echo $_POST['dob_month'];
            echo '-';
            echo $_POST['dob_year'];
            echo '<br>Telephone: ';
            echo $_POST['tel'];
            echo '<br>Your Email Address: ';
            echo $_POST['email'];
            echo '<br>'; 
            ?>
            <p></p>
          </div>
        </div>
      </div>
      <?php
				  endwhile;
			   else : 
			?>
      Oops, there are no posts.
      <?php
			   endif;
			?>
      <div class="lifecoverouter">
        <div class="title2">Why Use Lifecover only <img src="<?php bloginfo('template_url'); ?>/images/doublearrow.jpg"></div>
        <div class="quotes">
     <?PHP 
	 $post_7 = get_post(4); 
$excerpt = $post_7->post_content;
echo $excerpt
	 ?>

        </div>
	<div class="testimonialcontainer">
        <div class="testimonialouter">
          <div class="titleT">Testimonials <span style="display:inline-block;margin-top: -6px; vertical-align: top;"><img src="<?php bloginfo('template_url'); ?>/images/left_arrow.jpg"><img src="<?php bloginfo('template_url'); ?>/images/right_arrow.jpg"></div>
          <div class="content">
           <?PHP 
	global $post;
$args = array( 'numberposts' => 1, 'offset'=> 0, 'post_type' => 'testimonials' );
$myposts = get_posts( $args );
foreach( $myposts as $post ) :	setup_postdata($post); 
$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
?>
            <div class="image"><?PHP 
            if ( has_post_thumbnail() ) {
                the_post_thumbnail();
                } 
               ?></div>
            <div class="sentence"><?PHP the_content(); ?></div>        
            <br>
            <br>
            <br>
            <div class="design"><?PHP the_title();?></div>
            <?PHP
endforeach;
?>
          </div>
        </div>
      </div>

      </div>
    </div>
    
    <div class='clear_for_mobile'> <!----------------------- --> </div>
    
    <div class="col-xs-12 col-sm-12 col-md-5 iphone_b">
      <div id="framBlock">&nbsp;</div>
	  <?php if(!is_page( 18 )) if (!is_page( 14 )) { ?>
      
      <?php } ?>
    </div>
  </div>
  <div class="row">
      <div class="testimonialcontainer slider">
        <ul>
        <?PHP 
	global $post;
$args = array( 'numberposts' => 8, 'offset'=> 0, 'post_type' => 'logos' );
$myposts = get_posts( $args );
foreach( $myposts as $post ) :	setup_postdata($post); 
$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
?>
<li><img src="<?PHP echo $image[0]; ?>" width="120" height="60" alt="<?PHP the_title();?>"></li>
<?PHP
endforeach;
?>
        </ul>
      </div>
    </div>
  <!--End of row --> 
</section>
<!--End of midWrap -->
</div>
<!--End of container -->
<?php
get_footer();
?>
