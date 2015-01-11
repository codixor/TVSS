{config_load file=test.conf section="setup"}
{include file="header.tpl" title=header}

{if !$loggeduser_id}
<div class="span-16">
	<h1>{$lang.favorites_session_expired}</h1>
	
	<p>{$lang.favorites_we_are_sorry}</p>
</div>
{else}
<div class="span-16">
	<h1>{$lang.favorites_favorite_movies}</h1>
	
	{if $favorite_movies}
		{php} $i=1; {/php}
		{foreach from=$favorite_movies key=id item=movie}
		
			<div class="flickr item left" {php} if ($i%5!=0) print('style="margin-right:10px"'); {/php}>
				{if $global_settings.seo_links}
					<a href="{$baseurl}/{$routes.movie}/{$movie.perma}"  class="">
						<img class="tooltip" original-title="{$movie.title}" alt="" src="{$templatepath}/timthumb.php?src={$baseurl}/thumbs/{$movie.thumb}&amp;w=106&amp;h=160&amp;zc=1" style="width:106px; height:160px">
					</a>
				{else}
					<a href="{$baseurl}/index.php?menu=watchmovie&perma={$movie.perma}"  class="">
						<img class="tooltip" original-title="{$movie.title}" alt="" src="{$templatepath}/timthumb.php?src={$baseurl}/thumbs/{$movie.thumb}&amp;w=106&amp;h=160&amp;zc=1" style="width:106px; height:160px">
					</a>
				{/if}
			</div>
			
			{php}if ($i%5 == 0){ print('<div class="clear"></div>'); } {/php}
			{php} $i++; {/php}
		{/foreach}
		<div class="clear"></div><br />
	{else}
		<p>{$lang.favorites_no_movie|replace:'#baseurl#':$baseurl}</p>
		<form method="post" id="search-form" action="{$baseurl}/index.php">
			<input type="hidden" name="menu" value="search" />
			<input type="text" name="query" value="{$lang.search_tip}" onfocus="if(this.value=='{$lang.search_tip}') this.value=''" onblur="if(this.value=='') this.value='{$lang.search_tip}'" style="width:200px" /> <input type="submit" value="{$lang.search_button}" class="btn tab02d grey" style="width:100px; cursor:pointer;" />
		</form>
		<div class="clear"></div><br /><br />
	{/if}
	
	<h1>{$lang.favorites_favorite_shows}</h1>
	
	{if $favorite_shows}
		{php} $i=1; {/php}
		{foreach from=$favorite_shows key=id item=show}
		
			<div class="flickr item left" {php} if ($i%5!=0) print('style="margin-right:10px"'); {/php}>
				{if $global_settings.seo_links}
					<a href="{$baseurl}/{$routes.show}/{$show.permalink}"  class="">
						<img class="tooltip" original-title="{$show.title}" alt="" src="{$templatepath}/timthumb.php?src={$baseurl}/thumbs/{$show.thumbnail}&amp;w=106&amp;h=160&amp;zc=1" style="width:106px; height:160px">
					</a>
				{else}
					<a href="{$baseurl}/index.php?menu=show&perma={$show.permalink}"  class="">
						<img class="tooltip" original-title="{$show.title}" alt="" src="{$templatepath}/timthumb.php?src={$baseurl}/thumbs/{$show.thumbnail}&amp;w=106&amp;h=160&amp;zc=1" style="width:106px; height:160px">
					</a>
				{/if}
			</div>
			
			{php}if ($i%5 == 0){ print('<div class="clear"></div>'); } {/php}
			{php} $i++; {/php}
		{/foreach}
		<div class="clear"></div><br />
	{else}
		<p>{$lang.favorites_no_show|replace:'#baseurl#':$baseurl}</p>
		<form method="post" id="search-form" action="{$baseurl}/index.php">
			<input type="hidden" name="menu" value="search" />
			<input type="text" name="query" value="{$lang.search_tip}" onfocus="if(this.value=='{$lang.search_tip}') this.value=''" onblur="if(this.value=='') this.value='{$lang.search_tip}'" style="width:200px" /> <input type="submit" value="{$lang.search_button}" class="btn tab02d grey" style="width:100px; cursor:pointer;" />
		</form>
		<div class="clear"></div><br /><br />
	{/if}
	
</div>

{/if}
{include file="sidebar.tpl" title=sidebar}

{include file="footer.tpl" title=footer}