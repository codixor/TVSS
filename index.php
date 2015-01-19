<?php

@error_reporting(-1);
@ini_set("display_errors", "On");

session_start();
set_time_limit(0);

extract($_GET);
extract($_POST);

if (!file_exists("vars.php")){
    header("Location: install/index.php");
} else {
    include("vars.php");
    
    if (!isset($sitename)){
        header("Location: install/index.php");    
    }
}

$app['settings'] = $container->create('Settings');
$app['user'] 	 = $container->create('User');
$app['page'] 	 = $container->create('Page');
$app['movie'] 	 = $container->create('Movie');
$app['show'] 	 = $container->create('Show');
$app['cache']	 = $container->create('Cache', [$basepath]);
$app['misc'] 	 = $container->create('Misc');
$app['request']  = $container->create('Request');
$app['plugins']  = $container->create('Plugins');
$app['seo']  	 = $container->create('SEO');
$app['stream'] 	 = $container->create('Stream');

$modules = $app['settings']->getModules();

$default_language = $app['settings']->getSetting("default_language", true);
if (!$default_language || (is_array($default_language) && empty($default_language))){
    $default_language = "en";
}

if (Session::has('language')){
    $language = Session::get('language');
} else if (isset($_COOKIE['language']) && $_COOKIE['language']) {
    $language = $_COOKIE['language'];
	Session::put('language', $_COOKIE['language']);
} else {
    if (isset($_SERVER['GEOIP_COUNTRY_CODE'])){
        $country_code = $_SERVER['GEOIP_COUNTRY_CODE'];
        
        if ($country_code && isset($language_mapping) && isset($language_mapping[$country_code])){
            $language = $language_mapping[$country_code];
			Session::put('language', $language_mapping[$country_code]);
        } else {
			Session::put('language', $default_language);
            $language = $default_language;
        }

    } else {
		Session::put('language', $default_language);
        $language = $default_language;
    }
}
 
// force language based on url
if (isset($lang) && $lang!=$language){
    $lang = preg_replace("/[^a-z]/i","",$lang);
    if (file_exists($basepath."/language/".$lang."/general.php")){
		Session::put('language', $lang);
        $language = $lang;
    }
}
 
// force country if its posted in
if (isset($_POST['action']) && isset($_POST['lang'])){
    $_POST['lang'] = preg_replace("/[^a-z]/i","",$_POST['lang']);
    if (file_exists($basepath."/language/".$_POST['lang']."/general.php")){
		Session::put('language', $_POST['lang']);
        $language = $_POST['lang'];
        setcookie("language",$_POST['lang'],time()+60*60*24*30, "/");
        
        if (Session::has('loggeduser_id')){
			
            $app['user']->update(Session::get('loggeduser_id'), array("language" => $_POST['lang']));
        }
    }
}
 
if (isset($_SERVER['HTTP_REFERER'])){
    $referer = $_SERVER['HTTP_REFERER'];
} else {
    $referer = '';
}

if (!file_exists("language/$language/general.php")){
    $language = $default_language;
}

include("language/$language/general.php");
 
if (isset($menu) && $menu=='logout'){
    session_destroy();
    setcookie("guid","",time()-60*60, "/");
    print("<script>window.location='$baseurl';</script>");
    exit();
}

if (!Session::has('loggeduser_id') && isset($_COOKIE['guid'])){

    $res = $app['user']->cookieLogin($_COOKIE['guid']);
    if (!$res){
        setcookie("guid","",time()-60*60, "/");
    }
}
 
if (((isset($menu) && $menu=='login') || (isset($action) && $action=='login')) && isset($username) && isset($password)){

    $errors = $app['user']->login($username,$password);
    if ((isset($returnpath) && $returnpath) || (isset($menu) && $menu=='login')){
        print("<script>window.location='$baseurl$returnpath';</script>");
        exit();
    } 
}

