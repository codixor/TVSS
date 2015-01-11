<ul id="simple_wizard-titles" class="stepy-titles clearfix">
	<li id="simple_wizard-title-0">
		<div>mySQL details</div>
		<span>Enter your database info</span>
		<span class="stepNb">1</span>
	</li>
	
	<li id="simple_wizard-title-1">
		<div>Site info</div>
		<span>Paths, urls, etc.</span>
		<span class="stepNb">2</span>
	</li>
	
	<li id="simple_wizard-title-2">
		<div>Extra Security</div>
		<span>(added by Hawke663)</span>
		<span class="stepNb">3</span>
	</li>
	
	<li id="simple_wizard-title-3">
		<div>Admin user</div>
		<span>Your first user</span>
		<span class="stepNb">4</span>
	</li>
	
	<li id="simple_wizard-title-4">
		<div>All done</div>
		<span>Enjoy your site</span>
		<span class="stepNb">5</span>
	</li>
</ul>
<br />		                     
<form method="post" class="stepy-wizzard form-horizontal" id="simple_wizard">
    <fieldset title="" class="step" id="simple_wizard-step-0">
        <h3>Installation in progress...</h3>
        
        Creating configuration file...
        <?php 
            $content = file_get_contents("vars.sample");
            $content = str_replace("[[LICENSE_KEY]]", "http://tvstreamscript.tk" ,$content);
            $content = str_replace("[[DBHOST]]",$_SESSION['mysql_host'],$content);
            $content = str_replace("[[DBUSER]]",$_SESSION['mysql_user'],$content);
            $content = str_replace("[[DBPASS]]",$_SESSION['mysql_pass'],$content);
            $content = str_replace("[[DATABASE]]",$_SESSION['mysql_name'],$content);
            $content = str_replace("[[BASEURL]]",$_SESSION['site_url'],$content);
            $content = str_replace("[[BASEPATH]]",$_SESSION['site_path'],$content);
            $content = str_replace("[[SITENAME]]",$_SESSION['site_title'],$content);
            
            $handle = fopen("../vars.php","w+");
            fwrite($handle, "<?php\n");
            fwrite($handle, $content);
            fclose($handle);
            
            print("<span style='color:#00aa00'>Success</span><br />");
        ?>
        
        Cleaning tables...
        <?php 
            require_once("../vars.php");
            
            $res = ORM::raw_execute("SHOW TABLES");
            if ($res && count($res) > 0){
                foreach($res as $s){
                    $table_name = array_pop($s);
                    if ($table_name){
                        ORM::raw_execute("DROP TABLE $table_name");
                    }
                }
            }
            
            print("<span style='color:#00aa00'>Success</span><br />");
        ?>
        
        Creating new tables...
        <?php 
            $table = ORM::raw_execute("CREATE TABLE `activity` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user_id` int(11) NOT NULL,
                                      `target_id` int(11) NOT NULL,
                                      `user_data` text NOT NULL,
                                      `target_data` text NOT NULL,
                                      `target_type` tinyint(4) NOT NULL,
                                      `event_date` datetime NOT NULL,
                                      `event_type` tinyint(4) NOT NULL,
                                      `event_comment` varchar(500) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`),
                                      KEY `target_id` (`target_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `admin` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `username` varchar(100) NOT NULL,
                                      `password` varchar(255) NOT NULL,
                                      PRIMARY KEY  (`id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `broken_episodes` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `episodeid` int(11) NOT NULL,
                                      `reportdate` datetime NOT NULL,
                                      `problem` varchar(255) NOT NULL,
                                      `ip` varchar(30) NOT NULL,
                                      `user_id` int(11) NOT NULL,
                                      `user_agent` varchar(255) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `broken_movies` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `movieid` int(11) NOT NULL,
                                      `reportdate` datetime NOT NULL,
                                      `problem` varchar(255) NOT NULL,
                                      `ip` varchar(30) NOT NULL,
                                      `user_id` int(11) NOT NULL,
                                      `user_agent` varchar(255) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `embeds` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `episode_id` int(11) NOT NULL,
                                      `embed` text NOT NULL,
                                      `link` varchar(255) NOT NULL,
                                      `lang` varchar(20) NOT NULL,
                                      `weight` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `episode_id` (`episode_id`),
                                      KEY `lang` (`lang`),
                                      KEY `weight` (`weight`)									  
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `episodes` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `show_id` int(11) NOT NULL,
                                      `season` int(11) NOT NULL,
                                      `episode` int(11) NOT NULL,
                                      `title` varchar(255) NOT NULL,
                                      `description` text NOT NULL,
                                      `embed` text NOT NULL,
                                      `date_added` datetime NOT NULL,
                                      `thumbnail` varchar(255) NOT NULL,
                                      `views` bigint(20) NOT NULL,
                                      `checked` tinyint(4) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `category_id` (`show_id`),
                                      KEY `season` (`season`),
                                      KEY `episode` (`episode`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `friends` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user1` int(11) NOT NULL,
                                      `user2` int(11) NOT NULL,
                                      `date_added` datetime NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user1` (`user1`),
                                      KEY `user2` (`user2`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `likes` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user_id` int(11) NOT NULL,
                                      `target_id` int(11) NOT NULL,
                                      `target_type` tinyint(4) NOT NULL,
                                      `comment` varchar(500) NOT NULL,
                                      `vote` tinyint(4) NOT NULL,
                                      `date_added` datetime NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`),
                                      KEY `target_id` (`target_id`),
                                      KEY `target_type` (`target_type`),
                                      KEY `date_added` (`date_added`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `modules` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `perma` varchar(30) NOT NULL,
                                      `title` varchar(30) NOT NULL,
                                      `status` tinyint(4) NOT NULL,
                                      PRIMARY KEY  (`id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `movies` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `title` varchar(200) NOT NULL,
                                      `perma` varchar(50) NOT NULL,
                                      `description` text NOT NULL,
                                      `thumb` varchar(50) NOT NULL,
                                      `embed` text NULL,
                                      `views` bigint(20) NOT NULL DEFAULT '0',
                                      `imdb_id` varchar(30) NOT NULL,
                                      `imdb_rating` float NOT NULL,
                                      `date_added` datetime NOT NULL,
                                      `meta` text NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `imdb_id` (`imdb_id`),
                                      KEY `title` (`title`),
                                      KEY `perma` (`perma`),
                                      KEY `imdb_rating` (`imdb_rating`)
                                    ) DEFAULT CHARSET=utf8 ");
            
            $table = ORM::raw_execute("CREATE TABLE `movie_embeds` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `movie_id` int(11) NOT NULL,
                                      `embed` varchar(1024) NOT NULL,
                                      `link` varchar(255) NOT NULL,
                                      `lang` varchar(20) NOT NULL,
                                      `weight` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `movie_id` (`movie_id`),
                                      KEY `lang` (`lang`),
                                      KEY `weight` (`weight`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `movie_ratings` (
                                  `id` int(11) NOT NULL auto_increment,
                                  `movieid` int(11) NOT NULL,
                                  `rating` float NOT NULL,
                                  `ip` varchar(30) NOT NULL,
                                  `ratingdate` datetime NOT NULL,
                                  PRIMARY KEY  (`id`),
                                  KEY `movieid` (`movieid`),
                                  KEY `rating` (`rating`),
                                  KEY `ip` (`ip`)
                                ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `movie_tags` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `tag` varchar(150) NOT NULL,
                                      `perma` varchar(100) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `perma` (`perma`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `movie_tags_join` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `tag_id` int(11) NOT NULL,
                                      `movie_id` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `tag_id` (`tag_id`),
                                      KEY `movie_id` (`movie_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `pages` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `title` text NOT NULL,
                                      `permalink` varchar(255) NOT NULL,
                                      `content` text NOT NULL,
                                      `parent_id` int(11) NOT NULL,
                                      `visible` tinyint(4) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `permalink` (`permalink`),
                                      KEY `parent_id` (`parent_id`),
                                      KEY `visible` (`visible`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `plugins` (
                                  `id` int(11) NOT NULL auto_increment,
                                  `dirname` varchar(30) NOT NULL,
                                  `name` varchar(255) NOT NULL,
                                  `description` varchar(255) NOT NULL,
                                  `install_url` varchar(100) NOT NULL,
                                  `start_url` varchar(100) NOT NULL,
                                  `author` varchar(255) NOT NULL,
                                  `author_url` varchar(100) NOT NULL,
                                  PRIMARY KEY  (`id`)
                                ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `ratings` (
                                  `id` int(11) NOT NULL auto_increment,
                                  `episodeid` int(11) NOT NULL,
                                  `rating` float NOT NULL,
                                  `ip` varchar(30) NOT NULL,
                                  `ratingdate` datetime NOT NULL,
                                  PRIMARY KEY  (`id`),
                                  KEY `episodeid` (`episodeid`),
                                  KEY `rating` (`rating`),
                                  KEY `ip` (`ip`)
                                ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `requests` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user_id` int(11) NOT NULL,
                                      `request_date` datetime NOT NULL,
                                      `message` text NOT NULL,
                                      `response` text NULL,
                                      `status` tinyint(4) NOT NULL,
                                      `votes` int(11) NOT NULL default '1',
                                      PRIMARY KEY  (`id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `request_votes` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `request_id` int(11) NOT NULL,
                                      `user_id` int(11) NOT NULL,
                                      `vote_date` datetime NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `request_id` (`request_id`),
                                      KEY `user_id` (`user_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `settings` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `title` varchar(50) NOT NULL,
                                      `value` text NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `title` (`title`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `shows` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `title` varchar(255) NOT NULL,
                                      `description` text NOT NULL,
                                      `thumbnail` varchar(120) NOT NULL,
                                      `permalink` varchar(255) NOT NULL,
                                      `sidereel_url` varchar(255) NOT NULL,
                                      `imdb_id` varchar(20) NOT NULL,
                                      `type` tinyint(4) NOT NULL,
                                      `featured` tinyint(4) NOT NULL default '0',
                                      `last_episode` datetime NOT NULL,
                                      `imdb_rating` float NOT NULL,
                                        `meta` text NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `permalink` (`permalink`),
                                      KEY `title` (`title`),
                                      KEY `featured` (`featured`),
                                      KEY `last_episode` (`last_episode`),
                                      KEY `imdb_id` (`imdb_id`),
                                      KEY `imdb_rating` (`imdb_rating`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `similar_movies` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `movie1` int(11) NOT NULL,
                                      `movie2` int(11) NOT NULL,
                                      `score` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `movie1` (`movie1`),
                                      KEY `movie2` (`movie2`)
                                    ) DEFAULT CHARSET=utf8 ");
            
            $table = ORM::raw_execute("CREATE TABLE `similar_shows` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `show1` int(11) NOT NULL,
                                      `show2` int(11) NOT NULL,
                                      `score` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `show1` (`show1`),
                                      KEY `show2` (`show2`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `submitted_links` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user_id` int(11) NOT NULL,
                                      `type` tinyint(4) NOT NULL,
                                      `imdb_id` varchar(20) NOT NULL,
                                      `season` int(11) NOT NULL,
                                      `episode` int(11) NOT NULL,
                                      `link` varchar(255) NOT NULL,
                                      `language` varchar(10) NOT NULL,
                                      `date_submitted` datetime NOT NULL,
                                      `status` tinyint(4) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`),
                                      KEY `type` (`type`),
                                      KEY `imdb_id` (`imdb_id`),
                                      KEY `season` (`season`),
                                      KEY `episode` (`episode`),
                                      KEY `language` (`language`),
                                      KEY `status` (`status`)
                                    ) DEFAULT CHARSET=utf8 ");
            
            $table = ORM::raw_execute("CREATE TABLE `tv_submits` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `episode_id` int(11) NOT NULL,
                                      `type` tinyint(4) NOT NULL,
                                      `link` varchar(255) NOT NULL,
                                      `timestamp` datetime NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `episode_id` (`episode_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `tv_tags` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `tag` varchar(150) NOT NULL,
                                      `perma` varchar(100) character set utf8 collate utf8_hungarian_ci NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `perma` (`perma`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `tv_tags_join` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `tag_id` int(11) NOT NULL,
                                      `show_id` int(11) NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `tag_id` (`tag_id`),
                                      KEY `show_id` (`show_id`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `users` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `username` varchar(25) NOT NULL,
                                      `password` varchar(255) NOT NULL,
                                      `email` varchar(150) NOT NULL,
                                      `fb_id` bigint(20) NOT NULL,
                                      `fb_session` varchar(200) NOT NULL,
                                      `avatar` varchar(100) NOT NULL DEFAULT 'no-pic.jpg',
                                      `notify_favorite` tinyint(4) NOT NULL default '1',
                                      `notify_new` tinyint(4) NOT NULL default '1',
                                      `language` varchar(2) NOT NULL default 'hu',
                                      PRIMARY KEY  (`id`),
                                      KEY `notify_favorite` (`notify_favorite`),
                                      KEY `notify_new` (`notify_new`),
                                      KEY `username` (`username`),
                                      KEY `password` (`password`),
                                      KEY `fb_id` (`fb_id`),
                                      KEY `language` (`language`)
                                    ) DEFAULT CHARSET=utf8");
            
            $table = ORM::raw_execute("CREATE TABLE `watches` (
                                      `id` int(11) NOT NULL auto_increment,
                                      `user_id` int(11) NOT NULL,
                                      `target_id` int(11) NOT NULL,
                                      `target_type` tinyint(4) NOT NULL,
                                      `date_added` datetime NOT NULL,
                                      PRIMARY KEY  (`id`),
                                      KEY `user_id` (`user_id`),
                                      KEY `target_id` (`target_id`),
                                      KEY `target_type` (`target_type`)
                                    ) DEFAULT CHARSET=utf8");
            
			$table = ORM::raw_execute("ALTER TABLE `movie_ratings` 	  ADD CONSTRAINT `movie_ratings_movies` FOREIGN KEY (`movieid`) REFERENCES `movies` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `movie_embeds`  	  ADD CONSTRAINT `movie_embeds_movies` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `movie_tags_join`  ADD CONSTRAINT `movie_tags_join_movies` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `movie_tags_join`  ADD CONSTRAINT `movie_tags_join_movie_tags` FOREIGN KEY (`tag_id`) REFERENCES `movie_tags` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `broken_movies` 	  ADD CONSTRAINT `broken_movies_movies` FOREIGN KEY (`movieid`) REFERENCES `movies` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `broken_movies`    ADD CONSTRAINT `broken_movies_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `broken_episodes`  ADD CONSTRAINT `broken_episodes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `broken_episodes`  ADD CONSTRAINT `broken_episodes_episodes` FOREIGN KEY (`episodeid`) REFERENCES `episodes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `ratings`		  ADD CONSTRAINT `ratings_episodes` FOREIGN KEY (`episodeid`) REFERENCES `episodes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `likes`			  ADD CONSTRAINT `likes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `requests`    	  ADD CONSTRAINT `requests_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `request_votes`	  ADD CONSTRAINT `request_votes_requests` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `request_votes`	  ADD CONSTRAINT `request_votes_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `submitted_links`  ADD CONSTRAINT `submitted_links_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `tv_submits` 	  ADD CONSTRAINT `tv_submits_episodes` FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `tv_tags_join`	  ADD CONSTRAINT `tv_tags_join_tv_tags` FOREIGN KEY (`tag_id`) REFERENCES `tv_tags` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `tv_tags_join`	  ADD CONSTRAINT `tv_tags_join_shows` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
			$table = ORM::raw_execute("ALTER TABLE `watches`	      ADD CONSTRAINT `watches_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE;");
            
			print("<span style='color:#00aa00'>Success</span><br />");
        ?>
        
        Inserting admin user...
        <?php 
            
            $admin_user = $_SESSION['admin_user'];			
            $admin_pass = password_hash($_SESSION['admin_pass'], PASSWORD_BCRYPT);
			
            $ins = ORM::raw_execute("INSERT INTO admin(`username`, `password` ) VALUES('$admin_user', '$admin_pass')");
            
            print("<span style='color:#00aa00'>Success</span><br />");
        ?>
        
        Activating modules...
        <?php 
            $ins = ORM::raw_execute("INSERT INTO `modules` VALUES (1, 'tv_shows', 'TV shows', 1)");
            $ins = ORM::raw_execute("INSERT INTO `modules` VALUES (2, 'movies', 'Movies', 1)");
            $ins = ORM::raw_execute("INSERT INTO `modules` VALUES (7, 'submit_links', 'Submit links', 1)");
            $ins = ORM::raw_execute("INSERT INTO `modules` VALUES (6, 'requests', 'Requests', 1)");
            
            print("<span style='color:#00aa00'>Success</span><br />");
        ?>
        
        Adding default settings...
        <?php 
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (1, 'default_language', 'en')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (2, 'maxtvperpage', '50')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (3, 'maxmoviesperpage', '50')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (4, 'countdown_free', '30')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (5, 'countdown_user', '0')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (6, 'seo_links', '1')");
            $ins = ORM::raw_execute("INSERT INTO `settings` VALUES (7, 'smart_bar', '0')");
            
            print("<span style='color:#00aa00'>Success</span><br />");
        ?>
		
		Changing Admin Panel Name...
		<?php
		
		
rename(realpath(dirname(__FILE__)).'/../control',realpath(dirname(__FILE__)).'/../'.$_SESSION['admin_folder']);
print("<span style='color:#00aa00'>Success</span><br />");
?>
        
        Congratulation. Your site is all set up<br /><br />
        Next steps:<br /><br />
        <ul>
            <li><a href="<?php print($_SESSION['site_url']); ?>/<?php print($_SESSION['admin_folder']); ?>">Log in to your control panel</a></li>
            <li><a href="http://tvss.co">Check out the documentation on how to add your cronjobs</a></li>
        </ul>
        <br /><br />
        Wish you all the best luck with your new site. Have fun! - TVSS.co Team
        <br /><br />
    </fieldset>
</form>