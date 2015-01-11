<?php
 class Page{
	function __construct(){
	
	}
	
	function validate($params,$page_id=null){
		global $global_languages;
		
		$errors = array();
		
		foreach($global_languages as $lang_code => $lang_name){
			if (!isset($params['title'][$lang_code]) || !$params['title'][$lang_code]){
				$errors[1][$lang_code] = "Please enter the $lang_name title for this page";
			}
			
			if (!isset($params['content'][$lang_code]) || !$params['content'][$lang_code]){
				$errors[2][$lang_code] = "Please enter the $lang_name content for this page";
			}
		}
		
		if (isset($params['parent_id']) && !is_numeric($params['parent_id'])){
			$errors[3] = "Invalid parent page";
		} elseif ($params['parent_id']>0) {
			$check = ORM::for_table('pages')->find_one($params['parent_id']);
			if (!$check){
				$errors[3] = "Invalid parent page";
			}
		}

		return $errors;
	}
	
	function validatePerma($perma,$page_id = null){
		global $default_language;
			
		if ($page_id){
			$check = ORM::for_table('pages')->where_equal('permalink', $perma)->where_not_equal('id', $page_id)->find_one();			
		} else {
			$check = ORM::for_table('pages')->where_equal('permalink', $perma)->find_one();
		}
		
		if ($check){
			return [1 => [$default_language => "There is already a page with the same title"]];
		}
	}
	
	function save($params,$page_id=null){
		
		$title 		= json_encode($params['title']);
		$permalink 	= $params['permalink'];
		$content 	= json_encode($params['content']);
		
		if (isset($params['parent_id'])){
			$parent_id = $params['parent_id'];
		} else {
			$parent_id = 0;
		}
		
		if (isset($params['visible']) && $params['visible']){
			$visible = 1;
		} else {
			$visible = 0;
		}
		
		if ($page_id){
			$e = ORM::for_table('pages')->find_one($page_id);
			if($e){
				$e->set('title', $title);
				$e->permalink 	= $permalink;
				$e->content 	= $content;
				$e->parent_id 	= $parent_id;
				$e->visible 	= $visible;
				$e->save();
			}
			return $e->id;
		} else {
			$e = ORM::for_table('pages')->create();
			$e->title       = $title;
			$e->permalink   = $permalink;
			$e->content     = $content;
			$e->parent_id   = $parent_id;
			$e->visible     = $visible;
			$e->save();			
			return mysql_insert_id();
		}
	}
	
	function getPagesMenu($lang){
		$pages = [];
		$e = ORM::for_table('pages')->where('visible', 1)->find_many();
		if (count($e) > 0){
			foreach($e as $s){
				$title = json_decode($s['title'],true);
				$title = $title[$lang];
				
				$content = json_decode($s['content'],true);
				$content = $content[$lang];		
				
				if ($s['parent_id'] == 0){
					if (!isset($pages[$s['id']])){
						$pages[$s['id']] = [];
						$pages[$s['id']]['children'] = [];
					}
					
					$pages[$s['id']]['title'] 		= $title;
					$pages[$s['id']]['content'] 	= $content;
					$pages[$s['id']]['permalink'] 	= $s['permalink'];
				} else {
					if (!isset($pages[$s['parent_id']])){
						$pages[$s['parent_id']] 			= [];
						$pages[$s['parent_id']]['children'] = [];
					}
					
					$pages[$s['parent_id']]['children'][$s['id']] 				= [];
					$pages[$s['parent_id']]['children'][$s['id']]['title'] 		= $title;
					$pages[$s['parent_id']]['children'][$s['id']]['content'] 	= $content;
					$pages[$s['parent_id']]['children'][$s['id']]['permalink'] 	= $s['permalink'];
				}
			}
		}
		
		return $pages;
	}
	
	function getPages($lang = null){
		$pages = [];
		$e = ORM::for_table('pages')->find_many();
		if (count($e) > 0){
			foreach($e as $s){
				$pages[$s['id']] = $s;
				if ($lang){
					$pages[$s['id']]['title'] = json_decode($pages[$s['id']]['title'],true);
					$pages[$s['id']]['title'] = $pages[$s['id']]['title'][$lang];
					
					$pages[$s['id']]['content'] = json_decode($pages[$s['id']]['content'],true);
					$pages[$s['id']]['content'] = $pages[$s['id']]['content'][$lang];
				} else {
					$pages[$s['id']]['title'] = json_decode($pages[$s['id']]['title'],true);
					
					$pages[$s['id']]['content'] = json_decode($pages[$s['id']]['content'],true);
				}
				
			}
		}
		return $pages;
	}
	
	function delete($page_id){
		$del = ORM::for_table('pages')->find_one($page_id);
		if($del){
			$del->delete();
		}
		$upd = ORM::for_table('pages')->where('parent_id', $page_id)->find_result_set()
			->set('parent_id', 0)
			->save();
	}
	
	function getByPerma($permalink, $lang=null){
		$page = [];
		
		$e = ORM::for_table('pages')->where('permalink', $permalink)->find_array();		
		if (count($e) == 1){			
			$page = $e[0];
			if ($lang){
				$page['title'] = json_decode($page['title'],true);
				$page['title'] = $page['title'][$lang];
				
				$page['content'] = json_decode($page['content'],true);
				$page['content'] = $page['content'][$lang];
			} else {
				$page['title'] = json_decode($page['title'],true);
				$page['content'] = json_decode($page['content'],true);
			}
		}
		
		return $page;
	}
	
	function getPage($id, $lang=null){				
		$page = [];
		
		$e = ORM::for_table('pages')->where('id', $id)->find_array();	
		if (count($e) == 1){			
			$page = $e[0];
			if ($lang){
				$page['title'] = json_decode($page['title'],true);
				$page['title'] = $page['title'][$lang];
				
				$page['content'] = json_decode($page['content'],true);
				$page['content'] = $page['content'][$lang];
			} else {
				$page['title'] 	 = json_decode($page['title'],true);
				$page['content'] = json_decode($page['content'],true);
			}
		}
		return $page;
	}
 }
?>