<?php include 'header.php';?>
<title>Host Mayo - Contact Us</title>
<body>
<div class="contact_index">
	<div class="container">
		<div class="col-md-8 contact_index-left">
			<h3>Send Us A Message</h3>
			<div class="contact-form">
                            <form action="email.php" method="post">
                                <input type="text" class="textbox" name="name" value="name" onfocus="this.value = '';" onblur="if (this.value == '') {this.value = 'Name';}">
                                <input type="text" class="textbox" name="email" value="Email" onfocus="this.value = '';" onblur="if (this.value == '') {this.value = 'Email';}">
                                <textarea name="message" value="Message:" onfocus="this.value = '';" onblur="if (this.value == '') {this.value = 'Message';}">Message</textarea>
					<input type="submit" value="Send Now">
                            </form>
			</div>
		</div>
		<div class="col-md-4 contact_index-right">
			<h3>Quick Links</h3>
			<ul class="footer_social">
			  <li><a href="mailto:admin@hostmayo.com"> <i class="email"> </i> </a></li>
			  <li><a href="https://www.facebook.com/hostmayoservers"> <i class="chat"> </i> </a></li>
			  <li><a href="http://hostmayo.com/members/index.php?fuse=support&controller=ticket&view=submitticket"> <i class="report"> </i></a></li>
			  <li><a href="tel:+923228879833"> <i class="phone"> </i></a></li>
			</ul>
		</div>
	</div>
</div>
<?php include './footer.php';?>

