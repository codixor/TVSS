<?php

class Show{
    
    public $episode_schema 	 = ["show_id","season","episode","title","description","date_added","thumbnail","views","checked"];
    public $embed_schema	 = ["episode_id","embed","link","lang","weight"];
    public $show_schema		 = ["title","description","thumbnail","permalink","sidereel_url","imdb_id","type","featured","imdb_rating","meta"];
    
    public function __construct(){
        
    }
    
    
    public function getList($page = null, $start = null, $limit = null, $sortby="id", $sortdir="DESC", $search_term = null){
        if (!$limit){
            $limit = 50;
        } 
                
        if ($page && !$search_term){
            $start = ($page-1)*$limit;
			$e = ORM::for_table('shows')->order_by_expr($sortby, $sortdir)->limit($start, $limit)->find_array();
        } elseif ($page && $search_term){
            $start = ($page-1)*$limit;
            $e = ORM::for_table('shows')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($start && !$search_term) {            
            $e = ORM::for_table('shows')->order_by_expr($sortby.' '.$sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($start && !$search_term) {            
            $e = ORM::for_table('shows')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_array();
        } elseif ($search_term) {
            $e = ORM::for_table('shows')->where_like('title', '%'.$search_term.'%')->order_by_expr($sortby, $sortdir)->find_array();
        } else{
            $e = ORM::for_table('shows')->order_by_expr($sortby.' '.$sortdir)->limit($limit)->find_array();
        }       
        $shows = [];
        $ids = [];
        if (count($e) > 0 ){
            foreach($e as $s){
                extract($s);
                $shows[$id]=$s;
                $shows[$id]['meta'] 		 = json_decode($shows[$id]['meta'],true);
                $shows[$id]['title'] 		 = json_decode($shows[$id]['title'],true);
                $shows[$id]['description'] 	 = json_decode($shows[$id]['description'],true);
                $shows[$id]['episode_count'] = 0;
                $ids[] = $id;                
            }		
            $e = ORM::for_table('episodes')->select_expr('COUNT(*)', 'episode_count')->select('show_id')->where_in('show_id', $ids)->group_by('show_id')->find_array();
            if (count($e) > 0 ){
                foreach($e as $s){
                    $shows[$s['show_id']]['episode_count'] = $s['episode_count'];
                }
            }
        }
        return $shows;
    }
    
    /* Returns all the possible embed languages from the database */
    public function getEmbedLanguages(){
		global $session;
        if ($session->has('embed_languages')){
            return $session->get('embed_languages');
        } else {
            $res = [];
			ORM::for_table('embeds')->distinct('lang')->where('vote', 1)->find_array();
            if (count($e) > 0 ){
                foreach($e as $s){
                    $res[] = $s['lang'];
                }
            }
            
            $session->set('embed_languages', $res);
            return $res;
        }
    }
    
    /* Returns the newest episodes, don't care about if it has embed or not */
	public function getNewestEpisodes($date_from, $limit=20){
		$date_begin = $this->dt->format('Y-m-d H:i:s', $date_from);
        $res = [];        
        $e = ORM::for_table('episodes')
				->select_many('episodes.id', 'episodes.season', 'episodes.episode', 'episodes.thumbnail', 'shows.title', 'shows.permalink')
				->join('shows', 'episodes.show_id = shows.id')				
				->where_any_is([['episodes.date_added' => $date_from]], '>=')				
				->order_by_expr('id', 'desc')
				->limit($limit)
				->find_array();
        if (count($e) > 0 ){
            foreach($e as $s){
                $res[$s['id']] = $s;
            }
        }
        
        return $res;
    }
    
    /* Method to add an embed code to an episode, doesn't add it if it's already there */
    public function addEmbed($episode_id, $embed, $lang='ENG', $link='', $weight=0){
	
        $embed = stripslashes(stripslashes(urldecode($embed)));
        
		$check = ORM::for_table('embeds')->where('embed', $embed)->where('episode_id', $episode_id)->find_one();
        if (!$check){
			$insert 		  	= ORM::for_table('embeds')->create();
			$insert->episode_id = $episode_id;
			$insert->embed 		= $embed;
			$insert->link 		= $link;
			$insert->lang 		= $lang;
			$insert->weight 	= $weight;
			$insert->save();
            return $insert->id;
        } else {
            return false;
        }
    }
    
    /* Removes all embeds of an episode */
    public function deleteEmbed($episode_id, $embed_id){
		$del = ORM::for_table('embeds')->where_equal('episode_id', $episode_id)->where_equal('id', $embed_id)->delete();
    }
    
    /* Updates the thumbnail for the episode */
    public function updateEpisodeThumbnail($episode_id, $thumb_file){
		$up = ORM::for_table('episodes')->find_one($episode_id);
		if($up){
			$up->set(['thumbnail' => $thumb_file])->save();
		}
    }
    
    /* Updates the show and the episode date */
    public function setEpisodeDate($episode_id, $show_id){
        $episode_id = (int) $episode_id;
        $show_id = (int) $show_id;
        
		$up = ORM::for_table('episodes')->find_one($episode_id);
		if($up){
			$up->set(['date_added' => Carbon::now()->toDateTimeString()])->save();
		}
		$up = ORM::for_table('shows')->find_one($show_id);
		if($up){
			$up->set(['last_episode' => Carbon::now()->toDateTimeString()])->save();
		}
    }
    
    /* List shows based on views */
    public function getPopularShows($lang=false, $limit = 20){        
        $limit = (int) $limit;
        $e = ORM::for_table('episodes')
			->join('shows', 'episodes.show_id = shows.id')
			->select('episodes.show_id', 'showid' )
			->select('shows.title', 'showtitle')
			->select('shows.thumbnail')
			->select_expr('SUM(views)', 'views')
			->group_by('episodes.show_id')
			->order_by_expr('SUM(views)', 'desc')
			->limit($limit)
			->find_array();
        $topshows = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $topshows[$showid] = [];
                $topshows[$showid]['views'] = $views;
                $topshows[$showid]['thumbnail'] = $thumbnail;
                if (!$lang){
                    $topshows[$showid]['showtitle'] = json_decode($showtitle,true);
                } else {
                    $topshows[$showid]['showtitle'] = json_decode($showtitle,true);
                    $topshows[$showid]['showtitle'] = $topshows[$showid]['showtitle'][$lang];
                }
            }
        }
        return $topshows;
    }
    
    /* Updates an embed code */
    public function updateEmbedCode($episode_id, $embed_id, $embed_code){
        
        $embed_code = stripslashes(stripslashes(urldecode($embed_code)));
		
		$e = ORM::for_table('embeds')->where('episode_id', $episode_id)->where('embed_id', $embed_id)->find_one();
		if(!$e){
			$e->set(['embed' => $embed_code])->save();
		}
    }
    
    /* Returns a string representation of the embed's provider */
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
    
    /* Returns all the embeds for the given episode id */
    public function getEpisodeEmbeds($episode_id){
        $embeds = [];
        $counter = 0;
        $e = ORM::for_table('embeds')->where('episode_id', $episode_id)->order_by_desc('weight')->find_array();
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                if ($embed){                    
                    $embeds[$counter]['type'] = $this->getEmbedType($embed, $link);
                    $embeds[$counter]['embed']=stripslashes(stripslashes(urldecode($embed)));
                    $embeds[$counter]['link'] = $link;
                    $embeds[$counter]['id']=$id;
                    $embeds[$counter]['lang'] = $lang;
                    $embeds[$counter]['weight'] = $weight;
                    $counter++;
                }
            }
        }
        
