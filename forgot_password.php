<?php

/** 
 * @fiename forgot_password.php
 * @author VanDaddy
 * @copyright 2014
 */

function sendEmail($email, $token, $username){
    $sitename    = ""; // your website name eg. BligBlag
    $admin_email = ""; // email used to send password reset code eg. admin@bligblag.net
    $url         = ""; // must end with forward slash eg. http://bligblag.net/
    $subject     = 'Password Reset';
    $message     = 'Hey '.$username.'. You have requested a password reset for your account on '.$sitename.'. Please click on the link below in order to reset your password. '.$url.'index.php?menu=password_reset&token='.$token.'';
    $headers     = 'From: admin@bligblag.net' . "\r\n" .
                   'Reply-To: admin@bliagblag.net' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
    
    mail($email, $subject, $message, $headers);    
}

if (!isset($_POST['lost_password_email'])){
    $noemail = "Please type in your email address:";
}

if (isset($_POST['lost_password_email'])){
    $email = mysql_real_escape_string($_POST['lost_password_email']);
    $reset_code = md5($_POST['lost_password_email'].time());
    $reset_code = mysql_real_escape_string($reset_code);
    $query = mysql_query("SELECT * FROM users WHERE email = '$email'") or die(mysql_error());
    if (mysql_num_rows($query)){
        $insert_token = mysql_query("UPDATE users SET reset_code = '$reset_code' WHERE email = '$email'") or die(mysql_error());
        $get_token = mysql_query("SELECT * FROM users WHERE email = '$email'") or die(mysql_error());
        while($row = mysql_fetch_assoc($get_token)){
            $username = $row['username'];
            $token = $row['reset_code'];
            $user_email = $row['email'];
            sendEmail($user_email,$token, $username);
            $success = 'We have sent an email to "'.$_POST['lost_password_email'].'". Please follow the email instructions in order to reset your password.';
        }
    } else {
        $wrongemail = "No account found for this email address";
    }
}

@$smarty->assign("noemail",$noemail);
@$smarty->assign("success",$success);
@$smarty->assign("wrongemail",$wrongemail);

?>