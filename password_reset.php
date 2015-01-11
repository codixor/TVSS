<?php
/** 
 * @fiename password_reset.php
 * @author VanDaddy
 * @copyright 2014
 */
 

function changePasword($token,$password){
    $password = mysql_real_escape_string(password_hash($password, PASSWORD_BCRYPT));
    mysql_query("UPDATE users SET password = '$password' WHERE reset_code = '$token'") or die(mysql_error());
}

$admin_email = ""; // your admin email eg. admin@bligblag.net
if (empty($_POST['new_password'])){
   $noinput = "Please submit your new password.";
}
if (isset($_POST['new_password']) && !empty($_POST['new_password'])){

    $token = mysql_real_escape_string($_GET['token']);
    
    $sql = mysql_query("SELECT * FROM users WHERE reset_code = '$token'") or die(mysql_error());

    if(mysql_num_rows($sql)){
        $password = mysql_real_escape_string($_POST['new_password']);
        changePasword($_GET['token'], $password);
        $success = "Your password has been changed. You can now login using your new password.";
        $remove_token = mysql_query("UPDATE users SET reset_code = '' WHERE reset_code = '$token'") or die(mysql_error());
        
    } else {
        $error = "Something went wrong. Please contact us at ".$admin_email."";
    }
    
}
@$smarty->assign("noinput",$noinput);
@$smarty->assign("token",$token);
@$smarty->assign("error",$error);
@$smarty->assign("success",$success);
?>