if (isset($action) && $action=='change_avatar' && Session::has('loggeduser_id')){
    
    if (isset($_FILES['avatar_file']['name']) && $_FILES['avatar_file']['name']){
        if (($_FILES["avatar_file"]["type"] == "image/gif") || ($_FILES["avatar_file"]["type"] == "image/jpeg") || ($_FILES["avatar_file"]["type"] == "image/pjpeg") || ($_FILES["avatar_file"]["type"] == "image/png")){
            $filename = Session::get('loggeduser_id')."_".date("YmdHis").".jpg";            
			if (move_uploaded_file($_FILES["avatar_file"]["tmp_name"],"$basepath/thumbs/users/" . $filename)){
                
				$app['user']->update(Session::get('loggeduser_id'), array("avatar" => $filename));
				Session::push('loggeduser_details.avatar', $filename);
                $data = array();
                $data['user_id'] = Session::get('loggeduser_id');
                $data['user_data'] = Session::get('loggeduser_id');
                $data['target_id'] = 0;
                $data['target_data'] = array();
                $data['target_type'] = 0;
                $data['event_date'] = date("Y-m-d H:i:s");
                $data['event_type'] = 4;
                $data['event_comment'] = '';
                $app['stream']->addActivity($data);
                unset($app['stream']);
            }    
        }
    }
    
}

if (isset($action) && $action=='settings' && Session::has('loggeduser_id')){

    if (isset($_POST['notify_favorite']) && isset($_POST['notify_favorite'])){
        $notify_favorite = 1;
    } else {
        $notify_favorite = 0;
    }
    
    if (isset($_POST['notify_new']) && isset($_POST['notify_new'])){
        $notify_new = 1;
    } else {
        $notify_new = 0;
    }
    
    $app['user']->update(Session::get('loggeduser_id'), array("notify_favorite" => $notify_favorite,"notify_new" => $notify_new));
    
}

if (isset($theme) && $theme){
	Session::put('theme', $theme);
}

$hascache = 0;
$cache_writeable = $app['cache']->checkDir();

if ($cache_writeable){
    $cachekey_plain = date("YmdH").@$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI'].@json_encode($_GET).@json_encode($_POST);
    
    $cachekey = md5($cachekey_plain);
    $hascache = $app['cache']->getCache($cachekey);
}

$hascache = 0;

