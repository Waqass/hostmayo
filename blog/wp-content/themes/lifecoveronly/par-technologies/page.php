<?php
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
          <div class="aa_left" style="width:165px;"> <a href="#"><img src="<?php echo $image[0]; ?>" alt="banner_logo" style="width:164px; height:108px;"></a></div> 
          <div class="infoleft" style="width:99%; padding:0px;">
            <p> </p>
            <?php the_content(); ?>
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
            <div class="image"><img src="<?PHP echo $image[0]; ?>"></div>
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
      <div class="title"><img src="<?php bloginfo('template_url'); ?>/images/getform.png"><span class="titlewords">Get a Free Quote Now</span></div>
      
      <div class="fwrap">
        <div class="fcont">
          <div class="fram-he fram-heD">
              <?php include_once 'form.php'?>
          </div>
      </div>
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
