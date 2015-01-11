{include file="header.tpl"}
<h1>Reset Password Form</h1>
{$success}
{$noemail}
{$wrongemail}
<form action="index.php?menu=forgot_password" method="post" id="search-form" class="login-form">
    <div class="clear"></div>
    <input type="text" name="lost_password_email" placeholder="Type in your email address" style="margin: 5px 0px 5px 0px;width:25%;" />
    <div class="clear"></div>
    <input type="submit" value="Submit" class="btn grey" style="border-radius:5px; background-color:grey; color:#fff;"/>
    <div class="clear"></div>
</form>
{include file="footer.tpl"}