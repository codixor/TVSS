<?php

class Stream{
	
	function __construct(){
	
	
	}
	
	public function get($max_id, $limit=20, $lang=null, $user_id = null, $friends = array()){
		$where = array();
		
		if ($user_id && is_numeric($user_id) && $max_id){
			if (count($friends)){
				$friends[] = $user_id;
				$e = ORM::for_table('activity')->where_in('user_id', $friends)->where_lt('id', $max_id)->limit($limit)->find_array();
			} else {
				$e = ORM::for_table('activity')->where('user_id', $user_id)->where_lt('id', $max_id)->limit($limit)->find_array();
			}
		}elseif ($user_id && is_numeric($user_id) && !$max_id){
			if (count($friends)){
				$friends[] = $user_id;
				$e = ORM::for_table('activity')->where_in('user_id', $friends)->limit($limit)->find_array();
			} else {
				$e = ORM::for_table('activity')->where('user_id', $user_id)->limit($limit)->find_array();
			}
		}elseif (!$user_id && $max_id){
			$e = ORM::for_table('activity')->where_lt('id', $max_id)->limit($limit)->find_array();
		}else{
			$e = array();
		}
						
		$res = array();
		if (count($e) > 0){
			foreach($e as $s){
				$s['user_data'] = json_decode($s['user_data'],true);
				if (!$lang){
					$s['target_data'] = json_decode($s['target_data'],true);
				} else {
					$s['target_data'] = json_decode($s['target_data'],true);
					if (isset($s['target_data']['title'])){
						if (isset($s['target_data']['title'][$lang])){
							$s['target_data']['title'] = $s['target_data']['title'][$lang];
						} else {
							$s['target_data']['title'] = $s['target_data']['title']['en'];
						}
					}			
					
					if (isset($s['target_data']['description']) && $s['target_data']['description']){
						
						if (isset($s['target_data']['description'][$lang])){
							
							$s['target_data']['description'] = $s['target_data']['description'][$lang];
						} else {
							$s['target_data']['description'] = $s['target_data']['description']['en'];
						}
					} else {
						$s['target_data']['description'] = "";
					}
					
					if (isset($s['target_data']['showtitle']) && $s['target_data']['showtitle']){
						if (isset($s['target_data']['showtitle'][$lang])){
							$s['target_data']['showtitle'] = $s['target_data']['showtitle'][$lang];
						} else {
							$s['target_data']['showtitle'] = $s['target_data']['showtitle']['en'];
						}
					} 
				}
				$res[$s['id']] = $s;
			}
		}
		return $res;
	}
	
	public function addLike($data){
		
		$e = ORM::for_table('likes')->where('user_id', $data['user_id'])->where('target_id', $data['target_id'])->where('target_type', $data['target_type'])->find_one();
		
		if(!empty($e->id))
			$e->delete();
			
		$ins = ORM::for_table('likes')->create();
		$ins->set($data);
		$ins->save();
		
		return $ins->id;
		
	}
	
	public function addWatch($data){
				
		if ($data['target_type']==3){
			if (!isset($_SESSION['loggeduser_seen_episodes'])){
				$_SESSION['loggeduser_seen_episodes'] = array();
			}
			
			if (!in_array($data['target_id'],$_SESSION['loggeduser_seen_episodes'])){
				$_SESSION['loggeduser_seen_episodes'][] = $data['target_id'];
			}
		} elseif ($data['target_type']==2){
			if (!isset($_SESSION['loggeduser_seen_movies'])){
				$_SESSION['loggeduser_seen_movies'] = array();
			}
			
			if (!in_array($data['target_id'],$_SESSION['loggeduser_seen_movies'])){
				$_SESSION['loggeduser_seen_movies'][] = $data['target_id'];
			}
		}
		
		$e = ORM::for_table('watches')->where('user_id', $data['user_id'])->where('target_id', $data['target_id'])->where('target_type', $data['target_type'])->find_one();
		
		if(!$e){
			$ins = ORM::for_table('watches')->create();
			$ins->set($data);
			$ins->save();
			
			return $ins->id;
		}else {
		
			return 0;
		}
	}
	
	public function addActivity($data){

		foreach($data as $key => $val){		
			if (is_array($val)){
				$data[$key] = json_encode($val);
			} else {
				$data[$key] = $val;
			}
		}
		
		$ins = ORM::for_table('activity')->create();
		$ins->set($data);
		$ins->save();
		
		return $ins->id;
	}

}