if ($hascache){
    print($hascache);
} else {
    @ob_start();

    if (!Session::has('theme')){
        $theme = $app['settings']->getSetting("theme");
        if (!count($theme)){
            $theme = 'svarog';
        } else {
            $theme = $theme->theme;
        }
    } else {
        $theme = Session::get('theme');
    }
    
    

   Rain\Tpl::configure([
		'base_url' 			=> $baseurl.'/',
		'tpl_dir' 			=> $basepath.'/templates/'.$theme.'/',
		'cache_dir' 		=> $basepath.'/cachefiles/'.$theme.'/',
		'cache_path' 		=> 'cachefiles/'.$theme.'/',
		'remove_comments' 	=> false,
		'debug' 			=> false,
		'path_replace' 		=> false,
		'auto_escape' 		=> false
	]);
	
	$app['tpl_compress'] = $container->create('Rain\\Tpl\\Plugin\\Compress');	

	Rain\Tpl::registerPlugin($app['tpl_compress']);
	$app['tpl'] = $container->create('Rain\\Tpl');
    
	$app['global']['templatepath']   = $baseurl.'/templates/'.$theme;
	$app['global']['cachekey_plain'] = $cachekey_plain;
    
    if (Session::has('fb_justregistered')){
		$app['global']['facebook_promo'] = 1;
		Session::forget('fb_justregistered');
    }

    
    $pages = $app['page']->getPagesMenu($language);
    
    $global_settings = $app['settings']->getMultiSettings(array("tv_guide","captchas","listing_style","adfly","analytics","seo_links","facebook","smart_bar","smartbar_size","smartbar_rows","maxtvperpage","maxmoviesperpage","countdown_free","countdown_user"), true);
    
    /* SEO links */
    
    if (!isset($global_settings['seo_links']) || !in_array($global_settings['seo_links'],array(0,1))){
        $global_settings['seo_links'] = 1;
    }
    
    if (!isset($global_settings['captchas']) || !$global_settings['captchas']){
        $global_settings['captchas'] = false;
    } else {
        $global_settings['captchas'] = true;
    }
    
    if (!isset($global_settings['tv_guide'])){
        $global_settings['tv_guide'] = true;
    } elseif (!$global_settings['tv_guide']){
        $global_settings['tv_guide'] = false;
    } else {
        $global_settings['tv_guide'] = true;
    }
    
    /* Video listing style */
    if (!isset($global_settings['listing_style']) || !$global_settings['listing_style'] || !in_array($global_settings['listing_style'],array("embeds", "links", "both"))){
        $global_settings['listing_style'] = "embeds";
    }    
    
    $listing_styles = array();
    if ($global_settings['listing_style'] == "embeds"){
        $listing_styles['embeds'] = true;
        $listing_styles['links'] = false;
    } elseif ($global_settings['listing_style'] == "links"){
        $listing_styles['embeds'] = false;
        $listing_styles['links'] = true;        
    } elseif ($global_settings['listing_style'] == "both"){
        $listing_styles['embeds'] = true;
        $listing_styles['links'] = true;        
    }
    
	$app['global']['listing_styles'] = $listing_styles;
    
    /* SmartBar */
    
    if (!isset($global_settings['smart_bar']) || !in_array($global_settings['smart_bar'],array(0,1))){
        $global_settings['smart_bar'] = 1;
    }
    
    
    /* Countdown before video */
    
    if (!Session::has('loggeduser_id')){
        if ($global_settings['countdown_free']==''){
            $global_settings['countdown_free'] = 20;
        }
        
        $global_settings['countdown'] = $global_settings['countdown_free'];
    } else {
    
        if ($global_settings['countdown_user']==''){
            $global_settings['countdown_user'] = 0;
        }
        
        $global_settings['countdown'] = $global_settings['countdown_user'];
    }
    
    /* Widgets */
    
    $widgets = $app['settings']->getWidgets();
    
    $shows = $app['show']->getRandomShow(5,$language);
    if (!count($shows)){
        $shows = '';
    } else {
        foreach($shows as $key=>$val){
            $shows[$key]['title']=stripslashes(stripslashes($shows[$key]['title']));
        }
    }
    
    $shows = '';
    
    // smartbar
    if ($app['user'] && isset($app['user']->id)){
        $smartbar_cachekey = "smartbar_".$user->id."_".date("YmdH")."_".$language; 
    } else {
        $smartbar_cachekey = "smartbar_global_".date("YmdH")."_".$language;
    }
    
    if (!isset($global_settings['smartbar_size']) || !$global_settings['smartbar_size']){
        $smartbar_size = "small";
    } else {
        $smartbar_size = $global_settings['smartbar_size'];
    }
    
    if (!isset($global_settings['smartbar_rows']) || !$global_settings['smartbar_rows']){
        $smartbar_rows = 2;
    } else {
        $smartbar_rows = $global_settings['smartbar_rows'];
    }
    
    switch($smartbar_size){
        
        case "small":
            $smartbar_width = 43;
            $smartbar_height = 65;
            $smartbar_cols = 15;
            break;
            
        case "medium":
            $smartbar_width = 74;
            $smartbar_height = 120;
            $smartbar_cols = 10;
            break;
            
        case "large":
            $smartbar_width = 114;
            $smartbar_height = 170;
            $smartbar_cols = 7;
            break;
            
        default:
            $smartbar_width = 43;
            $smartbar_height = 60;
            $smartbar_cols = 15;
            break;
    }
    
    $smartbar = $app['cache']->getCache($smartbar_cachekey);
    if (!$smartbar){
        $smartbar = $app['misc']->getSmartbar($app['user'],$app['movie'],$app['show'],$language,$smartbar_cols * $smartbar_rows);
        if ($cache_writeable){
            $app['cache']->saveCache($smartbar_cachekey,json_encode($smartbar));
        }
    } else {
        $smartbar = json_decode($smartbar,true);
    }
    
	$app['global']['smartbar_width']  = $smartbar_width;
	$app['global']['smartbar_height'] = $smartbar_height;
	$app['global']['smartbar_cols']   = $smartbar_cols;
    
    /* Featured shows */
    
    $featured_shows = $app['show']->getFeatured(4,$language);
    if (!count($featured_shows)){
        $featured_shows = '';
    } else {
        foreach($featured_shows as $key => $val){
            extract($val);
            $description = nl2br(stripslashes($description));
            $featured_shows[$key]['title'] = stripslashes($title);
            $featured_shows[$key]['description'] = $description;
        }
    }
    /* Categories */
    
    $tv_categories = $app['show']->getCategories($language);
    
    if (!count($tv_categories)){
        $tv_categories = '';
    }

    $movie_categories =  $app['movie']->getCategories($language);
    
    if (!count($movie_categories)){
        $movie_categories = '';
    }
    
    /* Rendering */
    
    if (!isset($menu) || !$menu || $menu=='login'){
        $menu = 'home';
    }
    $menu = preg_replace("/[^a-zA-Z0-9\-_]/","",$menu);
    
	$app['global']['smartbar']  		= $smartbar;
	$app['global']['baseurl']   		= $baseurl;
	$app['global']['sitename']   		= $sitename;
	$app['global']['siteslogan']   		= $siteslogan;
	$app['global']['menu']   			= $menu;
	$app['global']['pages']   		 	= $pages;
	$app['global']['global_settings']   = $global_settings;
	$app['global']['widgets']   		= $widgets;
	$app['global']['shows']   			= $shows;
	$app['global']['tv_categories']   	= $tv_categories;
	$app['global']['movie_categories']  = $movie_categories;
	$app['global']['current_url']   	= $_SERVER['REQUEST_URI'];
	$app['global']['modules']   		= $modules;
    

    if (!Session::has('loggeduser_id')){
		$app['global']['loggeduser_id']   = 0;
        $logged = 0;
    } else {
        $logged = 1;
		$app['global']['loggeduser_id']   	  = Session::get('loggeduser_id');
		$app['global']['loggeduser_username'] = Session::get('loggeduser_username');
    }

    $seodata = array();
    
    
    $seodata['menu'] = $menu;
    
    if (file_exists("language/$language/$menu.php")){
        require_once("language/$language/$menu.php");
    }
    
    $embed_languages = $app['misc']->getEmbedLanguages();
    $available_languages = array();
    foreach($embed_languages as $lang_code => $lang_data){
        if (substr_count($lang_code,"SUB")==0){
            $available_languages[] = $lang_data;
        }
    }
    
    $activeplugins = $app['plugins']->getInstalledPlugins();
    $plugin_menus = $app['plugins']->getFrontendMenu($activeplugins);
    if ($menu == "plugin"){
        if (isset($plugin) && $plugin && isset($plugin_menu) && $plugin_menu){
            $plugin = preg_replace("/[^a-zA-Z0-9\-_]/","",$plugin);
            $plugin_menu = preg_replace("/[^a-zA-Z0-9\-_]/","",$plugin_menu);
            
            if (file_exists($basepath."/plugins/".$plugin."/".$plugin_menu.".php")){
				$app['global']['plugin'] 	  = $plugin;
				$app['global']['plugin_menu'] = $plugin_menu;
                include($basepath."/plugins/".$plugin."/".$plugin_menu.".php");
            } else {
                unset($plugin);
                unset($plugin_menu);
				$app['global']['menu'] = 'home';               
                $menu = "home";
                include("home.php");                
            }
        } else {
            $app['global']['menu'] = 'home';
            $menu = "home";
            include("home.php");
        }
    } else {
        include("$menu.php");    
    }
    
    $seo_tags = $app['seo']->getSeo($seodata);
	$app['global']['seo'] 				  = $seo_tags;
	$app['global']['lang'] 				  = $lang;
	$app['global']['routes'] 			  = $routes;
	$app['global']['available_languages'] = $available_languages;
	$app['global']['embed_languages'] 	  = $embed_languages;
	$app['global']['plugin_menus'] 		  = $plugin_menus;
	$app['global']['absolute_url'] 		  = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    
	$app['tpl']->assign($app['global']);
    if ($menu == "plugin"){
        if (isset($plugin) && $plugin && isset($plugin_menu) && $plugin_menu){
            $app['tpl']->draw($basepath."/plugins/".$plugin."/templates/".$plugin_menu.".tpl");
        } else {
            $app['tpl']->draw('home');
        }
    } else {
        $app['tpl']->draw($menu);
    }
    
    $pagecontent = ob_get_contents();
    ob_end_clean();
    
    print($pagecontent);
    
    if ($cache_writeable){
        $app['cache']->saveCache($cachekey,$pagecontent);
    }
    
}
?>