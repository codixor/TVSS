{include file="header.tpl"}
<h1>New Password Form</h1>
{$success}
{$error}
{$noinput}
<form action="index.php?menu=password_reset&token={$token}" method="post" id="search-form" class="login-form">
    <div class="clear"></div>
    <input type="password" name="new_password" placeholder="Type in your new password" style="margin: 5px 0px 5px 0px;width:25%;" />
    <div class="clear"></div>
    <input type="submit" value="Submit" class="btn grey" style="border-radius:5px; background-color:grey; color:#fff;" />
    <div class="clear"></div>
</form>
{include file="footer.tpl"}