        return $embeds;
    }
    
    /* Return COUNT embeds for the given episode id */
    public function getEpisodeCountEmbeds($episode_id){
        $e = ORM::for_table('embeds')->where('episode_id', $episode_id)->count();              
        return $e;
    }
    
    /* Returnss the list of episodes which hasn't been submitted to a given submit target */        
    public function getUnsubmitted($type){
        
		$e = ORM::for_table('tv_submits')->select('episode_id')->where('type', $type)->find_array();
        if (count($e) > 0){
			$tmp = [];
            foreach($e as $s){            
                extract($s);
                $tmp[]=$episode_id;
            }			
			$e = ORM::for_table('episodes')
			->join('shows', 'episodes.show_id = shows.id')			
			->select('shows.title', 'showtitle')
			->select('episodes.id')
			->select('episodes.season')
			->select('episodes.episode')
			->select('episodes.title')
			->select('episodes.description')
			->select('shows.permalink')
			->select('shows.sidereel_url')
			->where_not_in('episodes.id', $tmp)
			->find_array();
        } else {
            $e = ORM::for_table('episodes')
			->join('shows', 'episodes.show_id = shows.id')
			->select('shows.title', 'showtitle')
			->select('episodes.id')
			->select('episodes.season')
			->select('episodes.episode')
			->select('episodes.title')
			->select('episodes.description')
			->select('shows.permalink')
			->select('shows.sidereel_url')
			->find_array();
        }
        
        
        $episodes = [];
        if (count($e) > 0 ){
            foreach($e as $s){
                extract($s);
                $episodes[$id]=$s;
            }
        }
        
        return $episodes;
    }
    
    /* Get 3 dimensional array of the existing episodes from the database */
    public function getExistingEpisodes($showid){
		 $e = ORM::for_table('episodes')
			->join('embeds', 'episodes.id = embeds.episode_id')
			->select_expr('COUNT(embeds.episode_id)', 'embedcount')
			->select('episodes.season')
			->select('episodes.episode')
			->where('episodes.show_id', $showid)
			->group_by('embeds.episode_id')
			->find_array();
			
        $eps = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
				if($embedcount > 5){
					if (!array_key_exists($season,$eps)) $eps[$season] = [];
					$eps[$season][]=$episode;
				}
            }
        }
        return $eps;
    }
   
    /* Get 3 dimensional array of the existing episodes from the database */
    public function getNewExistingEpisodes($showid){
		 $e = ORM::for_table('episodes')
			->join('embeds', 'episodes.id = embeds.episode_id')
			->select_expr('COUNT(embeds.episode_id)', 'embedcount')
			->select('episodes.season')
			->select('episodes.episode')
			->where('episodes.show_id', $showid)
			->group_by('embeds.episode_id')
			->find_array();
			
        $eps = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
				if($embedcount < 5){
					if (!array_key_exists($season,$eps) ) $eps[$season] = [];
						$eps[$season][]=$episode;
					
				}
            }
        }
        return $eps;
    }
	
    /* Returns a list of shows organized by ABC */
    public function getAlphabet($lang=false){
		$e = ORM::for_table('shows')
		->join('episodes', 'shows.id = episodes.show_id')
		->select_expr('SUBSTRING(LOWER(shows.title),1,1)', 'letter')
		->where('shows.type', 1)
		->group_by('letter')
		->order_by_asc('letter')
		->find_array();
        $alphabet = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $alphabet[$letter] = [];
				$shows = ORM::for_table('shows')->select_many('id', 'permalink', 'thumbnail', 'title', 'description')
				->where('type', 1)
				->where_raw('LOWER(title) LIKE ?', [$letter.'%'])
				->find_array();
                if (count($shows) > 0){
                    foreach($shows as $sh){
                        extract($sh);
                        $alphabet[$letter][$id] = [];
                        $alphabet[$letter][$id]['perma'] = $permalink;
                        $alphabet[$letter][$id]['thumbnail'] = $thumbnail;
                        
                        if ($lang){
                            $alphabet[$letter][$id]['title'] = json_decode($title, true);
                            $alphabet[$letter][$id]['title'] = $alphabet[$letter][$id]['title'][$lang];
                            $alphabet[$letter][$id]['description'] = json_decode($description, true);
                            $alphabet[$letter][$id]['description'] = $alphabet[$letter][$id]['description'][$lang];                            
                        } else {
                            $alphabet[$letter][$id]['title'] = json_decode($title, true);
                            $alphabet[$letter][$id]['description'] = json_decode($description, true);
                        }
                    }
                }
            }
        }
        return $alphabet;
    }
    
    /* Returns the domain from an URL */
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
    
    /* Check if the given season/episode exists for the given show */
    public function exists($showid, $season, $episode){   
	
        $e = ORM::for_table('episodes')->select('id')->where('show_id', $showid)->where('season', $season)->where('episode', $episode)->find_one();
        if (!$e){
            return false;
        } else {
            return true;
        }
    }    
    
    public function getLink($link_id){		
		$res = ORM::for_table('submitted_links')
		->join('shows', 'shows.imdb_id = submitted_links.imdb_id')
		->where('submitted_links.type', 1)
		->where('submitted_links.id', $link_id)
		->find_array();
        if (count($res) > 0){
            return $res;
        } else {
            return false;
        }
    }
    
    /* Returns a list of user submitted links for the given episode */
    public function getLinks($status = null, $lang=false, $p, $l, $sortby=null){
		
		$start = ($p-1)*$l;

		if ($status && $sortby == 'status'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('shows', 'shows.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('shows.title', 'show_title')
			->select('shows.id', 'show_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->where('submitted_links.status', $status)
			->order_by_asc('status')
			->limit($l)
			->offset($start)
			->find_array();
        }elseif($status && $sortby == 'date'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('shows', 'shows.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('shows.title', 'show_title')
			->select('shows.id', 'show_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->where('submitted_links.status', $status)
			->order_by_desc('date_submitted')
			->limit($l)
			->offset($start)
			->find_array();			
		}elseif(!$status && $sortby == 'date'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('shows', 'shows.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('shows.title', 'show_title')
			->select('shows.id', 'show_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->order_by_desc('date_submitted')
			->limit($l)
			->offset($start)
			->find_array();
		}elseif(!$status && $sortby == 'status'){
			$e = ORM::for_table('submitted_links')
			->left_outer_join('shows', 'shows.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('shows.title', 'show_title')
			->select('shows.id', 'show_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->order_by_asc('status')
			->limit($l)
			->offset($start)
			->find_array();
		}else{
			$e = ORM::for_table('submitted_links')
			->left_outer_join('shows', 'shows.imdb_id = submitted_links.imdb_id')
			->left_outer_join('users', 'users.id = submitted_links.user_id')
			->select('submitted_links.*')
			->select('shows.title', 'show_title')
			->select('shows.id', 'show_id')
			->select('users.username')
			->where('submitted_links.type', 1)
			->order_by_asc('status')
			->limit($l)
			->offset($start)
			->find_array();
		}      
        
        $links = [];      
        
        if (count($e) > 0){
            foreach($e as $s){
                $links[$s['id']] = $s;                
                if ($s['show_title']){
                    $links[$s['id']]['show_title'] = json_decode($links[$s['id']]['show_title'],true);
                    if ($lang){
                        $links[$s['id']]['show_title'] = $links[$s['id']]['show_title'][$lang];                        
                    } 
                }
            }
        }

        return $links;
    }

    /* Removes a show */
    public function deleteShow($showid){
        global $basepath;        
        
		$e = ORM::for_table('shows')->select('thumbnail')->where('id', $showid)->find_one();
        if ($e){           
            if (isset($e->thumbnail) && file_exists($basepath."/thumbs/".$e->thumbnail)){
                if (file_exists($basepath."/thumbs/".$e->thumbnail)){
                    unlink($basepath."/thumbs/".$e->thumbnail);
                }
            }
            
			$e = ORM::for_table('episodes')->where('show_id', $showid)->delete_many();
			$e = ORM::for_table('shows')->find_one($showid)->delete();
        }
    }
    
    /* Removes an episode */
    public function deleteEpisode($episode_id){
        global $basepath;
        
        $e = ORM::for_table('episodes')->select('thumbnail')->where('id', $episode_id)->find_one();       
        if ($e){
            if ($e->thumbnail && file_exists($basepath."/thumbs/".$e->thumbnail)){
                unlink($basepath."/thumbs/".$e->thumbnail);
            }
			$e = ORM::for_table('episodes')->find_one($episode_id)->delete();           
        }        
    }

    /* Returns $limit number random shows (with episodes) */
    public function getRandomShow($limit, $lang = false, $excluded_ids = []){
        
        $limit = (int) $limit;
        
        if (count($excluded_ids) > 0){
			$e = ORM::for_table('shows')->join('episodes', 'episodes.show_id = shows.id')->select('shows.*')->where('shows.type', 1)->where_not_in('shows.id', $excluded_ids)->find_array(); 
        } else {
            $e = ORM::for_table('shows')->join('episodes', 'episodes.show_id = shows.id')->select('shows.*')->where('shows.type', 1)->find_array();
        }        
      
        $shows = [];
        if (count($e)>0){
            foreach($e as $s){
                $shows[$s['id']] = $this->formatShowData($s, false, $lang);
            }
        }
        return $shows;
    }
    
    /* Returns a list of featured shows */
    public function getFeatured($limit = 10, $lang = null){
        $shows = [];
		$e = ORM::for_table('shows')->where('type', 1)->where('featured', 1)->where_raw('id >= (select RAND()*MAX(id) FROM shows)')->limit($limit)->find_array();
        if (count($e) > 0 ){
			foreach($e as $s){
				$shows[$s['id']] = $this->formatShowData($s, false, $lang);		
			}
		}
		return $shows;    
    }
    
    /* Returns all the shows which have episodes */
    public function getAllShowsWithEpisodes($lang=null, $p=null, $l=null, $sortby=null){
        
		$start = ($p-1)*$l;
        if (!$sortby || $sortby == 'abc'){
			$e = ORM::for_table('shows')
			->join('episodes', 'shows.id = episodes.show_id')
			->select('shows.*')
			->where('shows.type', 1)
			->order_by_asc('title')
			->group_by('shows.id')
			->limit($l)->offset($start)			
			->find_array();
        } elseif ($sortby == 'date') {
			$e = ORM::for_table('shows')
			->join('episodes', 'shows.id = episodes.show_id')
			->select('shows.*')
			->where('shows.type', 1)
			->group_by('shows.id')
			->order_by_desc('last_episode')
			->limit($l)->offset($start)			
			->find_array();          
        } elseif ($sortby == 'imdb_rating'){
			$e = ORM::for_table('shows')
			->join('episodes', 'shows.id= episodes.show_id')
			->select('shows.*')
			->where('shows.type', 1)
			->group_by('shows.id')
			->order_by_desc('imdb_rating')
			->limit($l)->offset($start)			
			->find_array();                      
        }else{
			$e = ORM::for_table('shows')
			->join('episodes', 'episodes.show_id = shows.id')
			->select('shows.*')
			->where('shows.type', 1)
			->group_by('shows.id')
			->order_by_desc('shows.id')
			->limit($l)->offset($start)			
			->find_array();         
		}		
       
        $shows = [];
        if (count($e) > 0){
            foreach($e as $s){
                $shows[$s['id']] = $this->formatShowData($s, false, $lang);
            }
        }
        
        return $shows;
    }  
    
    /* Returns all the shows */
    public function getAllShows($p=null, $l=null, $lang = null){
        
        if ($p && $l){
			$start = ($p-1)*$l;
			$e = ORM::for_table('shows')->where('type', 1)->order_by_asc('title')->limit($l)->offset($start)->find_array();
        } else {
            $e = ORM::for_table('shows')->where('type', 1)->order_by_asc('title')->find_array();
        }        
        
        $shows = [];
        if (count($e) > 0){
            foreach($e as $s){
                $shows[$s['id']] = $this->formatShowData($s, false, $lang);
            }
        }
        return $shows;
    }  
    
    /* Returns the list of seasons for a given show */
    public function getSeasons($show_id){        
        
        $seasons = [];
		$e = ORM::for_table('episodes')->select_many('season', 'episode')->where('show_id', $show_id)->group_by('season')->order_by_asc('season')->find_array();        
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $seasons[] = $season;
            }
        }
        return $seasons;
    }
    
    /* Returns a show matching title in given language */
    public function getShowByTitle($title, $lang = "en"){
        $title = preg_replace("/[^a-zA-Z0-9 ]+/i","%",$title);
        
		$e = ORM::for_table('shows')->where('type', 1)->where_like('title', '%'.$title.'%')->find_array();
        
        if (count($e) == 1){
            return $this->formatShowData($e, false, $lang);
        } else {
            return [];
        }
    }
    
    public function getShowByImdb($imdb_id, $lang = "en"){
        
		$e = ORM::for_table('shows')->where('type', 1)->where('imdb_id', $imdb_id)->find_array();
         
        if (count($e) == 1){
            return $this->formatShowData($e, false, $lang);
        } else {
            return [];
        }
    }
    
    public function formatShowData($data, $nested = true, $lang = false){
        if ($nested){
            $data['meta'] = json_decode($data['meta'], true);
            
            $shows[$data['id']]=$data;
            
            if (!$lang){
                $shows[$data['id']]['title'] = json_decode($shows[$data['id']]['title'],true);
                $shows[$data['id']]['description'] = json_decode($shows[$data['id']]['description'],true);
            } else {
                $shows[$data['id']]['title'] = json_decode($shows[$data['id']]['title'],true);
                $shows[$data['id']]['title'] = $shows[$data['id']]['title'][$lang];
                
                $shows[$data['id']]['description'] = json_decode($shows[$data['id']]['description'],true);
                $shows[$data['id']]['description'] = $shows[$data['id']]['description'][$lang];
            }
            
            return $shows;
        } else {
            $data['meta'] = json_decode($data['meta'], true);
            
            if (!$lang){
                $data['title'] = json_decode($data['title'],true);
                $data['description'] = json_decode($data['description'],true);
            } else {
                $data['title'] = json_decode($data['title'],true);
                $data['title'] = $data['title'][$lang];
                
                $data['description'] = json_decode($data['description'],true);
                $data['description'] = $data['description'][$lang];
            }

            return $data;
        }
    }

    /* Returns show details matching permalink */
    public function getShowByPerma($perma, $lang=null){
        $e = ORM::for_table('shows')->where('type', 1)->where('permalink', $perma)->find_array();		
        if (count($e) == 1){
				return $this->formatShowData($e[0], true, $lang);
        } else {
            return [];
        }        
    }
    
    /* Validate a show */
    public function validate($params,$update=0){
        $errors = array();
        
        if (!isset($_SESSION['global_languages'])){
            return array("99" => "Session expired");
        } else {
            
            foreach($_SESSION['global_languages'] as $lang_code => $lang_name){
                if (!isset($params['title'][$lang_code]) || !$params['title'][$lang_code]){
                    $errors[1][$lang_code] = "Please enter the $lang_name title for this show";
                }
                
                if (!isset($params['description'][$lang_code]) || !$params['description'][$lang_code]){
                    $errors[3][$lang_code] = "Please enter the $lang_name description for this show";
                }
            }
        
            if (!isset($params['thumbnail']) || !$params['thumbnail']){
                $errors[2]='Please upload a default image';
            }

            if (isset($params['sidereel_url']) && $params['sidereel_url'] && !substr_count($params['sidereel_url'],"http://www.sidereel.com/")){
                $errors[4] = "Invalid Sidereel url. URL format must be http://www.sidereel.com/[SHOWNAME]";
            }
            
            if (isset($params['imdb_rating']) && $params['imdb_rating'] && !is_numeric($params['imdb_rating'])){
                $errors[7] = "IMDB rating must be numeric";          
            }
            
            if (isset($params['year_started']) && $params['year_started'] && !is_numeric($params['year_started'])){
                $errors[8] = "Year must be numeric";
            }
            
            if (!isset($params['imdb_id']) || substr_count($params['imdb_id'],"tt")==0){
                $errors[6] = "Invalid IMDB id. It should be in format: tt12345";
            } else {
                $imdb = mysql_real_escape_string($params['imdb_id']);
                if (!$update){
                    $check = mysql_query("SELECT * FROM shows WHERE imdb_id='$imdb'") or die(mysql_error());
                } else {
                    $update = mysql_real_escape_string($update);
                    $check = mysql_query("SELECT * FROM shows WHERE imdb_id='$imdb' AND id!='$update'") or die(mysql_error());
                }
                
                if (mysql_num_rows($check)){
                    $errors[6] = "This IMDB id is already in use";
                }
                
            }
            
            return $errors;
        }
    }
    
    /* Method to validate a multi language tag */    
    public function validateCategory($category){
		global $session;
        $errors = [];
        if ($session->has('global_languages') == NULL){
            return ["99" => "Session expired"];
        }
        
        foreach($session->get('global_languages') as $lang_code => $lang_name){
            if (!isset($category[$lang_code]) || !$category[$lang_code]){
                $errors[$lang_code] = "Please enter a $lang_name category title";
            }
        }
        
        return $errors;
    }
    
    /* Method to add a multi-language tag */    
    public function addCategory($category){        
        $perma = $this->makePerma($category['en']);

        $category = json_encode($category, JSON_UNESCAPED_UNICODE);
        
        $e = ORM::for_table('tv_tags')->where_any_is([['tag' => $category], ['perma' => $perma]])->find_one();
        if (!$e){
			$ins 		  = ORM::for_table('tv_tags')->create();
			$ins->tag     = $category;
			$ins->perma   = $perma;
			$ins->save();
            return 1;            
        } else {
            return 0;
        }
    }
    
    /* Add / update show categories */
    public function saveCategories($showid, $categories){
	
        $e = ORM::for_table('tv_tags_join')->where_equal('show_id', $showid)->delete_many();
		
        foreach($categories as $key => $category_id){
			$ins 		  = ORM::for_table('tv_tags_join')->create();
			$ins->show_id = $showid;
			$ins->tag_id  = $category_id;
			$ins->save();
        }
    }
    
    /* Remove a tag and all of it's members */
    public function deleteTag($tagid){
		$e = ORM::for_table('tv_tags')->find_one($tagid)->delete();
    }
    
    public function getShowCountByCategory($tag_id){
		$e = ORM::for_table('shows')->join('tv_tags_join', 'tv_tags_join.show_id = shows.id')->where('tv_tags_join.tag_id', (int)$tag_id)->count();
		return $e;
    }
    
    /* Returns all the shows matching a tag */
    public function getShowsByCategory($tag_id, $lang = null, $page = 1, $limit = 40, $sortby = "date"){
        
        $page = (int) $page;
        $limit = (int) $limit;
        
        if (!$page) $page = 1;
        if (!$limit) $limit = 1;
        
        $start = ($page-1) * $limit;
        
        if (!$sortby || $sortby == 'abc'){
			$e = ORM::for_table('shows')			
			->join('tv_tags_join', 'tv_tags_join.show_id = shows.id')
			->where('tv_tags_join.tag_id', $tag_id)	
			->order_by_asc('shows.title')
			->limit($limit)
			->offset($start)			
			->find_array();             
        } elseif ($sortby == 'date') {
			$e = ORM::for_table('shows')
			->join('tv_tags_join', 'tv_tags_join.show_id = shows.id')
			->where('tv_tags_join.tag_id', $tag_id)	
			->order_by_desc('shows.last_episode')
			->limit($limit)
			->offset($start)			
			->find_array();
        } elseif ($sortby == 'imdb_rating'){
			$e = ORM::for_table('shows')
			->join('tv_tags_join', 'tv_tags_join.show_id = shows.id')
			->where('tv_tags_join.tag_id', $tag_id)	
			->order_by_desc('shows.imdb_rating')
			->limit($limit)
			->offset($start)			
			->find_array();
        }
        
        $shows = [];
        if (count($e) > 0){
            foreach($e as $s){
                $shows[$s['id']] = $this->formatShowData($s, false, $lang);
            }
        }
        return $shows;
    }
    
	/* Returns a category matching a permalink */
    public function getCategoryByPerma($perma, $lang = null){
		$e = ORM::for_table('tv_tags')->where('perma', $perma)->find_one();
        if (!$e){
			return '';           
        } else {            
            $tag = json_decode($e->tag, true);
            if ($lang){
                $tag = $tag[$lang];
            }            
            return ["id" => $e->id, "tag" => $tag];
        }
    }
	
    /* Method to retrieve all the tags for a given show */
    public function getShowCategories($show_id, $get_info = false, $lang = false){

        $tags = [];
        
        if (!$get_info){
			$e = ORM::for_table('tv_tags_join')->where('show_id', $show_id)->find_array();                      
            if (count($e) > 0){
                foreach($e as $s){
                    extract($s);
                    $tags[]=$tag_id;
                }
            }        
        } else {
			$e = ORM::for_table('tv_tags')->join('tv_tags_join', 'tv_tags.id = tv_tags_join.tag_id')->where('tv_tags_join.show_id', $show_id)->find_array();            
            if (count($e) > 0){
                foreach($e as $s){
                    $tags[$s['id']] = $s;
                    $tags[$s['id']]['tag'] = json_decode($tags[$s['id']]['tag'], true);
                    
                    if ($lang){                        
                        if (isset($tags[$s['id']]['tag'][$lang])){
                            $tags[$s['id']]['tag'] = $tags[$s['id']]['tag'][$lang]; 
                        } elseif (isset($tags[$s['id']]['tag']['en'])) {
                            $tags[$s['id']]['tag'] = $tags[$s['id']]['tag']['en'];
                        }
                    }
                }
            }
        }
        return $tags;
    }
    
    /* Lists all the categories */
    public function getCategories($lang = null, $limit = null, $order="tag ASC"){
        $categories = [];
        
        if ($limit){ 
			$e = ORM::for_table('tv_tags')->order_by_asc('tag')->limit($limit)->find_array();
		} else {
			$e = ORM::for_table('tv_tags')->order_by_asc('tag')->find_array();
		}
        
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                
                $tag = json_decode(utf8_encode($tag),true);
                if ($lang){
                    $tag = $tag[$lang];
                }
                
                $categories[$id]['name']=$tag;
                $categories[$id]['perma']=$perma;
            }
        }
        
        return $categories;
    }
    
    /* Returns $limit latest episodes */ 
    public function getLatestEpisodes($limit){
		$maxepisodes = ORM::for_table('episodes')->select_expr('MAX(id) as maxid')->group_by('show_id')->find_array();
        $episodes = [];
        
        if (count($maxepisodes) > 0){
            
            $maxids = [];
            foreach($maxepisodes as $s){
                $maxids[]=$s['maxid'];
            }
			
            $e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['episodetitle' => 'episodes.title'], ['showtitle' => 'shows.title'], ['epid' => 'episodes.id'], 
			['category_thumbnail' => 'shows.thumbnail'], ['showid' => 'shows.id'], 'episodes.description', 'episodes.season', 
			'episodes.episode', 'episodes.date_added', 'episodes.thumbnail', 'shows.permalink')
			->where_in('episodes.id', $maxids)
			->group_by_expr('shows.id,episodes.season')
			->order_by_desc('episodes.id')
			->limit($limit)
			->find_array();          
            
            if (count($e) > 0){
                foreach($e as $s){
                    extract($s);
                    $episodes[$showid] = [];
                    $episodes[$showid]['showtitle']	=$showtitle;
                    $episodes[$showid]['showid']	=$showid;
					
                    if ($thumbnail){
                        $episodes[$showid]['thumbnail'] = $thumbnail;
                    } else {
                        $episodes[$showid]['thumbnail'] = $category_thumbnail;
                    }
                    $episodes[$showid]['episode']		=$episode;
                    $episodes[$showid]['season']		=$season;
                    $episodes[$showid]['description']	=$description;
                    $episodes[$showid]['permalink']		=$permalink;
                    $episodes[$showid]['episodetitle']	=$episodetitle;
					
                    if (!$episodes[$showid]['episodetitle']){
                        $episodes[$showid]['episodetitle'] = "Season $season, Episode $episode";
                    }
                    
                }
            }
        }
        return $episodes;
    }
    
    /* Get all the language flags for a given list of episode ids */    
    public function getEpisodeFlags($ids){
        $flags = [];
        // getting embed languages
        if (count($ids)){
			$e = ORM::for_table('embeds')->select_many('episode_id', 'lang')->where_in('episode_id', $ids)->find_array();
            if (count($e) > 0){
                foreach($e as $s){
                    if (!array_key_exists($s['episode_id'], $flags)){
                        $flags[$s['episode_id']] = [];
                    }
                    
                    if (!in_array($s['lang'], $flags[$s['episode_id']])){
                        $flags[$s['episode_id']][] = $s['lang'];
                    }
                }
            }
        }
        
        return $flags;
    }
    
    /* Method to return episodes where we have embed codes */    
    public function getRealLatestEpisodes($page, $lang = null){
		$start 	  = ($page-1)*20;
        $episodes = [];
        
		$e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['episodetitle' => 'episodes.title'], ['showtitle' => 'shows.title'], ['epid' => 'episodes.id'], 
			['category_thumbnail' => 'shows.thumbnail'], ['showid' => 'shows.id'], 'episodes.description', 'episodes.season', 
			'episodes.episode', 'episodes.date_added', 'episodes.thumbnail', 'shows.permalink', 'shows.imdb_rating')
			->order_by_desc('episodes.date_added')
			->limit(20)
			->offset($start)
			->find_array();

        $counter = 0;
        if (count($e) > 0){
            
            $ids = [];
            foreach($e as $s){
                extract($s);
                $episodes[$counter]=[];
                
                if (!$lang){
                    $episodes[$counter]['showtitle'] 	= json_decode($showtitle,true); 
					$episodes[$counter]['description']	= json_decode($description, true);
					$episodes[$counter]['episodetitle'] = json_decode($episodetitle, true);
                } else {
                    $showtitle 	  = json_decode($showtitle,true);
					$description  = json_decode($description, true);
					$episodetitle = json_decode($episodetitle, true);
                    $episodes[$counter]['showtitle']	= $showtitle[$lang];
					$episodes[$counter]['description']	= $description[$lang];
					$episodes[$counter]['episodetitle'] = $episodetitle[$lang];
                }
                
                
                $episodes[$counter]['showid']=$showid;
                $episodes[$counter]['epid']=$epid;
                if ($thumbnail){
                    $episodes[$counter]['thumbnail']= $thumbnail;
                } else {
                    $episodes[$counter]['thumbnail']= $category_thumbnail;
                }
                $episodes[$counter]['episode']			  = $episode;
                $episodes[$counter]['season']			  = $season;
                $episodes[$counter]['permalink']		  = $permalink;                
                $episodes[$counter]['date_added']		  = $date_added;
                $episodes[$counter]['category_thumbnail'] = $category_thumbnail;
                if (!$episodes[$counter]['episodetitle']){
                    $episodes[$counter]['episodetitle'] = 'Season '.$season.', Episode '.$episode;
                }
                $counter++;
                
                $ids[] = $epid;
                
            }
            
            // getting embed languages
            $flags = $this->getEpisodeFlags($ids);
            foreach($episodes as $key => $val){
                if (array_key_exists($val['epid'],$flags)){
                    $episodes[$key]['languages'] = $flags[$val['epid']];
                } else {
                    $episodes[$key]['languages'] = [];
                }
            }
            
        }
        return $episodes;
    }
	
	/* Method to return episodes where we have embed codes */    
    public function getHomeLatestEpisodes($limit, $lang = null){
        $episodes = [];
        
		$e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['episodetitle' => 'episodes.title'], ['showtitle' => 'shows.title'], ['epid' => 'episodes.id'], 
			['category_thumbnail' => 'shows.thumbnail'], ['showid' => 'shows.id'], 'episodes.description', 'episodes.season', 
			'episodes.episode', 'episodes.date_added', 'episodes.thumbnail', 'shows.permalink', 'shows.imdb_rating')
			->order_by_desc('episodes.date_added')
			->limit($limit)
			->find_array();

        $counter = 0;
        if (count($e) > 0){
            
            $ids = [];
            foreach($e as $s){
                extract($s);
                $episodes[$counter]=[];
                
                if (!$lang){
                    $episodes[$counter]['showtitle'] 	= json_decode($showtitle,true); 
					$episodes[$counter]['description']	= json_decode($description, true);
					$episodes[$counter]['episodetitle'] = json_decode($episodetitle, true);
                } else {
                    $showtitle 	  = json_decode($showtitle,true);
					$description  = json_decode($description, true);
					$episodetitle = json_decode($episodetitle, true);
                    $episodes[$counter]['showtitle']	= $showtitle[$lang];
					$episodes[$counter]['description']	= $description[$lang];
					$episodes[$counter]['episodetitle'] = $episodetitle[$lang];
                }
                
                
                $episodes[$counter]['showid']=$showid;
                $episodes[$counter]['epid']=$epid;
                if ($thumbnail){
                    $episodes[$counter]['thumbnail']= $thumbnail;
                } else {
                    $episodes[$counter]['thumbnail']= $category_thumbnail;
                }
                $episodes[$counter]['episode']			  = $episode;
                $episodes[$counter]['season']			  = $season;
                $episodes[$counter]['permalink']		  = $permalink;                
                $episodes[$counter]['date_added']		  = $date_added;
                $episodes[$counter]['category_thumbnail'] = $category_thumbnail;
                if (!$episodes[$counter]['episodetitle']){
                    $episodes[$counter]['episodetitle'] = 'Season '.$season.', Episode '.$episode;
                }
                $counter++;
                
                $ids[] = $epid;
                
            }
            
            // getting embed languages
            $flags = $this->getEpisodeFlags($ids);
            foreach($episodes as $key => $val){
                if (array_key_exists($val['epid'],$flags)){
                    $episodes[$key]['languages'] = $flags[$val['epid']];
                } else {
                    $episodes[$key]['languages'] = [];
                }
            }
            
        }
        return $episodes;
    }
    
    /* Global validate function for a new episode */    
    public function validateEpisode($params, $no_embeds = false){
        $errors = [];
        
        if (!isset($params['show_id']) || !$params['show_id'] || !is_numeric($params['show_id'])){
            $errors[1] = "Please select a TV show";    
        }
        
        if (!isset($params['season']) || !$params['season'] || !is_numeric($params['season'])){
            $errors[2] = "Please enter the season number";
        }
        
        if (!isset($params['episode']) || !$params['episode'] || !is_numeric($params['episode'])){
            $errors[3] = "Please enter the episode number";
        }
        
        // checking for real embeds
        if (!$no_embeds){
            if (isset($params['from']) && $params['from']=="admin"){
                if (!isset($params['embed_enabled']) || !is_array($params['embed_enabled']) || !count($params['embed_enabled'])){
                    $errors[4] = "Please add at least one embed";
                } else {
                    $found = false;
                    foreach($params['embed_enabled'] as $embed_id => $val){
                        if (isset($params['embeds'][$embed_id]) && $params['embeds'][$embed_id]){
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found){
                        $errors[4] = "Please add at least one embed";
                    }
                }
            } elseif (!isset($params['embeds']) || !is_array($params['embeds']) || !count($params['embeds'])){
                $errors[4] = "Please add at least one embed";
            }
        }
        
        return $errors;
    }
    
    /* Function to update episode data */
    public function updateEpisode($episode_id, $params){        
	
		$up = ORM::for_table('episodes')->find_one($episode_id);
		$up->title 		 = $params['title'];
		$up->description = $params['description'];
		$up->thumbnail 	 = $params['thumbnail'];
		$up->embed 		 = $params['embed'];
		$up->season 	 = $params['season'];
		$up->episode 	 = $params['episode'];
		$up->show_id 	 = $params['show_id'];
		$up->save();
    }
    
    /* Function to add a new episode with embed codes */    
    public function saveEpisode($params){
               
        if (!isset($params['title']) || !$params['title']){
            $params['title'] = "Season ".$params['season']." Episode ".$params['episode'];
        }else{
            $params['title'] = json_encode($params['title']);
        }
        
        $params['description'] = json_encode($params['description']);
        
        $episode_id = $this->getEpisode($params['show_id'],$params['season'],$params['episode']);
        if (!$episode_id){
            $ins = ORM::for_table('episodes')->create();
            $ins->title       = $params['title'];
            $ins->description = $params['description'];
            $ins->thumbnail   = $params['thumbnail'];
            $ins->embed       = $params['embed'];
            $ins->season      = $params['season'];
            $ins->episode     = $params['episode'];
            $ins->show_id     = $params['show_id'];
            $ins->date_added  = Carbon::now()->toDateTimeString();
            $ins->save();
            $episode_id = $ins->id;
            
        }
        
        if (isset($params['embeds']) && is_array($params['embeds']) && count($params['embeds'])){
            foreach($params['embeds'] as $key => $val){
                $embed = urldecode($val);
                if (isset($params['embed_langs'][$key])){
                    $language = $params['embed_langs'][$key];
                } else {
                    $language = "ENG";
                }
                
                $this->addEmbed($episode_id,$embed,$language);
            }
        }
        
        $this->setEpisodeDate($episode_id,$params['show_id']);
        
        return $episode_id;
    }

    /* Makes a clean, url friendly representation of a string */    
    public function makePerma($str, $replace=array(), $delimiter='-') {
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }
    
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    
        return $clean;
    }
    
    /* Saves a new show */
    public function save($params){
        
        $permalink = $this->makePerma($params['title']['en']);
        
        $title 		 = json_encode($params['title']);
        $description = json_encode($params['description']);
        $thumbnail 	 = $params['thumbnail'];
		
        if (isset($params['sidereel_url'])){
            $sidereel_url = $params['sidereel_url'];
        } else {
            $sidereel_url = '';
        }
        
		if (isset($params['show_status'])){
            $show_status = $params['show_status'];
        } else {
            $show_status = 'Undefined';
        }
		
        if (isset($params['featured']) && $params['featured']){
            $featured = 1;
        } else {
            $featured = 0;
        }
        
        if (isset($params['imdb_id'])){
            $imdb_id = $params['imdb_id'];
        } else {
            $imdb_id = "-1";
        }
        
        if (isset($params['imdb_rating']) && $params['imdb_rating']){
            $imdb_rating = $params['imdb_rating'];
        } else {
            $imdb_rating = 0;
        }
        
        $meta = [];
        
        if (isset($params['year_started']) && $params['year_started']){
            $meta['year_started'] = (int) $params['year_started'];
        }
		
		if (isset($params['duration']) && $params['duration']){
			$meta['duration'] = $params['duration'];
		}

		if (isset($params['prime_uri']) && $params['prime_uri']){
			$meta['prime_uri'] = $params['prime_uri'];
		}
		
		if (isset($params['iwatch_uri']) && $params['iwatch_uri']){
			$meta['iwatch_uri'] = $params['iwatch_uri'];
		}
		
		if (isset($params['published']) && $params['published']){
			$meta['published'] = $params['published'];
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
        
        if (isset($params['creators']) && $params['creators'] && is_array($params['creators']) && count($params['creators'])){
            $creators = [];
            foreach($params['creators'] as $key => $creator){
                if (trim($creator)){
                    $creators[] = $creator;
                }
            }
            if (count($creators)){
                $meta['creators'] = $creators;
            }
        }
        
        if (count($meta)){
            $meta = json_encode($meta);
        } else {
            $meta = '';
        }
		
		$insert = ORM::for_table('shows')->create();
		$insert->title 			= $title;
		$insert->imdb_id 		= $imdb_id;
		$insert->description 	= $description;
		$insert->thumbnail 	 	= $thumbnail;
		$insert->permalink 	 	= $permalink;
		$insert->type 			= 1;
		$insert->sidereel_url 	= $sidereel_url;
		$insert->featured 		= $featured;
		$insert->imdb_rating 	= $imdb_rating;
		$insert->meta 			= $meta;
		$insert->status			= $show_status;
		$insert->save();
        return $insert->id;			
        
    }
    
    /* Updates show_id with given parameters */
    public function update($params, $show_id){
        global $basepath;
        
		$check = ORM::for_table('shows')->select('id')->select('thumbnail', 'thumbnail_to_delete')->find_one($show_id);       
        if($check){   
            $title 		 = json_encode($params['title']);
            $description = json_encode($params['description']);
            $thumbnail 	 = $params['thumbnail'];
            
            if (isset($params['sidereel_url'])){
                $sidereel_url = $params['sidereel_url'];
            } else {
                $sidereel_url = '';
            }
            
            $imdb_id = $params['imdb_id'];
            
			if (isset($params['show_status'])){
                $show_status = $params['show_status'];
            } else {
                $show_status = 'Undefined';
            }
			
            if (isset($params['featured']) && $params['featured']){
                $featured = 1;
            } else {
                $featured = 0;
            }
            
            if (isset($params['imdb_rating']) && $params['imdb_rating']){
                $imdb_rating = $params['imdb_rating'];
            } else {
                $imdb_rating = 0;
            }
            
            $meta = [];
            
            if (isset($params['year_started']) && $params['year_started']){
                $meta['year_started'] = (int) $params['year_started'];
            }

			if (isset($params['prime_uri']) && $params['prime_uri']){
                $meta['prime_uri'] = $params['prime_uri'];
            }
			
			if (isset($params['iwatch_uri']) && $params['iwatch_uri']){
                $meta['iwatch_uri'] = $params['iwatch_uri'];
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
            
            if (isset($params['creators']) && $params['creators'] && is_array($params['creators']) && count($params['creators'])){
                $creators = [];
                foreach($params['creators'] as $key => $creator){
                    if (trim($creator)){
                        $creators[] = $creator;
                    }
                }
                if (count($creators)){
                    $meta['creators'] = $creators;
                }
            }
            
            if (count($meta)){
                $meta = json_encode($meta);
            } else {
                $meta = '';
            }
			
			if ($check->thumbnail_to_delete!=$thumbnail && file_exists($basepath."/thumbs/".$check->thumbnail_to_delete)){
                unlink($basepath."/thumbs/".$check->thumbnail_to_delete);
            }	
			
			$check->set('title', $title);
			$check->description  = $description;
			$check->thumbnail 	 = $thumbnail;
			$check->sidereel_url = $sidereel_url;
			$check->imdb_id 	 = $imdb_id;
			$check->featured 	 = $featured;
			$check->imdb_rating  = $imdb_rating;
			$check->meta 		 = $meta;
			$check->status 		 = $show_status;
			$check->save();   
			
            return true;
            
        } else {
            return false;
        }
    }   
    
    /* Returns a show based on id */
    public function getShow($id, $nested=1, $lang=false){
		$e = ORM::for_table('shows')->where('id', $id)->find_array();        
        if (count($e) > 0){
            return $this->formatShowData($e[0], $nested, $lang);
        } else {
            return [];
        }
    }   
    
    /* Returns all the shows having at least one episode */
    public function getShows($getcounts=null, $lang = false){
        $shows = [];
        
        if ($getcounts){
			$e = ORM::for_table('shows')
			->join('episodes', 'episodes.show_id = shows.id')
			->select_exp('COUNT(*) as cnt')
			->select('episodes.title', 'episodetitle')
			->where('shows.type', 1)
			->where('submitted_links.status', $status)
			->group_by('shows.id')
			->order_by_asc('shows.title')
			->find_array();            
        } else {
			$e = ORM::for_table('shows')->where('type', 1)->order_by_asc('title')->find_array();
        }
        
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $shows[$s['id']]=$s;
                if (!$lang){
                    $shows[$s['id']]['title'] 		= json_decode($shows[$s['id']]['title'],true);
                    $shows[$s['id']]['description'] = json_decode($shows[$s['id']]['description'],true);
                } else {
                    $shows[$s['id']]['title'] 		= json_decode($shows[$s['id']]['title'],true);
                    $shows[$s['id']]['title'] 		= $shows[$s['id']]['title'][$lang];
                    
                    $shows[$s['id']]['description'] = json_decode($shows[$s['id']]['description'],true);
                    $shows[$s['id']]['description'] = $shows[$s['id']]['description'][$lang];
                }
                
                
                if ($getcounts){
                    if ((!$episodetitle) && ($cnt==1)){					
						$getcount = ORM::for_table('episodes')->where('shows_id', $id)->count();
                    }
                    $shows[$id]['episodecount'] = $getcount;
                }
            }
            
        }
        return $shows;
    }
    
    /* Lists episodes belonging to a show / season */
    public function getEpisodes($showid, $season=null, $lang = false, $embed_lang = []){
        $episodes = [];

        if (is_numeric($season) && count($embed_lang) > 0){
			$e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->where('episodes.season', $season)
			->where_raw ('(episodes.id IN (SELECT episode_id FROM embeds WHERE lang IN (?))', $embed_lang)
			->order_by_desc('episodes.season')
			->order_by_desc('episodes.episode')
			->find_array();
        } elseif (is_numeric($season) && count($embed_lang) == 0) { 
            $e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->where('episodes.season', $season)
			->order_by_desc('episodes.season')
			->order_by_desc('episodes.episode')
			->find_array();
        }elseif (!is_numeric($season) && count($embed_lang) > 0) { 
			 $e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->where_raw ('(episodes.id IN (SELECT episode_id FROM embeds WHERE lang IN (?))', $embed_lang)
			->order_by_desc('episodes.season')
			->order_by_desc('episodes.episode')
			->find_array();
		}else{
			$e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->order_by_desc('episodes.season')
			->order_by_desc('episodes.episode')
			->find_array();
		}
        
        if (count($e) > 0){
            $ids = [];
            foreach($e as $s){
                extract($s);
                $episodes[$epid] = [];
                
                if (!$lang){
                    $episodes[$epid]['title'] 		 = json_decode($showtitle, true);
					$episodes[$epid]['episodetitle'] = json_decode($episodetitle, true);
					$episodes[$epid]['description']	 = json_decode($description, true);
                } else {
                    $episodes[$epid]['title'] = json_decode($showtitle,true);
					
                    if (isset($episodes[$epid]['title'][$lang])){
                        $episodes[$epid]['title'] = $episodes[$epid]['title'][$lang];
                    } else {
                        $episodes[$epid]['title'] = $episodes[$epid]['title']['en'];
                    }
					$episodes[$epid]['episodetitle'] = json_decode($episodetitle, true);
					$episodes[$epid]['episodetitle'] = $episodes[$epid]['episodetitle'][$lang];
					
					$episodes[$epid]['description']  = json_decode($description, true);
					$episodes[$epid]['description']  = $episodes[$epid]['description'][$lang];
                }
                
                if ($thumbnail){
                    $episodes[$epid]['thumbnail'] = $thumbnail;
                } else {
                    $episodes[$epid]['thumbnail'] = $category_thumbnail;
                }
                
                if (!$episodes[$epid]['episodetitle']){
                    $episodes[$epid]['episodetitle'] = "Season $season, Episode $episode";
                }                
                
                $episodes[$epid]['season']		= $season;
                $episodes[$epid]['episode']		= $episode;
                
                $episodes[$epid]['embed'] = $embed;
                
                $ids[] = $epid;
            }
            
            // getting embed languages
            if (count($ids)){
                $flags = $this->getEpisodeFlags($ids);
                
                $flags = $this->getEpisodeFlags($ids);
                foreach($episodes as $episode_id => $val){
                    if (array_key_exists($episode_id,$flags)){
                        $episodes[$episode_id]['languages'] = $flags[$episode_id];
                    } else {
                        $episodes[$episode_id]['languages'] = [];
                    }
                }
            }
        }
        return $episodes;
    }
    
    /* Returns all episodes from the database */
    public function getAllEpisodes(){
        $episodes = [];
        $e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->order_by_desc('episodes.season')
			->order_by_desc('episodes.episode')
			->find_array();
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $episodes[$epid] = [];
                $episodes[$epid]['title']		= $showtitle;
                $episodes[$epid]['description']	= $description;
                if ($thumbnail){
                    $episodes[$epid]['thumbnail'] = $thumbnail;
                } else {
                    $episodes[$epid]['thumbnail'] = $category_thumbnail;
                }
                $episodes[$epid]['season']		 = $season;
                $episodes[$epid]['episode']		 = $episode;
                $episodes[$epid]['episodetitle'] = $episodetitle;
                if (!$episodes[$epid]['episodetitle']){
                    $episodes[$epid]['episodetitle'] = "Season $season, Episode $episode";
                }
                $episodes[$epid]['embed'] = $embed;
            }
        }
        
        return $episodes;
    }
    
    /* Search by metadata */
    public function searchByMeta($params, $lang = null, $page = null){
	
		$start = ($page-1)*20;
		
        if (isset($params['creator'])){
            $res = ORM::for_table('shows')->where_like('meta', '%'.$params['director'].'%')
			->limit(20)
			->offset($start)
			->find_array();
        } elseif (isset($params['star'])){
            $res = ORM::for_table('shows')->where_like('meta', '%'.$params['star'].'%')
			->limit(20)
			->offset($start)
			->find_array();
		} elseif (isset($params['country'])){
			$res = ORM::for_table('shows')->where_like('meta', '%'.$params['country'].'%')
			->limit(20)
			->offset($start)
			->find_array();
		} elseif (isset($params['keyword'])){
			$res = ORM::for_table('shows')->where_like('meta', '%'.$params['keyword'].'%')
			->limit(20)
			->offset($start)
			->find_array();
        } elseif (isset($params['year_started'])){
            $res = ORM::for_table('shows')->where_like('meta', '%'.$params['year_started'].'%')
			->limit(20)
			->offset($start)
			->find_array();			
        }
        
        $shows = [];
     
        if (count($res) > 0){
            foreach($res as $s){
                $shows[$s['id']] = $this->formatShowData($s, false, $lang);
            }
        }
        return $shows;
    }
    
    /* Search function */
    public function search($q, $lang=null, $page = null){
		
		$start = ($page-1)*20;
        $episodes = [];
       
		$e = ORM::for_table('episodes')
		->join('shows', 'shows.id = episodes.show_id')
		->select_many(['show_id' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
		->where_raw('MATCH (episodes.title, episodes.description) AGAINST (? IN BOOLEAN MODE) OR MATCH (shows.title, shows.description) AGAINST (? IN BOOLEAN MODE)', [$q, $q])	
		->order_by_desc('episodes.season')
		->order_by_desc('episodes.episode')
		->limit(20)
		->offset($start)
		->find_array();
		
        if (count($e) > 0){
            
            $ids = [];
            foreach($e as $s){
                extract($s);
                $episodes[$epid] = [];
                
                if (!$lang){
                    $episodes[$epid]['title'] = json_decode($showtitle,true);
                } else {
                    $showtitle = json_decode($showtitle,true);
                    $episodes[$epid]['title'] = $showtitle[$lang];
                }
                
                $episodes[$epid]['title'] 		= stripslashes($episodes[$epid]['title']);
                $episodes[$epid]['description'] = nl2br(stripslashes($description));
                
                if ($thumbnail){
                    $episodes[$epid]['thumbnail'] = $thumbnail;
                } else {
                    $episodes[$epid]['thumbnail'] = $category_thumbnail;
                }
                
                $episodes[$epid]['show_id']			 = $show_id;
                $episodes[$epid]['season']			 = $season;
                $episodes[$epid]['episode']			 = $episode;
                $episodes[$epid]['permalink']		 = $permalink;
                $episodes[$epid]['episodetitle']	 = $episodetitle;
                if (!$episodes[$epid]['episodetitle']){
                    $episodes[$epid]['episodetitle'] = "Season $season, Episode $episode";
                }
                $episodes[$epid]['embed']=$embed;
                
                $ids[] = $epid;
            }
            
            if (count($ids)){
                $flags = $this->getEpisodeFlags($ids);
                foreach($episodes as $episode_id => $val){
                    if (array_key_exists($episode_id,$flags)){
                        $episodes[$episode_id]['languages'] = $flags[$episode_id];
                    } else {
                        $episodes[$episode_id]['languages'] = [];
                    }
                }
            }
        }
        return $episodes;
    }
    
    /* Returns the first day of the given month */
    public function firstOfMonth($date) {
        return date("Y-m-d", strtotime(date('m',strtotime($date)).'/01/'.date('Y',strtotime($date)).' 00:00:00'));
    }
    
    /* Returns the last day of the given month */
    public function lastOfMonth($date) {
        return date("Y-m-d", strtotime('-1 second',strtotime('+1 month',strtotime(date('m',strtotime($date)).'/01/'.date('Y',strtotime($date)).' 00:00:00'))));
    }
    
    /* Returns a from-to value for a period id */
    public function getPeriod($period){
        if ($period==1){ $from = date("Y-m-d"); $to = date("Y-m-d"); } // today
        if ($period==2){ $from = date("Y-m-d",strtotime("yesterday")); $to = date("Y-m-d",strtotime("yesterday")); } // yesterday
        if ($period==3){ $from = date("Y-m-d",strtotime("7 days ago")); $to = date("Y-m-d"); } // this week
        if ($period==4){ $from = date("Y-m")."-01"; $to = date("Y-m-d"); } // this month
        if ($period==5){ $from = "0000-00-00"; $to = date("Y-m-d"); } // all time
        if ($period==6){ $from = $this->firstOfMonth(date("Y-m-d",strtotime("1 month ago"))); $to = $this->lastOfMonth(date("Y-m-d",strtotime("1 month ago"))); } // last month
        if ($period==7){ $from = $this->firstOfMonth(date("Y-m-d",strtotime("2 month ago"))); $to = $this->lastOfMonth(date("Y-m-d",strtotime("2 month ago"))); } // two months ago
        
        return array("from" => $from, "to" => $to);
    }
    
    /* Returns all the episodes added in the given period */
    public function getByPeriod($period, $lang=false, $embed_lang = []){
        $period = $this->getPeriod($period);
        extract($period);
        
        $from .= " 00:00:00";
        $to .= " 23:59:59";
        
        if (count($embed_lang)){
            foreach($embed_lang as $key => $val){
                $embed_lang[$key] = "'".$val."'";
            }			
			$e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->where_gte('episodes.date_added', $from)
			->where_lte('episodes.date_added', $to)
			->where_raw ('(episodes.id IN (SELECT episode_id FROM embeds WHERE lang IN (?))', $embed_lang)
			->order_by_asc('episodes.id')
			->find_array();
        } else {
             $e = ORM::for_table('episodes')
			->join('shows', 'shows.id = episodes.show_id')
			->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['category_thumbnail' => 'shows.thumbnail'], ['epid' => 'episodes.id'], ['episodetitle' => 'episodes.title'], 
			'shows.permalink', 'episodes.season', 'episodes.episode', 'episodes.embed', 'episodes.thumbnail', 'episodes.description')
			->where('episodes.show_id', $showid)
			->order_by_asc('episodes.id')
			->find_array();
        }
		
        $episodes = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $episodes[$epid] = [];
                if (!$lang){
                    $episodes[$epid]['title'] 		= json_decode($showtitle,true);
                    $episodes[$epid]['description'] = json_decode($description,true);
                } else {
                    $episodes[$epid]['title'] 		= json_decode($showtitle,true);
                    if (isset($episodes[$epid]['title'][$lang])){
                        $episodes[$epid]['title'] 	= $episodes[$epid]['title'][$lang];
                    } else {
                        $episodes[$epid]['title'] 	= $episodes[$epid]['title']['en'];
                    }
                    
                    $episodes[$epid]['description'] = json_decode($description,true);
                    if (isset($episodes[$epid]['description'][$lang])){
                        $episodes[$epid]['description'] = $episodes[$epid]['description'][$lang];
                    } else {
                        $episodes[$epid]['description'] = $episodes[$epid]['description']['en'];
                    }                
                }
                $episodes[$epid]['thumbnail']		 = $thumbnail;
                $episodes[$epid]['season']			 = $season;
                $episodes[$epid]['episode']			 = $episode;
                $episodes[$epid]['episodetitle']	 = $episodetitle;
                if (!$episodes[$epid]['episodetitle']){
                    $episodes[$epid]['episodetitle'] = "Season $season, Episode $episode";
                }
                $episodes[$epid]['embed'] = $embed;
            }
        }
        return $episodes;
    }
       
    /* Gives a count of all the episodes in the db */
    public function getEpisodeCount(){
		$total = ORM::for_table('episodes')->count();        
        return $total;
    }
    
    /* Counts all the shows with at least one episode */
    public function getShowCountWithEpisodes(){
		$total = ORM::for_table('shows')->select_expr('COUNT(DISTINCT(shows.id)) AS shows_count')->join('episodes', 'shows.id = episodes.show_id')->find_one();
        return $total->shows_count;
    }    
	
	
	/* Counts all the shows links submitted */
    public function getLinksCount(){
		$total = ORM::for_table('submitted_links')->where('submitted_links.type', 1)->count();        
        return $total;
    }
	
	/* Counts all the pending links submitted */
    public function getLinksStatusCount($status){
        $total = ORM::for_table('submitted_links')->where('submitted_links.type', 1)->where('submitted_links.status', "$status")->count();
		return $total;
    }
    
    /* Total show count */
    public function getShowCount($search_term = null){
	
        if ($search_term){
            $total = ORM::for_table('shows')->select_expr('COUNT(*)', 'total')->where_raw('(`title` LIKE ?)', array('%'.$search_term.'%'))->count();
			return $total;
        } else {
            $total = ORM::for_table('shows')->select_expr('COUNT(*)', 'total')->count();
			return $total;
        }    
        
    }
   
    /* Number of episode ratings */
    public function getRatingCount(){
		$total = ORM::for_table('ratings')->count();       
        return $total;
    }
    
	 /* Number of broken episode reports */
    public function getBrokenCount(){
		$total = ORM::for_table('broken_episodes')->count();
        return $total;
    }
	
	/* Increments the view counter for given episode */
    public function addView($episodeid){
		$e = ORM::for_table('episodes')->where('id', $episodeid)->find_result_set()->set_expr('views', 'views + 1')->save();
    }
	
    /* Lists episodes based on views */
    public function getCounts($page, $lang=false){
        $start = ($page-1)*40;
		
		$e = ORM::for_table('episodes')
		->join('shows', 'shows.id = episodes.show_id')
		->select_many(['episodetitle' => 'episodes.title'], ['showtitle' => 'shows.title'], ['epid' => 'episodes.id'], 
		 ['showid' => 'shows.id'], 'episodes.season', 'episodes.views', 'episodes.episode', 'episodes.date_added', 
		 'shows.permalink')
		->order_by_desc('views')
		->limit(40)
		->offset($start)
		->find_array();           
		
        $counts = [];
        if (count($e) > 0){
            foreach($e as $sor){
                extract($sor);
                $counts[$epid] = [];
                $counts[$epid]['episodetitle']		= $episodetitle;
                if (!$counts[$epid]['episodetitle']){
                    $counts[$epid]['episodetitle'] 	= "Season $season, Episode $episode";
                }
                
                if (!$lang){
                    $counts[$epid]['showtitle'] = json_decode($showtitle,true);
                } else {
                    $counts[$epid]['showtitle'] = json_decode($showtitle,true);
                    $counts[$epid]['showtitle'] = $counts[$epid]['showtitle'][$lang];
                }
                $counts[$epid]['views']		= $views;
                $counts[$epid]['showid']	= $showid;
                $counts[$epid]['episode']	= $episode;
                $counts[$epid]['season']	= $season;
            }
        }
        return $counts;
    }
     
    /* Returns a list of broken episode reports */
    public function getBroken($page, $lang=false){
	
        $start = ($page-1)*50;
		
		$e = ORM::for_table('episodes')
		->join('shows', 'shows.id = episodes.show_id')
		->join('broken_episodes', 'broken_episodes.episodeid = episodes.id')
		->select_many(['brokenid' => 'broken_episodes.id'], ['showtitle' => 'shows.title'], ['epid' => 'episodes.id'], 
		['episodetitle' => 'episodes.title'], ['showid' => 'shows.id'], 'broken_episodes.user_id', 'broken_episodes.user_agent',
		'broken_episodes.problem', 'shows.permalink', 'episodes.views', 'episodes.season', 'episodes.episode', 
		'broken_episodes.reportdate', 'broken_episodes.ip')
		->order_by_desc('broken_episodes.id')
		->limit(50)
		->offset($start)
		->find_array();
		
        $broken = [];
        if (count($e) > 0){
            $user_ids = [];
            $user_map = [];
            foreach($e as $sor){
                extract($sor);
                $broken[$brokenid] = [];
                $broken[$brokenid]['episodetitle']=$episodetitle;
                if (!$broken[$brokenid]['episodetitle']){
                    $broken[$brokenid]['episodetitle'] = "Season $season, Episode $episode";
                }
                
                if (!$lang){
                    $broken[$brokenid]['showtitle'] = json_decode($showtitle,true);
                } else {
                    $broken[$brokenid]['showtitle'] = json_decode($showtitle,true);
                    $broken[$brokenid]['showtitle'] = $broken[$brokenid]['showtitle'][$lang];
                }
                $broken[$brokenid]['views']		 = $views;
                $broken[$brokenid]['episode']	 = $episode;
                $broken[$brokenid]['date']		 = $reportdate;
                $broken[$brokenid]['ip']		 = $ip;
                $broken[$brokenid]['season']	 = $season;
                $broken[$brokenid]['problem']	 = $problem;
                $broken[$brokenid]['episodeid']	 = $epid;
                $broken[$brokenid]['showid']	 = $showid;
                $broken[$brokenid]['user_agent'] = $user_agent;
                $broken[$brokenid]['url']		 = '/'.$permalink.'/season/'.$season.'/episode/'.$episode;
                $broken[$brokenid]['user'] 		 = [];
                
                if ($user_id && !in_array($user_id,$user_ids)){
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
				$e = ORM::for_table('users')->select_many(['user_id'  => 'id'], 'username', 'email')->where_id_in($user_ids)->find_array();               
                if (count($e) > 0){
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
    
    /* Removes a broken episode report */
    public function deleteBroken($id){
		$e = ORM::for_table('broken_episodes')->find_one($id)->delete();
    }

    /* Removes an episode rating */
    public function deleteRating($id){
		$e = ORM::for_table('ratings')->find_one($id)->delete();
    }

    /* Lists episode ratings */
    public function getRatings($page, $lang=false){
        $start = ($page-1)*40;
        $e = ORM::for_table('ratings')
            ->select_many(['ratingid' => 'ratings.id'], ['episodetitle' => 'episodes.title'], ['showtitle' => 'shows.title'], 'episodes.season', 'episodes.episode',
                'ratings.ip', 'ratings.ratingdate')
            ->join('episodes', 'episodes.id = ratings.episodeid')
            ->join('shows', 'shows.id = episodes.show_id')
            ->order_by_desc('ratingdate')
            ->limit(40)
            ->offset($start)
            ->find_array();

        $ratings = [];
        if (count($e) > 0){
            foreach($e as $sor){
                extract($sor);
                $ratings[$ratingid]                 = [];
                $ratings[$ratingid]['episodetitle'] =$episodetitle;
                if (!$ratings[$ratingid]['episodetitle']){
                    $ratings[$ratingid]['episodetitle'] = "Season $season, Episode $episode";
                }
                
                if (!$lang){
                    $ratings[$ratingid]['showtitle'] = json_decode($showtitle,true);
                } else {
                    $ratings[$ratingid]['showtitle'] = json_decode($showtitle,true);
                    $ratings[$ratingid]['showtitle'] = $ratings[$ratingid]['showtitle'][$lang];
                }
                $ratings[$ratingid]['rating']    = $rating;
                $ratings[$ratingid]['date']      = $ratingdate;
                $ratings[$ratingid]['ip']        = $ip;
                $ratings[$ratingid]['episodeid'] = $epid;
            }
        }
        return $ratings;
    }

    /* Marks an episode to be submitted to a given submit target */
    public function addSubmit($epid, $type, $link){
        $e = ORM::for_table('tv_submits')->select('id')->where('episode_id', $epid)->where('type', $type)->find_one();
        if (!$e){
            $today = Carbon::now()->toDateTimeString();
			
            $insert             = ORM::for_table('tv_submits')->create();
            $insert->episode_id = $epid;
            $insert->type       = $type;
            $insert->link       = $link;
            $insert->timestamp  = $today;
            $insert->save();
        }
    }

    /* Returns a list of all the submitted episodes to the given submit target */
    public function getAllSubmits($type){
        $submits = [];
        $e = ORM::for_table('tv_submits')->where('type', $type)->find_array();
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $submits[$episode_id] = [];
                $submits[$episode_id]['link']=$link;
                $submits[$episode_id]['timestamp']=$timestamp;
            }
        }
        return $submits;
    }
   
    /* Adds a broken episode report */
    public function reportEpisode($episode, $problem, $ip, $user_agent=''){
		global $session;
		
        if ($session->has('global_languages') && $session->has('loggeduser_id')){
            $user_id = $session->get('loggeduser_id');
        } else {
            return $false;
        }
        $e = ORM::for_table('broken_episodes')->select('id')->where('ip', $ip)->where('episodeid', $episodeid)->find_array();        
        if (!$e){
            $today = Carbon::now()->toDateTimeString();
			
            $insert             = ORM::for_table('broken_episodes')->create();
            $insert->episodeid  = $episode;
            $insert->reportdate = $today;
            $insert->problem    = $problem;
            $insert->ip         = $ip;
            $insert->user_id    = $user_id;
            $insert->user_agent = $user_agent;
            $insert->save();
			return true;
		}else{
			return false;
		}
    }
    
    /* Returns the average rating for the episode */
    public function getRating($episodeid){
		$average  = ORM::for_table('ratings')->where('episodeid', $episodeid)->avg('rating');
        if(!$average){
			return 0;
		}else{
			return $average;
		}
    }
    
    /* Adds an episode rating */
    public function addRating($episodeid, $rating, $ip){
		$e 			= ORM::for_table('ratings')->select('id')->where('episodeid', $episodeid)->where('ip', $ip)->find_one();
		$check_ep 	= ORM::for_table('episodes')->find_one($episodeid);
		
        $today = Carbon::now()->toDateTimeString();
        if (!$e && $check_ep){
			$e 		  		= ORM::for_table('ratings')->create();
			$e->episodeid 	= $episodeid;
			$e->rating 		= $rating;
			$e->ip 			= $ip;
			$e->ratingdate  = $today;
			$e->save();
			return (int)$e->rating;
        } elseif($e && $check_ep) {
			$e->set(['rating' => $rating]);
			$e->rating 		= $rating;
			$e->ratingdate  = $today;
			$e->save();
			return (int)$e->rating;
        }else{
			return false;
		}
    }
   
    /* Returns episode details */
    public function getEpisodeById($id, $lang=false){
		$e = ORM::for_table('episodes')
            ->select_many(['showid' => 'shows.id'], ['showtitle' => 'shows.title'], ['episodetitle' => 'episodes.title'], ['episode_thumbnail' => 'episodes.thumbnail'], 
			['epid' => 'episodes.id'], 'shows.sidereel_url', 'episodes.embed', 'shows.permalink', 'shows.thumbnail', 'episodes.episode', 'episodes.description', 'episodes.season')
            ->join('shows', 'shows.id = episodes.show_id')
            ->where('episodes.id', $id)            
            ->find_array();
        $ep = [];
        if (count($e) > 0){
            extract($e);
            $ep['title']		= $episodetitle;
            if ($episode_thumbnail){
                $ep['thumbnail'] = $episode_thumbnail;
            } else {
                $ep['thumbnail'] = $thumbnail;
            }
            $ep['season']	= $season;
            $ep['episode']	= $episode;
			
            if (!$episodetitle) $episodetitle = "Season $season, Episode $episode";
			
            $ep['episodetitle'] = $episodetitle;
			
            $ep['embed'] = $embed;
            if (!$lang){
                $ep['showtitle'] 	= json_decode($showtitle,true);
				$ep['description']	= json_decode($description, true);
            } else {
                $ep['showtitle'] 	= json_decode($showtitle,true);
                $ep['showtitle'] 	= $ep['showtitle'][$lang];
				$ep['description']	= json_decode($description, true);
				$ep['description'] 	= $ep['description'][$lang];
            }
            $ep['show_sidereel'] = $sidereel_url;
            $ep['showid']		 = $showid;
            $ep['show_perma']	 = $permalink;
            $ep['url']="/".$permalink."/season/".$season."/episode/".$episode;
        }
        return $ep;
    }
   
    /* Returns episode data based on show_id episode and season number */
    public function getEpisode($showid, $season, $episode, $lang = null){
		$e = ORM::for_table('episodes')->where('season', $season)->where('episode', $episode)->where('show_id', $showid)->find_array();
        if (count($e) == 1){
            extract($e[0]);            
            $ret = [];
            $ret['id'] = $id;
			if (!$lang){
				$ret['title'] 		 = json_decode($title, true);
				$ret['description']  = json_decode($description, true);
			} else {
				$ret['title'] = json_decode($title,true);
				$ret['title'] = $ret['title'][$lang];            
				$ret['description'] = json_decode($description, true);
				$ret['description'] = $ret['description'][$lang];
			}
            
            $ret['episodeid'] = $id;
            $ret['thumbnail'] = $thumbnail;
            $ret['embed'] 	  = $embed;            
            return $ret;
        } else {
            return 0;
        }
    }

    /* gets the season / episode number for the latest episode of the specified show */
    public function getLatestEpisodeDetails($showid){
		$e = ORM::for_table('episodes')->select_many('season', 'episode')->where('show_id', $showid)->order_by_desc('episode')->find_one();
        if ($e){
            return ["season" => $e->season, "episode" => $e->episode];
        } else {
            return ["season" => 0,"episode" => 0];
        }
    }
    
    /* Get the latest episode for a given show */
    public function getLatestEpisode($showid=null){
        
		if (@$showid){ 
			$e = ORM::for_table('episodes')
            ->select_many(['catid' => 'episodes.show_id'], ['category_thumbnail' => 'shows.thumbnail'], 'episodes.thumbnail', 
			'episodes.season', 'shows.title')
			->select_expr('MAX(episodes.season)', 'maxseason')
			->select_expr('MAX(episodes.episode)', 'maxepisode')
            ->join('shows', 'shows.id = episodes.show_id')
            ->where('shows.id', $showid)            
            ->group_by_expr('`episodes`.`show_id`, `episodes`.`season`')          
            ->find_array(); 
		} else { 
			$e = ORM::for_table('episodes')
            ->select_many(['catid' => 'episodes.show_id'], ['category_thumbnail' => 'shows.thumbnail'], 'episodes.thumbnail', 
			'episodes.season', 'shows.title')
			->select_expr('MAX(episodes.season)', 'maxseason')
			->select_expr('MAX(episodes.episode)', 'maxepisode')
            ->join('shows', 'shows.id = episodes.show_id')           
            ->group_by_expr('`episodes`.`show_id`, `episodes`.`season`')          
            ->find_array();
		}
        $ret = [];
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                if ($maxseason==$season){
                    $ret[$catid] = [];
                    $ret[$catid]['season']  = $maxseason;
                    $ret[$catid]['episode'] = $maxepisode;
                    $ret[$catid]['title']	= $title;
                    if (!$ret[$catid]['title']){
                        $ret[$catid]['title'] = "Season $maxseason, Episode $maxepisode";
                    }
                    if ($thumbnail){
                        $ret[$catid]['thumbnail'] = $thumbnail;
                    } else {
                        $ret[$catid]['thumbnail'] = $category_thumbnail;
                    }
                }
            }
        }
        return $ret;
    }

    /* Gets the next episode from given position */
    public function getNextEpisode($show_id, $season, $episode){
	$e = ORM::for_table('episodes')
		->select_many('season', 'episode')
		->where_raw('((season = ? AND episode > ?) OR (season > ? AND episode < ?))', [$season, $episode, $season, $episode])
		->where('show_id', $show_id)
		->order_by_asc('season')
		->order_by_desc('episode')
		->find_one();
        $res = [];
        if ($e){			
			$res['season'] = $e->season;
			$res['episode'] = $e->episode;	
        }
        
        return $res;
    }
    
    /* Gets the previous episode from given position */
    public function getPrevEpisode($show_id, $season, $episode){
	$e = ORM::for_table('episodes')
        ->select_many('season', 'episode')
		->where_raw('((season = ? AND episode < ?) OR (season < ? AND episode > ?))', [$season, $episode, $season, $episode])
		->where('show_id', $show_id)
		->order_by_desc('season')
		->order_by_desc('episode')
		->find_one();
        $res = [];
        if ($e){
            $res['season'] = $e->season;
            $res['episode'] = $e->episode;
        }
        
        return $res;
    }
    
    /* Returns the newest episodes */
    public function getLastAdditions($limit=20, $perma=null){
        $ret = [];
        if ($perma){ 
            $e = ORM::for_table('episodes')
            ->select_many(['showtitle' => 'shows.title'], ['showid' => 'shows.id'], ['show_thumbnail' => 'shows.thumbnail'], 'shows.permalink',
			'episodes.*')
            ->join('shows', 'shows.id = episodes.show_id')
            ->where('shows.permalink', $perma)            
            ->order_by_desc('episodes.id')          
            ->limit($limit)          
            ->find_array();
        } else { 
            $e = ORM::for_table('episodes')
            ->select_many(['showtitle' => 'shows.title'], ['showid' => 'shows.id'], ['show_thumbnail' => 'shows.thumbnail'], 'shows.permalink',
			'episodes.*')
            ->join('shows', 'shows.id = episodes.show_id')            
            ->order_by_desc('episodes.id')          
            ->limit($limit)          
            ->find_array();
        }
        
        if (count($e) > 0){
            foreach($e as $s){
                extract($s);
                $ret[$id] = [];
                $ret[$id]['showtitle']  = $showtitle;
                $ret[$id]['title']		= $title;
                if (!$ret[$id]['title']){
                    $ret[$id]['title'] 	= "Season $season, Episode $episode";
                }
                $ret[$id]['showid']  	 	= $showid;
                $ret[$id]['season']		 	= $season;
                $ret[$id]['episode']	 	= $episode;
                $ret[$id]['description'] 	= $description;
                $ret[$id]['embed']		 	= $embed;
                $ret[$id]['thumbnail']	   	= $thumbnail;
                $ret[$id]['show_thumbnail'] = $show_thumbnail;
                $ret[$id]['thumbnail']		= $thumbnail;
                $ret[$id]['perma']			= $permalink;
                $ret[$id]['date_added']		= $date_added;
            }
        }
        
        return $ret;
    }
    
    /* Returns similar shows to the given ids */
    function getSimilarShows($show_id, $lang = null) {
		$ids 	= [];
		$shows  = [];
		$e = ORM::for_table('tv_tags_join')
		->table_alias('tvjoin1')
		->select('tvjoin1.show_id', 'show1')
		->select('tvjoin2.show_id', 'show2')
		->join('tv_tags_join', ['tvjoin1.tag_id', '=', 'tvjoin2.tag_id'], 'tvjoin2')
		->where_raw('`tvjoin1`.`show_id` != `tvjoin2`.`show_id`')
		->where('tvjoin1.show_id', $show_id)
		->order_by_expr('RAND()')
		->limit(6)
		->find_array();

		if(count($e) > 0){
			foreach($e as $s){
				extract($s);
				$ids[] = $show2;
			}
		}
		
		if(count($ids) > 0){
			$res = ORM::for_table('shows')->where_id_in($ids)->find_array();
			if(count($res) > 0){
				foreach($res as $s){
					extract($s);
					$shows[$id] = $s;
					if (!$lang){
						$shows[$id]['title'] 		 = json_decode($shows[$id]['title'], true);
						$shows[$id]['description']  = json_decode($shows[$id]['description'], true);
					} else {
						$shows[$id]['title'] 		 = json_decode($shows[$id]['title'], true);
						$shows[$id]['title'] 		 = $shows[$id]['title'][$lang];
					
						$shows[$id]['description']  = json_decode($shows[$id]['description'], true);
						$shows[$id]['description']  = $shows[$id]['description'][$lang];
					}                
					$shows[$id]['meta']  		 	 = json_decode($shows[$id]['meta'], true);
				}
			}
		}
		
		return $shows;
	}   
}