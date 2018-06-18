<?php
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message=$_POST['message'];
    $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Sanitizing E-mail.
///PHP Mailer
require './phpmail/PHPMailerAutoload.php';

$mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'mail.hostmayo.com';  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'admin@hostmayo.com';                 // SMTP username
$mail->Password = '900913Talk';                           // SMTP password
//$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 26;                                    // TCP port to connect to

$mail->From = 'admin@hostmayo.com'; //Should be same as smpt i think
$mail->FromName = 'Client';
//$mail->addAddress('waqasskhalid@gmail.com', 'Waqass Khalid');     // Add a recipient
$mail->addAddress('waqasskhalid@gmail.com');               // Name is optional
//$mail->addReplyTo('info@example.com', 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('waqasskhalid@gmail.com');

$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML
$mail->Subject = 'Host Mayo Message';
$mail->Body    = 'Name: '.$name. '<br>Email: '.$email. '<br>Message: '.$message.'<br>';
//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
if(!$mail->send()) {
    //echo 'Message could not be sent.';
    //echo 'Mailer Error: ' . $mail->ErrorInfo;
    $x= $mail->ErrorInfo;
    //echo $x;
    //echo 'Goback:<a href="./sell.php"';
} 
header( "Location: index.php");