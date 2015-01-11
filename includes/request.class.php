<?php

class Request{
	
	function __construct(){
		
	}
	
	public function validate($params){
		global $lang;
		
		$errors = array();
		if (!isset($params['request_content']) || !$params['request_content'] || $params['request_content'] == $lang['requests_details']){
			$errors[] = $lang['requests_empty_details'];
		}
		
		if (!isset($_SESSION['loggeduser_id']) || !$_SESSION['loggeduser_id']){
			$errors[] = $lang[''];
		}
		
		return $errors;
	}
	
	public function save($params){
		$data = [];
		$data['message'] 	  = strip_tags($params['request_content']);
		$data['user_id'] 	  = $_SESSION['loggeduser_id'];
		$data['request_date'] = date("Y-m-d H:i:s");
		$data['status'] = 0;		
		 
		$check = ORM::for_table('requests')->where('user_id', $data['user_id'])->where_like('message', $data['message'])->find_one();
		if (!$check){
			$ins 		  = ORM::for_table('requests')->create();
			$ins->user_id     	= $data['user_id'];
			$ins->request_date  = $data['request_date'];
			$ins->message   	= $data['message'];
			$ins->status   		= $data['status'];
			$ins->votes  		= 1;
			$ins->save();
			return $ins->id;
		} else {
			return false;
		}
	}
	
	public function getActive(){
		global $misc,$lang;
		
		$res = [];
		$two_weeks = date("Y-m-d H:i:s",strtotime("14 days ago"));
		$e = ORM::for_table('requests')
			->select_many('requests.*', 'users.username', 'users.avatar', 'users.email')
			->join('users', 'users.id = requests.user_id')
			->where_raw ('status=0 OR status=1 OR request_date>=?', [$two_weeks])
			->order_by_desc('requests.votes')
			->order_by_desc('requests.id')
			->find_array();
		if (count($e) > 0){
			foreach($e as $s){
				$res[$s['id']] = $s;
				if (!$res[$s['id']]['avatar']){
					$res[$s['id']]['avatar'] = "nopic.jpg";
				}
				
				$res[$s['id']]['date_print'] = $misc->ago(strtotime($s['request_date']),$lang);
			}
		}
		
		return $res;
		
	}
	
	public function getAll(){
		global $misc,$lang;		
		$res = [];
		$e = ORM::for_table('requests')
			->select_many('requests.*', 'users.username', 'users.avatar', 'users.email')
			->join('users', 'users.id = requests.user_id')
			->order_by_desc('requests.votes')
			->order_by_desc('requests.id')
			->find_array();
		if (count($e) > 0){
			foreach($e as $s){
				$res[$s['id']] = $s;
				if (!$res[$s['id']]['avatar']){
					$res[$s['id']]['avatar'] = "nopic.jpg";
				}
				
				$res[$s['id']]['date_print'] = $misc->ago(strtotime($s['request_date']),$lang);
			}
		}
		
		return $res;
		
	}
	
	public function addVote($request_id, $user_id){		
		// checking if request even exists
			
		$e = ORM::for_table('requests')->find_one($request_id);
		if ($e){
			if ($e->user_id != $user_id){				
				$check = ORM::for_table('requests')->where('request_id', $request_id)->where('user_id', $user_id)->find_one();
				if ($check){
					$today = date("Y-m-d H:i:s");
					$check->set_expr('votes', 'votes+1')->save();
					$ins = ORM::for_table('request_votes')->create();
					$ins->request_id  = $request_id;
					$ins->user_id 	  = $user_id;
					$ins->vote_date   = $today;
					$ins->save();		
					return $e->votes+1;
				} else {
					return $e->votes;	
				}
			} else {
				return $s['votes'];
			}
		} else {
			return false;
		}
	}
	
	public function getPendingCount(){
		$e = ORM::for_table('requests')->select_expr('COUNT(*)', 'pending_count')->where('status', 0)->find_one();
		if ($e){
			if (!$e->pending_count){
				$e->pending_count = 0;
			}
			
			return $e->pending_count;
		} else {
			return 0;
		}
	}
	
	public function delete($request_id){
		$e = ORM::for_table('requests')->find_one($request_id)->delete();
		$e = ORM::for_table('request_votes')->where_equal('request_id', $request_id)->delete();
	}
	
	public function update($request_id, $data){
		$up = ORM::for_table('requests')->find_one($request_id);		
		$up->set(['response' => $data['response']])->set(['status' => $data['status']])->save();
	}
	
}

?>