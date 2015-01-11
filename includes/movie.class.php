<?php

class Movie{

    public function __construct(){
        
    }
    
    
    public function getEmbedType($embed, $link = false){
        global $basepath;
        
        include($basepath."/includes/filehost.list.php");
        
        $embed = strtolower(stripslashes(stripslashes(urldecode($embed))));
        foreach($filehosts as $match => $filehost_name){
            if (substr_count($embed,$match)){
                return $filehost_name;
            }
        }
        
        if ($link){
            $url_parts = parse_url($link);
            if (isset($url_parts['host']) && $url_parts['host']){
                if (strpos($url_parts['host'],"www.") === 0){
                    $url_parts['host'] = substr($url_parts['host'],4);
                }
                return $url_parts['host'];
            } else {
                return "";
            }
        } else {
            return "";
        }
    }
	   
    public function deleteEmbed($movie_id, $embedid){
		$e =  ORM::for_table('movie_embeds')->where_equal('movie_id', $movie_id)->where_equal('id', $embedid)->delete_many();        
    }
    
    public function deleteAllEmbeds($movie_id){
		$e =  ORM::for_table('movie_embeds')->where_equal('movie_id', $movie_id)->delete_many();
    }
    
    public function getList($page = null, $start=null, $limit=null, $sortby="id", $sortdir="DESC", $search_term = null){
        if (!$limit){
            $limit = 50;
        }
        
		if ($page && !$search_term){
            $start = ($page-1)*$limit;
			$e = ORM::for_table('movies')->order_by_expr($sortby, $sortdir)->limit($start, $limit)->find_array();
        } elseif ($page && $search_term){
            $start = ($page-1)*$limit;
            $e = ORM::for_table('movies')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($start && !$search_term) {            
            $e = ORM::for_table('movies')->order_by_expr($sortby.' '.$sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($start && !$search_term) {            
            $e = ORM::for_table('movies')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($search_term) {
            $e = ORM::for_table('movies')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->find_array();
        } else{
            $e = ORM::for_table('movies')->order_by_expr($sortby.' '.$sortdir)->limit($limit)->find_array();
        }       
		
        $movies = [];
        $ids 	= [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $movies[$id]				 = $s;
                $movies[$id]['title'] 		 = json_decode($movies[$id]['title'], true);
                $movies[$id]['description']  = json_decode($movies[$id]['description'], true);
                $movies[$id]['meta']  		 = json_decode($movies[$id]['meta'], true);
                $movies[$id]['embed_count']  = 0;
                $ids[] = $id;                
            }
            
            $e = ORM::for_table('movie_embeds')->select_expr('COUNT(*)', 'embed_count')->select('movie_id')->where_in('movie_id', $ids)->group_by('movie_id')->find_array();
            if (count($e) > 0){
                foreach($e as $s){
                    $movies[$s['movie_id']]['embed_count'] = $s['embed_count'];
                }
            }
        }
        return $movies;
    }
    
    public function checkMovie($title, $year){
	
        $check = ORM::for_table('movies')->where_raw('MATCH (title) AGAINST (? IN BOOLEAN MODE)', [$title])->find_one();
        if (!$check){
            return 0;
        } else {
            return 1;
        }
    }
    
    public function getNewest($date_from, $limit=10){
        $res = [];
		ORM::for_table('movies')->select_many('id', 'title', 'thumb', 'perma')->where_gte('date_added', $date_from)->limit($limit)->find_array();
        if (count($e) > 0){
            foreach($e as $s){
                $res[$s['id']] = $s;
            }
        }
        
        return $res;
    }
    
    public function updateEmbedCode($movieid, $embedid, $embedcode){
        $embedcode = stripslashes(stripslashes(urldecode($embedcode)));
		$e = ORM::for_table('movie_embeds')->where_equal('movie_id', $movieid)->where_equal('id', $embedid)->find_one();
		$e->set('embed', $embedcode);
		$e->save();
    }
    
    public function getEmbeds($movieid){
        $embeds = [];
        $counter = 0;
		$e = ORM::for_table('movie_embeds')->where_equal('movie_id', $movieid)->order_by_desc('weight')->find_array();
        // getting the rest
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                if ($embed){
                    
                    $embeds[$counter]['type'] = $this->getEmbedType($embed, $link);
                    if (substr_count($embed,"<span id='flvplayer'>")){
                        $embed = '<iframe src="/embed.php?id='.$id.'&movie='.$movieid.'" width="620" height="400" frameborder="0" scrolling="no"></iframe>';
                    }
                    
					$embeds[$counter]['embed']  = stripslashes(stripslashes(urldecode($embed)));                    
                    $embeds[$counter]['lang']   = $lang;
                    $embeds[$counter]['id']	    = $id;
                    $embeds[$counter]['weight'] = $weight;
                    $embeds[$counter]['link']	= $link;
                    $counter++;
                }
            }
        }
        
        return $embeds;
    }

    public function validateCategory($category){
		$errors = array();
        if (!isset($_SESSION['global_languages'])){
            return array("99" => "Session expired");
        }
        
        foreach($_SESSION['global_languages'] as $lang_code => $lang_name){
            if (!isset($category[$lang_code]) || !$category[$lang_code]){
                $errors[$lang_code] = "Please enter a $lang_name category title";
            }
        }
        return $errors;
    }
    
    public function addCategory($category){

        $perma 	  = $this->makePerma($category['en']);
        $category = json_encode($category);
        
		$e = ORM::for_table('movie_tags')->where_any_is([['tag' => $category], ['perma' => $perma]])->find_one();
        if (!$e){ 
			$ins = ORM::for_table('movie_tags')->create();
			$ins->tag     = $category;
			$ins->perma   = $perma;
			$ins->save();
            return 1;            
        } else {
            return 0;
        }
    }
    
    public function getCategoryCount($tag_id){
		$e = ORM::for_table('movies')->join('movie_tags_join', 'movie_tags_join.movie_id = movies.id')->where('movie_tags_join.tag_id', $tag_id)->count();
        return $e;
    }
    
    public function getMoviesByCategory($tag_id, $sortby=null, $lang=null, $page = 1, $limit = 40){
        $page = (int) $page;
        $limit = (int) $limit;
        
        if (!$page) $page = 1;
        if (!$limit) $limit = 40;
        
        $start = ($page-1) * $limit;
        
        if (!$sortby || $sortby=='abc'){
			$e = ORM::for_table('movies')
			->select('movies.*')
			->join('movie_tags_join', 'movies.id = movie_tags_join.movie_id')
			->where('movie_tags_join.tag_id', $tag_id)	
			->order_by_asc('movies.title')
			->limit($limit)
			->offset($start)
			->find_array();
        } elseif ($sortby=='imdb_rating') {
			$e = ORM::for_table('movies')
			->select('movies.*')
			->join('movie_tags_join', 'movies.id = movie_tags_join.movie_id')
			->where('movie_tags_join.tag_id', $tag_id)	
			->order_by_desc('movies.imdb_rating')
			->limit($limit)
			->offset($start)
			->find_array();
        } elseif ($sortby=='date') {
			$e = ORM::for_table('movies')
			->select('movies.*')
			->join('movie_tags_join', 'movies.id = movie_tags_join.movie_id')
			->where('movie_tags_join.tag_id', $tag_id)	
			->order_by_desc('movies.date_added')
			->limit($limit)
			->offset($start)
			->find_array();
        } else {
           $e = ORM::for_table('movies')
			->select('movies.*')
			->join('movie_tags_join', 'movies.id = movie_tags_join.movie_id')
			->where('movie_tags_join.tag_id', $tag_id)	
			->order_by_desc('movies.id')
			->limit($limit)
			->offset($start)
			->find_array();
        }

        $movies = [];
        if (count($e) > 0){
            $ids = [];
            
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){
                $flags = $this->getFlags($ids);                
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
        }
        return $movies;
    }
    
    private function getDomain($url){
        $url = strtolower($url);
        $url = str_replace("https://","",$url);
        $url = str_replace("http://","",$url);
        $url = str_replace("www.","",$url);
        $tmp = explode("%3f",$url);
        $url = $tmp[0]; 
        $tmp = explode("%2f",$url);
        $url = $tmp[0];  
        $tmp = explode("/",$url);
        $url = $tmp[0];
        $tmp = explode("?",$url);
        $url = $tmp[0];
        return $url;
    } 
    
    public function getLink($link_id){
		$res = ORM::for_table('submitted_links')->where('submitted_links.type', 2)->where('id', $link_id)->find_array();
        if (count($e) == 1){
            return $e[0];            
        } else {
            return false;
        }

    }
    
    /* Returns a list of user submitted links for the given movie */	
   public function getLinks($status = null, $lang=false, $p=null, $l=null, $sortby=null){
        global $baseurl;
		
        $start = ($p-1)*$l;
		
		if ($status && $sortby == 'status'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('movies', 'movies.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('movies.title', 'movie_title')
			->select('movies.id', 'movie_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->where('submitted_links.status', $status)
			->order_by_asc('status')
			->limit($l)
			->offset($start)
			->find_array();
        }elseif($status && $sortby == 'date'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('movies', 'movies.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('movies.title', 'movie_title')
			->select('movies.id', 'movie_id')
			->select('users.username')
			->where('submitted_links.type', 2)
			->where('submitted_links.status', $status)
			->order_by_desc('date_submitted')
			->limit($l)
			->offset($start)
			->find_array();		
		}elseif(!$status && $sortby == 'date'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('movies', 'movies.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('movies.title', 'movie_title')
			->select('movies.id', 'movie_id')
			->select('users.username')
			->where('submitted_links.type', 2)
			->order_by_desc('date_submitted')
			->limit($l)
			->offset($start)
			->find_array();	
		}elseif(!$status && $sortby == 'status'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('movies', 'movies.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('movies.title', 'movie_title')
			->select('movies.id', 'movie_id')
			->select('users.username')
			->where('submitted_links.type', 2)
			->order_by_asc('status')
			->limit($l)
			->offset($start)
			->find_array();	
		}else{
			$e = ORM::for_table('submitted_links')
			->left_outer_join('movies', 'movies.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('movies.title', 'movie_title')
			->select('movies.id', 'movie_id')
			->select('users.username')
			->where('submitted_links.type', 2)
			->order_by_asc('status')
			->find_array();	
		}      
        $links = [];
        if (count($e) > 0){
            foreach($e as $s){
                $links[$s['id']] = $s;
                
                if ($s['movie_title']){
                    $links[$s['id']]['movie_title'] 	= json_decode($links[$s['id']]['movie_title'],true);
                    if ($lang){
                        $links[$s['id']]['movie_title'] = $links[$s['id']]['movie_title'][$lang];                        
                    } 
                }
            }
        }
        
        return $links;
    }
        
    public function getCategoryByPerma($perma, $lang=null){

        $e = ORM::for_table('movie_tags')->select_many('id', 'tag')->where('perma', $perma)->find_array();
        if (count($e) == 1){
            extract($e[0]);
            
            $tag = json_decode($tag, true);
            if ($lang){
                $tag = $tag[$lang];
            }
            
            return ['id' => $id, 'tag' => $tag];
        } else {
            return '';
        }
    }
    
    public function saveCategories($movie_id, $categories){
		ORM::for_table('movie_tags_join')->where_equal('movie_id', $movie_id)->delete_many();
        foreach($categories as $key=>$val){
			$insert = ORM::for_table('movie_tags_join')->create();
			$insert->movie_id = $movie_id;
			$insert->tag_id	  = $val;
			$insert->save();
        }
    }
    
    /* returns a list of category ids for the given movie */
    
    public function getMovieCategories($movieid){
        $e = ORM::for_table('movie_tags_join')->where_equal('movie_id', $movieid)->find_array();       
        $tags = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $tags[]=$tag_id;
            }
        }
        return $tags;
    }
    
    /* returns a list of categories (including ids and details) for the given movie */
    
    public function getMovieCategoryDetails($movieid, $lang=null){
        $e = ORM::for_table('movie_tags')->join('movie_tags_join', 'movie_tags_join.tag_id = movie_tags.id')->where('movie_tags_join.movie_id', $movieid)->find_array();
        $tags = [];
        if (count($e) > 0){
            foreach($e as $s){
                $s['tag'] = json_decode(utf8_encode($s['tag']), true);
                if ($lang){
                    $s['tag'] = $s['tag'][$lang];
                }
                
                $tags[$s['id']] = $s;
            }
        }
        return $tags;
    }
    
    public function deleteTag($tagid){
		$e = ORM::for_table('movie_tags')->where_equal('id', $tag_id)->delete_many();        
    }
    
    public function getCategories($lang=null, $limit=0, $order="tag ASC"){
        $categories = [];
        
        if ($limit){ 
			$e = ORM::for_table('movie_tags')->order_by_asc('tag')->limit($limit)->find_array();
		} else { 
			$e = ORM::for_table('movie_tags')->order_by_asc('tag')->find_array();
		}
		
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                
                $tag = json_decode(utf8_encode($tag), true);
                if (!$lang){
                    $categories[$id]['name'] = $tag;
                } else {
                    $categories[$id]['name'] = $tag[$lang];
                }
                $categories[$id]['perma']=$perma;
            }
        }
        
        return $categories;
    }
   
    public function getRatingCount(){
		$total = ORM::for_table('movie_ratings')->count();
        return $total;
    }
    
	public function getMovRatingCount($movieid){
        $movtotal  = ORM::for_table('movie_ratings')->where('movieid', $movieid)->count();        
        return $movtotal;
    }
	
    public function getLinksCount(){
		$total = ORM::for_table('submitted_links')->where('type', 2)->count();
        return $total;
    }
   
    public function getRatings($page, $lang=false){
        $start = ($page-1)*100;
		
		$e = ORM::for_table('movie_ratings')
			->join('movies', 'movies.id = movie_ratings.movieid')
			->select_many(['ratingid' => 'movie_ratings.id'], ['movieid' => 'movies.id'], 'movies.title', 'movies.title', 'movie_ratings.rating', 'movie_ratings.ip', 'movie_ratings.ratingdate')
			->order_by_desc('ratingdate')
			->limit(100)
			->offset($page)
			->find_array();
			
        $ratings = [];
        if (count($e) > 0){
            foreach($e as $sor){
                extract($sor);
                $ratings[$ratingid] = [];
                if (!$lang){
                    $ratings[$ratingid]['title'] = json_decode($title,true);
                } else {
                    $ratings[$ratingid]['title'] = json_decode($title,true);
                    if (isset($ratings[$ratingid]['title'][$lang])){
                        $ratings[$ratingid]['title'] = $ratings[$ratingid]['title'][$lang];
                    } else {
                        $ratings[$ratingid]['title'] = $ratings[$ratingid]['title']['en'];
                    }
                }
                $ratings[$ratingid]['rating']	= $rating;
                $ratings[$ratingid]['date']		= $ratingdate;
                $ratings[$ratingid]['ip']		= $ip;
                $ratings[$ratingid]['movieid']	= $movieid;
            }
        }
        return $ratings;
    }

    public function deleteRating($id){
		$e = ORM::for_table('movie_ratings')->find_one($id)->delete();
    }
   
    public function validate($params, $update = false, $no_embeds = false){       
        $errors = [];
        
        if (!isset($_SESSION['global_languages'])){
            return array("99" => "Session expired");
        }
        
        foreach ($_SESSION['global_languages'] as $lang_code => $lang_name){
            if (!isset($params['title'][$lang_code]) || !$params['title'][$lang_code]){
                $errors[1][$lang_code] = "Please enter the $lang_name title for this movie";
            }
            
            if (!isset($params['description'][$lang_code]) || !$params['description'][$lang_code]){
                $errors[3][$lang_code] = "Please enter the $lang_name description for this movie";
            }
        }
        
        if (!isset($params['thumb']) || !$params['thumb']){
            $errors[2]='Please upload a thumbnail image';
        }
        if (!isset($params['imdb_id']) || !$params['imdb_id']){
            $errors[4] = "Please enter the movie's IMDB id";
        } else if (!$update) { 
			$e = ORM::for_table('movies')->select('id')->where('imdb_id', $params['imdb_id'])->find_one();
            if ($e){
				$movie_id = $e->id;
                $errors[4] = "Movie with the same IMDB id already exists. <a href='index.php?menu=movies&movieid=$movie_id'>Click here to edit it</a>";
            }
        }

        if (isset($params['imdb_rating']) && $params['imdb_rating'] && !is_numeric($params['imdb_rating'])){
            $errors[6] = "Rating must be numeric";       
        }
        
        if (isset($params['year']) && $params['year'] && !is_numeric($params['year'])){
            $errors[7] = "Year of release must be numeric";       
        }
        
        // checking for real embeds
        if (!$no_embeds){
            if (isset($params['from']) && $params['from']=="admin"){
                if (!isset($params['embed_enabled']) || !is_array($params['embed_enabled']) || !count($params['embed_enabled'])){
                    $errors[5] = "Please add at least one embed";
                } else {
                    $found = false;
                    foreach($params['embed_enabled'] as $embed_id => $val){
                        if (isset($params['embeds'][$embed_id]) && $params['embeds'][$embed_id]){
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found){
                        $errors[5] = "Please add at least one embed";
                    }
                }
            } elseif (!isset($params['embeds']) || !is_array($params['embeds']) || !count($params['embeds'])){
                $errors[5] = "Please add at least one embed";
            }
        }
        
        return $errors;
    }

    public function getMovieCount($search_term = null){		
        if ($search_term){
            $total = ORM::for_table('movies')->where_raw('MATCH (title) AGAINST (? IN BOOLEAN MODE)', [$search_term])->count();
        } else {
            $total = ORM::for_table('movies')->count();
        }
        return $total;
    }
   
    public function getCounts($page, $lang=false, $limit = 100){
        $limit = (int) $limit;
        $start = ($page-1)*$limit;
		$e = ORM::for_table('movies')->select_many('movies.id', 'movies.title', 'movies.views', 'movies.thumb')->order_by_desc('views')->limit($limit)->offset($start)->find_array();
        $counts = [];
        if (count($e)){
            foreach($e as $sor){
                extract($sor);
                $counts[$id] = [];
                if (!$lang){
                    $counts[$id]['title'] = json_decode($title,true);
                } else {
                    $counts[$id]['title'] = json_decode($title,true);
                    if (isset($counts[$id]['title'][$lang])){
                        $counts[$id]['title'] = $counts[$id]['title'][$lang];
                    } else {
                        $counts[$id]['title'] = $counts[$id]['title']['en'];
                    }
                }
                
                $counts[$id]['views'] = $views;
                $counts[$id]['thumb'] = $thumb;
            }
        }
        return $counts;
    }

    public function deleteMovie($movie_id){
        global $basepath;
        
        $check = $e = ORM::for_table('movies')->find_one($movie_id);
        if ($check){
            $thumb = $e->thumb;
            if (file_exists($basepath."/thumbs/".$thumb)){
                unlink($basepath."/thumbs/".$thumb);
            }
			$check->delete();
        }
    }
   
    public function save($params){
       
		if (!isset($params['perma'])){
            $perma = $this->makePerma($params['title']['en']);
        } else {
            $perma = $params['perma'];
        }
		
        $title		 = json_encode($params['title']);
        $description = json_encode($params['description']);
        $thumb 		 = trim($params['thumb']);
        $imdb 		 = trim($params['imdb_id']);
        
        $meta = [];
        if (isset($params['year']) && $params['year']){
            $meta['year'] = $params['year'];
        }
        
        if (isset($params['director']) && $params['director']){
            $meta['director'] = $params['director'];
        }
		
		if (isset($params['duration']) && $params['duration']){
			$meta['duration'] = $params['duration'];
		}

		if (isset($params['content']) && $params['content']){
			$meta['content'] = $params['content'];
		}
		
		if (isset($params['published']) && $params['published']){
			$meta['published'] = $params['published'];
		} 

		if (isset($params['embed_vid']) && $params['embed_vid']){
			$meta['embed_vid'] = $params['embed_vid'];
		}
        
        if (isset($params['stars']) && $params['stars'] && is_array($params['stars']) && count($params['stars'])){
            $stars = [];
            foreach($params['stars'] as $key => $star){
                if (trim($star)){
                    $stars[] = $star;
                }
            }
            if (count($stars)){
                $meta['stars'] = $stars;
            }
        }
		
		if (isset($params['countries']) && $params['countries'] && is_array($params['countries']) && count($params['countries'])){
			$countries = [];
			foreach($params['countries'] as $key => $country){
				if (trim($country)){
					$countries[] = $country;
				}
			}
			if (count($countries)){
				$meta['countries'] = $countries;
			}
		}

		if (isset($params['keywords']) && $params['keywords'] && is_array($params['keywords']) && count($params['keywords'])){
			$keywords = [];
			foreach($params['keywords'] as $key => $keyword){
				if (trim($keyword)){
					$keywords[] = $keyword;
				}
			}
			if (count($keywords)){
				$meta['keywords'] = $keywords;
			}
		}  
        
        if (count($meta)){
            $meta = json_encode($meta);
        } else {
            $meta = '';
        }
        
        if (isset($params['imdb_rating']) && $params['imdb_rating']){
            $imdb_rating = $params['imdb_rating'];
        } else {
            $imdb_rating = 0;
        }
		
		$now = Carbon::now()->toDateTimeString();
		
		$create = ORM::for_table('movies')->create();
		
		$create->title		 = $title;
		$create->description = $description;
		$create->thumb		 = $thumb;
		$create->perma		 = $perma;
		$create->date_added  = $now;
		$create->imdb_id	 = $imdb;
		$create->imdb_rating = $imdb_rating;
		$create->meta		 = $meta;
        $create->save();
		
		return $create->id;		
    }
	
    function makePerma($str, $replace = [], $delimiter='-') {
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }
    
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    
        return $clean;
    }
    
    public function updateMeta($id, $meta){
	
        if (isset($meta['imdb_rating'])){
            $imdb_rating = $meta['imdb_rating'];
        } else {
            $imdb_rating = 0;
        }
		
        $meta = json_encode($meta);
        $meta = $meta;
        $id   = $id;
        $up   = ORM::for_table('movies')->find_one($id);
		$up->set(['meta' => $meta, 'imdb_rating'  => $imdb_rating]);
		$up->save();
    }
    
    public function updateThumbnail($movie_id, $thumbnail){
		$up   = ORM::for_table('movies')->find_one($movie_id);
		$up->set(['thumb' => $thumbnail]);
		$up->save();
    }
    
    public function update($id, $params){
        
        $perma 		  = $this->makePerma($params['title']['en']);
        $title 		  = json_encode($params['title']);
        $description  = json_encode($params['description']);
        $thumb 		  = $params['thumb'];
        $id 		  = $id;
		
        
        $meta = [];
        if (isset($params['year']) && $params['year']){
            $meta['year'] = $params['year'];
        }
        
        if (isset($params['director']) && $params['director']){
            $meta['director'] = $params['director'];
        }
        
        		if (isset($params['duration']) && $params['duration']){
			$meta['duration'] = $params['duration'];
		}

		if (isset($params['content']) && $params['content']){
			$meta['content'] = $params['content'];
		}
		
		if (isset($params['published']) && $params['published']){
			$meta['published'] = $params['published'];
		}  
        
		if (isset($params['embed_vid']) && $params['embed_vid']){
            $meta['embed_vid'] = $params['embed_vid'];
        }
			
        if (isset($params['stars']) && $params['stars'] && is_array($params['stars']) && count($params['stars'])){
            $stars = [];
            foreach($params['stars'] as $key => $star){
                if (trim($star)){
                    $stars[] = $star;
                }
            }
            if (count($stars)){
                $meta['stars'] = $stars;
            }
        }
		
		if (isset($params['countries']) && $params['countries'] && is_array($params['countries']) && count($params['countries'])){
			$countries = [];
			foreach($params['countries'] as $key => $country){
				if (trim($country)){
					$countries[] = $country;
				}
			}
			if (count($countries)){
				$meta['countries'] = $countries;
			}
		}

		if (isset($params['keywords']) && $params['keywords'] && is_array($params['keywords']) && count($params['keywords'])){
			$keywords = [];
			foreach($params['keywords'] as $key => $keyword){
				if (trim($keyword)){
					$keywords[] = $keyword;
				}
			}
			if (count($keywords)){
				$meta['keywords'] = $keywords;
			}
		}  
        
        if (count($meta)){
            $meta = json_encode($meta);
        } else {
            $meta = '';
        }
        
        if (isset($params['imdb_rating']) && $params['imdb_rating']){
            $imdb_rating = $params['imdb_rating'];
        } else {
            $imdb_rating = 0;
        }
        
		$now = Carbon::now()->toDateTimeString();
		
		$update = ORM::for_table('movies')->where('id', $id)->find_one();
		if(!$e){
			return false;		
		}else{
			$update->title = $title;
			$update->description = $description;
			$update->thumb = $thumb;
			$update->perma = $perma;
			$update->date_added = $now;
			$update->imdb_rating = $imdb_rating;
			$update->meta = $meta;
			$update->save();
			
			return true;	
		}        
    }

    public function searchByMeta($params, $lang=null){
		if (isset($params['director'])){
            $res = ORM::for_table('movies')->where_like('meta', '%'.$params['director'].'%')->find_array();
        } elseif (isset($params['star'])){
            $res = ORM::for_table('movies')->where_like('meta', '%'.$params['star'].'%')->find_array();
		} elseif (isset($params['country'])){
			$res = ORM::for_table('movies')->where_like('meta', '%'.$params['country'].'%')->find_array();
		} elseif (isset($params['keyword'])){
			$res = ORM::for_table('movies')->where_like('meta', '%'.$params['keyword'].'%')->find_array();
        } elseif (isset($params['year'])){
            $res = ORM::for_table('movies')->where_like('meta', '%'.$params['year'].'%')->find_array();        
        }
		
        $movies = [];      
        
        if (count($res) > 0){
            $ids = [];
            foreach($res as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){                
                $flags = $this->getFlags($ids);        
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
        }
        return $movies;
    }
    
    public function search($q, $lang = null, $page){
        
		$start = ($page-1)*20;
        $movies = [];
        $query = ORM::for_table('movies')->where_raw('MATCH (title, description) AGAINST (? IN BOOLEAN MODE)', [$q])->find_array();    
        if (count($e)>0){
            $ids = [];
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){                
                $flags = $this->getFlags($ids);        
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
        }
        return $movies;
    }
   
    public function getMovies($lang=false, $limit = 0, $page = 0){
        $movies = [];
        
        if ($limit){
            if ($page){
                $start = ($page-1)*$limit;
            } else {
                $start = 0;
            }
            $e = ORM::for_table('movies')->order_by_desc('title')->limit($limit)->offset($start)->find_array();           
        } else {
            $e = ORM::for_table('movies')->order_by_desc('title')->find_array();
        }
        if (count($e) > 0){
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
            }
        }
        return $movies;
    }
   
    public function getLatest($page, $lang=null){
		$start 	= ($page-1)*20;
        $movies = [];
		$e = ORM::for_table('movies')->order_by_desc('date_added')->limit(20)->offset($start)->find_array();
        if (count($e)>0){
            $ids = [];
            
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){
                
                $flags = $this->getFlags($ids);
                
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
        }
        return $movies;
    }
	
	public function getHomeLatest($limit, $lang=null){
        $movies = [];
		$e = ORM::for_table('movies')->order_by_desc('date_added')->limit($limit)->find_array();
        if (count($e)>0){
            $ids = [];
            
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){
                
                $flags = $this->getFlags($ids);
                
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
        }
        return $movies;
    }
   
    public function addView($id){
		$e = ORM::for_table('movies')->where('id', $id)->find_result_set()->set_expr('views', 'views + 1')->save();
    }
    
    public function getByImdb($imdb_id, $lang=null){
        $movie = false;
		$e = ORM::for_table('movies')->where('imdb_id', $imdb_id)->find_array();        
        if (count($e)==1){
            $movie = $this->formatMovieData($e[0], $lang);
        }
        return $movie;
    }
   
    public function getByPerma($perma, $lang=null){
        $movie = [];
        $e = ORM::for_table('movies')->where('perma', $perma)->find_array();
        if (count($e)==1){
            $movie = $this->formatMovieData($e[0], $lang);
        }
        return $movie;
    }
    
    public function getRealMovieCount(){
		$count = ORM::for_table('movies')->select_expr('COUNT(DISTINCT(movies.id)) AS mov_count')->join('movie_embeds', 'movie_embeds.movie_id = movies.id')->find_one();
        return $count->mov_count;
    }
   
    public function getFlags($ids){
        $flags = [];
        // getting embed languages
        if (count($ids)){
			$e = ORM::for_table('movie_embeds')->select_many('movie_id', 'lang')->where_id_in($ids)->find_array();
            if (count($e) > 0){
                foreach($e as $s){
                    if (!array_key_exists($s['movie_id'], $flags)){
                        $flags[$s['movie_id']] = [];
                    }
                    
                    if (!in_array($s['lang'],$flags[$s['movie_id']])){
                        $flags[$s['movie_id']][] = $s['lang'];
                    }
                }
            }
        }        
        return $flags;
    }
    
    public function getRealMovies($lang=null, $p=null, $l=null, $sortby=null){
        $movies = [];
            
        $start = ($p-1)*$l;
        
        if (!$sortby || $sortby=='abc'){
            $e = ORM::for_table('movies')
			->join('movie_embeds', 'movies.id = movie_embeds.movie_id')
			->select('movies.*')
			->group_by('movies.id')
			->order_by_asc('movies.title')
			->limit($l)->offset($start)			
			->find_array();
        } elseif ($sortby=='date'){
             $e = ORM::for_table('movies')
			->join('movie_embeds', 'movies.id = movie_embeds.movie_id')
			->select('movies.*')
			->group_by('movies.id')
			->order_by_desc('movies.date_added')
			->limit($l)->offset($start)			
			->find_array();
        } elseif ($sortby=='imdb_rating'){
            $e = ORM::for_table('movies')
			->join('movie_embeds', 'movies.id = movie_embeds.movie_id')
			->select('movies.*')
			->group_by('movies.id')
			->order_by_desc('movies.imdb_rating')
			->limit($l)->offset($start)			
			->find_array();
        }else{
			 $e = ORM::for_table('movies')
			->join('movie_embeds', 'movies.id = movie_embeds.movie_id')
			->select('movies.*')
			->group_by('movies.id')
			->order_by_asc('movies.id')
			->limit($l)->offset($start)			
			->find_array();
		}
        
        if (count($e)>0){
            
            $ids = [];
            
            foreach($e as $s){
                $movies[$s['id']] = $this->formatMovieData($s, $lang);
                $ids[] = $s['id'];
            }
            
            if (count($ids)){
                
                $flags = $this->getFlags($ids);
                
                if (count($flags)){
                    foreach($movies as $movie_id => $val){
                        if (array_key_exists($movie_id,$flags)){
                            $movies[$movie_id]['languages'] = $flags[$movie_id];
                        } else {
                            $movies[$movie_id]['languages'] = [];
                        }    
                    }
                }
            }
            
        }
        return $movies;
    }
    
    public function setDate($movie_id){
        $now = Carbon::now()->toDateTimeString();
		
        $up = ORM::for_table('movies')->find_one($movie_id);
		$up->set('date_added', $now);
		$up->save();
    }
    
    public function saveEmbed($movie_id, $embed_code, $lang="ENG", $weight=10, $link=''){
        $embed_code = urldecode($embed_code);
        $check = ORM::for_table('movie_embeds')->where('link', $link)->find_one();		
		if (!$check){
			$e = ORM::for_table('movie_embeds')->create();
			$e->movie_id = $movie_id;
			$e->embed 	 = $embed_code;
			$e->lang 	 = $lang;
			$e->weight   = $weight;
			$e->link 	 = $link;
			$e->save();
			return $e->id;
		}
    }
    
    public function formatMovieData($data, $lang = false){
        if (!$lang){
            $data['title'] 		 = json_decode($data['title'],true);
            $data['description'] = json_decode($data['description'],true);
        } else {
            $data['title'] = json_decode($data['title'],true);
            $data['title'] = $data['title'][$lang];
            
            $data['description'] = json_decode($data['description'],true);
            $data['description'] = $data['description'][$lang];
        }

        $data['meta'] = json_decode($data['meta'],true);
        
        return $data;
    }
    
    public function getMovie($id, $lang=false){
        $movie = [];
		$e = ORM::for_table('movies')->where('id', $id)->find_array();
        if (count($e) == 1){
            return $this->formatMovieData($e[0], $lang);
        }
        return $movie;
    }

    public function reportMovie($movieid, $problem, $ip, $user_agent = ''){
        global $session;
        if ($session->has('loggeduser_id')){
            $user_id = $session->get('loggeduser_id');
        } else {
            return $false;
        }
        
		$check = ORM::for_table('broken_movies')->select('id')->where('ip', $ip)->where('movieid', $broken_movies)->find_one();
        if (!$check){
            $today = Carbon::now()->toDateTimeString();
			
			$e = ORM::for_table('broken_movies')->create();
			$e->movieid 	= $movieid;
			$e->reportdate 	= $today;
			$e->problem 	= $problem;
			$e->ip 			= $ip;
			$e->user_id 	= $user_id;
			$e->user_agent 	= $user_agent;
			$e->save();
			return true;
		}else{
			return false;
		}
    }
   
    public function getBroken($page, $lang=false){
        $start = ($page-1)*100;
		$e = ORM::for_table('broken_movies')
		->select_many(['brokenid' => 'broken_movies.id'], ['date' => 'broken_movies.reportdate'], ['movieid' => 'movies.id'], 'broken_movies.user_agent', 'broken_movies.user_id', 'broken_movies.problem', 'movies.views', 'movies.perma', 'movies.imdb_id', 'movies.title', 'broken_movies.ip')
		->join('movies', 'movies.id = broken_movies.movieid')
		->order_by_desc('broken_movies.id')
		->limit(100)
		->offset($start)
		->find_array();
		
        $broken = [];
        if (count($e) > 0){
            $user_ids = [];
            $user_map = [];
            foreach($e as $sor){
                extract($sor);
                $broken[$sor['brokenid']] = $sor;
                if (!$lang){
                    $broken[$sor['brokenid']]['title'] = json_decode($broken[$sor['brokenid']]['title'],true); 
                } else {
                    $broken[$sor['brokenid']]['title'] = json_decode($broken[$sor['brokenid']]['title'],true);
                    if (isset($broken[$sor['brokenid']]['title'][$lang])){
                        $broken[$sor['brokenid']]['title'] = $broken[$sor['brokenid']]['title'][$lang];
                    } else {
                        $broken[$sor['brokenid']]['title'] = $broken[$sor['brokenid']]['title']['en'];
                    }
                }

                $broken[$sor['brokenid']]['url']='/watch/'.$perma;
                $broken[$sor['brokenid']]['user'] = [];
                
                if ($user_id && !in_array($user_id, $user_ids)){
                    $user_ids[] = $user_id;
                }
                
                if ($user_id){
                    if (!array_key_exists($user_id,$user_map)){
                        $user_map[$user_id] = [];
                    }
                    
                    $user_map[$user_id][] = $brokenid;
                }
            }
            
            if (count($user_ids)){
		    $e = ORM::for_table('users')->select_many(['user_id' => 'id' ], 'username', 'email')->where_id_in($user_ids)->find_array();
                if (count($e)){
                    foreach($e as $s){
                        
                        foreach($user_map[$s['user_id']] as $key => $val){
                            $broken[$val]['user'] = $s;    
                        }                        
                    }
                }
            }
        }
        
        return $broken;
    }
    
    public function getCommentCount(){
		$total = ORM::for_table('comments')->count();
        return $total;
    }
    
    public function getAllComments($page, $lang=false){
        global $baseurl;
        
        $start = ($page-1)*50;
		
        $e = ORM::for_table('comments')
		->select_many(['movieid' => 'movies.id'], ['movietitle' => 'movies.title'], 'comments.*', 'users.username', 'movies.perma')
		->where('comments.type', 2)
		->join('movies', 'movies.id = comments.target_id')
		->join('users', 'users.id = comments.user_id')
		->order_by_asc('comments.id')
		->limit(50)
		->offset($start)
		->find_array();
		
        $comments = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $comments[$id] = [];
                $comments[$id]['comment'] = stripslashes($comment);
                $comments[$id]['user_id'] = $user_id;
                $comments[$id]['username'] = $username;
                if (!$lang){
                    $comments[$id]['movietitle'] = json_decode($movietitle,true);
                } else {
                    $comments[$id]['movietitle'] = json_decode($movietitle,true);
                    if (isset($comments[$id]['movietitle'][$lang])){
                        $comments[$id]['movietitle'] = $comments[$id]['movietitle'][$lang];
                    } else {
                        $comments[$id]['movietitle'] = $comments[$id]['movietitle']['en'];
                    }
                }
                $comments[$id]['movieid'] 	 = $movieid;
                $comments[$id]['movielink']  = $baseurl."/watch/".$perma;
                $comments[$id]['date_added'] = $date_added;
            }
        }
        
        return $comments;
        
    }
         
    public function getBrokenCount(){
		$total = ORM::for_table('broken_movies')->count();        
        return $total;
    }
   
    public function getRating($movieid){
		$average  = ORM::for_table('movie_ratings')->where('movieid', $movieid)->avg('rating');        
        if(!$average){
			return 0;
		}else{
			return $average;
		}
    }

    public function deleteBroken($id){
		$e = ORM::for_table('broken_movies')->find_one($id)->delete();
    }

    public function addRating($movieid, $rating, $ip){
		$e 		= ORM::for_table('movie_ratings')->select('id')->where('movieid', $movieid)->where('ip', $ip)->find_one();
		$check 	= ORM::for_table('movies')->find_one($movieid);
		
        $today = Carbon::now()->toDateTimeString();
        if ($e && $check){
            $e->set(['rating' => $rating, 'ratingdate' => $today]);
			$e->save();
			return (int)$e->rating;
        } elseif(!$e && $check) {
			$e = ORM::for_table('movie_ratings')->create();
			$e->movieid 		= $movieid;
			$e->rating 			= $rating;
			$e->ip 				= $ip;
			$e->ratingdate  	= $today;
			$e->save();
			return (int)$e->rating;
        }else{
			return false;
		}
        
    }	
	
	public function addWatch($user_id, $movie_id, $target_type, $watch_date){
		$e 	= ORM::for_table('watches')->select('id')
		->where('target_id', $movie_id)
		->where('user_id', $user_id)
		->where('target_type', $target_type)
		->find_one();
        if ($e){
            return false;
        } else{
			$e = ORM::for_table('watches')->create();
			$e->user_id 	= $user_id;
			$e->target_id 	= $movie_id;
			$e->target_type = $target_type;
			$e->date_added  = $watch_date;
			$e->save();
			return true;
		}
        return false;
    }
	
	public function addLike($user_id, $movieid, $comment, $vote, $like_date){
		$e 	= ORM::for_table('likes')->select('id')->where('target_id', $movieid)->where('user_id', $user_id)->where('target_type', 1)->find_one();
        if ($e){
            return false;
        } else{
			$e = ORM::for_table('likes')->create();
			$e->user_id 	= $user_id;
			$e->target_id 	= $movieid;
			$e->target_type = 1;
			$e->comment  	= $comment;
			$e->vote  		= $vote;
			$e->date_added  = $like_date;
			$e->save();
			return true;
		}
        return false;
    }
	
	public function delLike($user_id, $movieid){
        $e  = ORM::for_table('likes')->where('user_id', $user_id)->where('target_id', $movieid)->where('target_type', 1)->find_one();
        if(!$e){            
            return false;
        }else{
            $e->delete();
            return true;
        }
    }
    
    public function getLikesCount($movie_id){
        $total  = ORM::for_table('likes')->where('target_id', $movie_id)->where('target_type', 1)->count();
        return $total;
    }
    
    public function getWatchesCount($movie_id){
        $total  = ORM::for_table('watches')->where('target_id', $movie_id)->where('target_type', 1)->count();
        return $total;
    }
    
    public function getRandomMovies($limit, $lang=false, $excluded_ids = []){
        $limit = (int) $limit;
        
        if (count($excluded_ids)){
            foreach($excluded_ids as $key => $val){
                $excluded_ids[$key] = $val;
            }
            
            $e = ORM::for_table('movies')->where_not_in('id', $excluded_ids)->order_by_expr('RAND(`id`)')->limit($limit)->find_array();
        } else {
		
            $e = ORM::for_table('movies')->order_by_expr('RAND(`id`)')->limit($limit)->find_array();
        }
        
        $res = [];
        if (count($e) > 0){
            foreach($e as $s){
                $res[$s['id']] = $this->formatMovieData($s, $lang);
            }
        }
        
        return $res;
    }
    
	function getSimilarMovies($movie_id, $lang = null) {
		$ids 	= [];
		$movies = [];
		$e = ORM::for_table('movie_tags_join')
		->table_alias('moviejoin1')
		->select('moviejoin1.movie_id', 'movie1')
		->select('moviejoin2.movie_id', 'movie2')
		->join('movie_tags_join', array('moviejoin1.tag_id', '=', 'moviejoin2.tag_id', 'moviejoin1.movie_id', '!=', 'moviejoin2.movie_id'), 'moviejoin2')
		->where('moviejoin1.movie_id', $movie_id)
		->order_by_expr('RAND()')
		->limit(6)
		->find_array();
		
		if(count($e) > 0){
			foreach($e as $s){
				extract($s);
				$ids[] = $movie2;
			}
		}
		
		if(count($ids) > 0){
			$res = ORM::for_table('movies')->where_id_in($ids)->find_array();
			if(count($res) > 0){
				foreach($res as $s){
					extract($s);
					$movies[$id] = $s;
					if (!$lang){
						$movies[$id]['title'] 		 = json_decode($movies[$id]['title'], true);
						$movies[$id]['description']  = json_decode($movies[$id]['description'], true);
					} else {
						$movies[$id]['title'] 		 = json_decode($movies[$id]['title'], true);
						$movies[$id]['title'] 		 = $movies[$id]['title'][$lang];
					
						$movies[$id]['description']  = json_decode($movies[$id]['description'], true);
						$movies[$id]['description']  = $movies[$id]['description'][$lang];
					}                
					$movies[$id]['meta']  		 	 = json_decode($movies[$id]['meta'], true);
				}
			}
		}
		
		return $movies;
	}	
	
}
?>