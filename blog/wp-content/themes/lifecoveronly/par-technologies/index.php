<?php
get_header();
?>
  <!--End of Header -->
  <?php
       $post = get_post(38);
       $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
  ?>
  <section id="midwrap">
    <div class="row firstcolumn">
     <div class="col-md-7 main_left_part iphone_a">
        <div class="F-coltitle"><?php echo apply_filters('the_title', $post->post_title); ?>
          <div style="float:right; color:#f00;font:normal 22px/25px arial;font-family: 'Conv_BebasNeue';position:relative;right:10px;margin-top:5px;">Lowest Price Guarantee</div>
        </div>
      <div class="bannerouter-inner" id='up_block'>
        <div class="infoclass">
          <div class="aa_left" style="width:165px;"> <a href="#"><img src="<?php echo $image[0]; ?>" alt="banner_logo" style="width:164px; height:108px;"></a></div> 
          <div class="infoleft" style="width:99%; padding:0px;">
            <p> </p>
            <?php echo apply_filters('the_content', $post->post_content); ?>
            <p></p>
          </div>
          <div>
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/1.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/2.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/3.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/4.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/5.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/6.png" >
                 <img src="<?php echo site_url();?>/wp-content/uploads/2014/01/7.png" >
          </div>
        </div>
      </div>
        
        <div class="lifecoverouter">
          <div class="title2">Why Use Lifecover only <img src="<?php bloginfo('template_url'); ?>/images/doublearrow.jpg"></div>
          <div class="quotes">
            
             <?PHP 
	 $post_7 = get_post(4); 
$excerpt = $post_7->post_content;
echo $excerpt
	 ?>
            
          </div>
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
      <div class='clear_for_mobile'> <!----------------------- --> </div>
      <div class="col-md-5 iphone_b" id="hhd"> 
        <div class="title"><img src="<?php bloginfo('template_url'); ?>/images/getform.png"><span class="titlewords">Get a Free Quote Now</span></div>
        <div class="fwrap">
          <div class="fcont">
              <div class="fram-he fram-heD">
                <?php include_once 'form.php'?>  
              </div>
              
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      
    </div>
    <!--End of row --> 
  </section>
  <!--End of midWrap -->
  </div>
<!--End of container --> 
  <?php
  get_footer();
  ?>