<?php
class User{
    
    public $id = null;
    
    function __construct($id=null){
        if (!$id){
            if (isset($_SESSION['loggeduser_id']) && $_SESSION['loggeduser_id']){
                $this->id = $_SESSION['loggeduser_id'];
            }
        } else {
            $this->id = null;
        }
    }
    
    public function addAdmin($params){
		$pass_hash  = password_hash($new_password, PASSWORD_BCRYPT);
		$admin 		= ORM::for_table('admin')->create();
		$admin->username = $params['username'];
		$admin->password = $pass_hash;
		$admin->save();
        return $admin;
    }
    
    public function updateAdmin($params, $admin_id){
		$pass_hash  = password_hash($new_password, PASSWORD_BCRYPT);
		$admin 		= ORM::for_table('admin')->find_one($admin_id);
		$admin->set(['username' => $params['username'], 'password'  => $pass_hash])->save();
    }
    
    public function removeAdmin($admin_id){
		$admin = ORM::for_table('admin')->find_one($admin_id);
        $admin->delete();
    }
    
    public function validateAdmin($admin, $edit_admin = null){
        $errors = [];        
        if (!isset($admin['username']) || !$admin['username']){
            $errors[1] = 'Please enter the admin username';
        } elseif (strlen($admin['username'])<5){
            $errors[1] = 'Admin username must be at least 5 characters long';
        } else {
            if (!$edit_admin){
				$check = ORM::for_table('admin')->where('username', $admin['username'])->count();
                if ($check > 0){
                    $errors[1] = 'There is already an admin user with this username';
                }
            } else {
				$check = ORM::for_table('admin')->where('username', $admin['username'])->where_not_equal('id', $edit_admin)->count();
                if ($check > 0){
                    $errors[1] = 'There is already an admin user with this username';
                }
            }
        }
        
        if (!isset($admin['password']) || !$admin['password']){
            $errors[2] = 'Please enter the admin password';
        } elseif (strlen($admin['password'])<5){
            $errors[2] = 'Admin password must be at least 5 characters long';
        }
        
        if (!isset($admin['password2']) || !$admin['password2'] || (isset($admin['password']) && $admin['password']!=$admin['password2'])){
            $errors[3] = 'Invalid password confirmation';
        }
        
        return $errors;
    }
    
    public function getAdmin($admin_id){
        $admin = ORM::for_table('admin')->find_one($admin_id);
        if (count($admin)> 0){		
            return $admin;
        } else {
            return [];
        }
    }
    
    public function getAdminUsers(){
        $res 	= [];
		$admins = ORM::for_table('admin')->find_many();
        return $admins;
    }
    
    public function getByUsername($username){

		$user = ORM::for_table('users')->where('username', $username)->find_one();
        if ( count($user) > 0){
            if (!$user->avatar){
                $user->avatar = 'nopic.jpg';
            }
            return $user;
        } else {
            return false;
        }
    }
    
    public function get($id){
		$user = ORM::for_table('users')->find_one($id);       
        if ( count($user) > 0 ){
            return $user;
        } else {
            return false;
        }
    }
    
    public function getUserCount($search_term=null){

        if ($search_term){	
			$people = ORM::for_table('users')->where_raw('(`username` LIKE ? OR `email` LIKE ?)', ['%'.$search_term.'%', '%'.$search_term.'%'])->count();            
        } else {
			$people = ORM::for_table('users')->count();
        }
		
        return $people;
    }
    
    public function search($query, $start=null, $limit=null, $sortby='id', $sortdir='DESC'){
        if (!$limit || !is_numeric($limit)){
            $limit = 50;
        }
        
        if ($start && is_numeric($start)){
			$e = ORM::for_table('users')->where_raw('(`username` LIKE ? OR `email` LIKE ?)', ['%'.$query.'%', '%'.$query.'%'])->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_many();
        } else {
			$e = ORM::for_table('users')->where_raw('(`username` LIKE ? OR `email` LIKE ?)', ['%'.$query.'%', '%'.$query.'%'])->order_by_expr($sortby, $sortdir)->limit($limit)->find_many();
        }       
        $users = [];
        if (count($e) > 0){
            foreach($e as $s ){
				$users[$s->id]=[];
                $users[$s->id]['username']  = $s->username;
                $users[$s->id]['email'] 	= $s->email;
                $users[$s->id]['language']  = $s->language;
                $users[$s->id]['fb_id'] 	= $s->fb_id;
                
                if (!$s->avatar){
                    $s->avatar = 'nopic.jpg';
                }
                $users[$s->id]['avatar'] = $s->avatar;
            } 
        }
        return $users;
    }
    
    public function getAllUsers($page = null, $start=null, $limit=null, $sortby='id', $sortdir='DESC'){
        if (!$limit || !is_numeric($limit)){
            $limit = 50;
        }
              
        
        if ($page && is_numeric($page)){
            $start = ($page-1)*$limit;
			$e = ORM::for_table('users')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_many();            
        } elseif ($start && is_numeric($start)) {
			$e = ORM::for_table('users')->order_by_expr($sortby, $sortdir)->limit($limit)->offset($start)->find_many();
        } else {
			$e = ORM::for_table('users')->order_by_expr($sortby, $sortdir)->limit($limit)->find_many();            
        }
        
        
        $users = [];
       if (count($e) > 0){
            foreach($e as $s ){
                $users[$s->id]				= [];
                $users[$s->id]['username']  = $s->username;
                $users[$s->id]['email'] 	= $s->email;
                $users[$s->id]['language']  = $s->language;
                $users[$s->id]['fb_id'] 	= $s->fb_id;
                
                if (!$s->avatar){
                    $s->avatar = 'nopic.jpg';
                }
                $users[$s->id]['avatar'] = $s->avatar;
            }
        }
        return $users;
    }
    
    public function getFollows($user_id = null){
        if (!$user_id){
            $user_id = $this->id;
        }
        
        if ($user_id && is_numeric($user_id)){
			$users = ORM::for_table('friends')->raw_query('SELECT users.* FROM friends,users WHERE user2 = users.id and user1 = ?', [$user_id])->find_many();
            if (count($users) > 0){
                 foreach($users as $user ){
                    if (!$user->avatar){
						$user->avatar = 'nopic.jpg';
					}
                }
            }    
        }        
        return $users;
    }
    
    public function getFollowers($user_id = null){
        if (!$user_id || !is_numeric($user_id)){
            $user_id = $this->id;
        }   
        
        if ($user_id && is_numeric($user_id)){
			$users = ORM::for_table('friends')->raw_query('SELECT users.* FROM friends,users WHERE user1 = users.id and user2=?', [$user_id])->find_many();
           if (count($users) > 0){
			foreach($users as $user ){
				if (!$user->avatar){
					$user->avatar = 'nopic.jpg';
				}
            }
           }    
        }        
        return $users;
    }
    
    public function unfollow($user1, $user2){ 
		ORM::for_table('friends')->where('user1', $user1)->where('user2', $user2)->delete();
    }
    
    public function follow($user1,$user2){			
        $get_user = ORM::for_table('users')->find_one($user2);
        if (count($get_user) > 0){			
            $e = ORM::for_table('friends')->where('user1', $user1)->where('user2', $user2)->find_one();
            if (!$e){
				$ins 		     = ORM::for_table('friends')->create();
				$ins->user1      = $user1;
				$ins->user2      = $user2;
				$ins->date_added = $this->dt->format('Y-m-d H:i:s');
				$ins->save();
                return $ins->id;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function getFacebookUsers($ids){
		$res = [];
		$e = ORM::for_table('users')->where_in('fb_id', $ids)->find_array();        
        if (count($e) > 0){
            foreach($e as $s){
                $res[$s['id']] = $s;
            }
        }
		
        return $res;
    }
    
    public function validateEmail($email){
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
			$isValid = true;
		}else{
			$isValid = false;
		}
        return $isValid;
    }
    
    public function getNewsletter(){
        $res = [];
		$e = ORM::for_table('users')->select_many('id', 'username', 'avatar', 'email')->where('notify_new', 1)->find_array();
        if (count($e) > 0){
            foreach($e as $s){
                $res[$s['id']] = $s;
            }
        }
		
        return $res;
    }
    
    public function deleteUser($userid){        
        $check = ORM::for_table('users')->find_one($userid);
        if ($check){
            if (file_exists($basepath.'/thumbs/users/'.$check->avatar)){
                unlink($basepath.'/thumbs/users/'.$check->avatar);
            }
			$e = ORM::for_table('users')->find_one($userid)->delete();			
        }else{
			return false;
		}   
                       
    }
    
    public function validate($params){
        global $lang;
        
        $errors=[];
        if ((!@$params['username']) || (!@$params['pass1']) || (!@$params['pass2']) || (!@$params['email'])){
            $errors[0] = $lang['register_all_field_required'];
        } else {
            // Username checks
            
            $tmpuser = preg_replace('/[^a-zA-Z0-9_]/','',$params['username']);
            if ($tmpuser!=$params['username']){
                $errors[1] = $lang['register_no_special_chars'];
            } else {
                if ((strlen($params['username'])<5) || (strlen($params['username'])>25)){
                    $errors[1] = $lang['register_min_5_chars'];
                } else {
                    $params['username'] = strtolower($params['username']);
					$check = ORM::for_table('users')->select('id')->where_raw('LOWER(username) = ?', [$params['username']])->find_one();
                    if ($check){
                        $errors[1] = $lang['register_username_taken'];
                    }
                }
            }
            
            // password checks
            if ($params['pass1']!=$params['pass2']){
                $errors[2] = $lang['register_password_confirm_doesnt_match'];
            } else {
                if (strlen($params['pass1'])<5){
                    $errors[2] = $lang['register_password_min_5_chars'];
                }
            }
            
            // email checks
            if (!$this->validateEmail($params['email'])){
                $errors[3] = $lang['register_invalid_email'];
            } else {
                $params['email'] = strtolower($params['email']);
				$e = ORM::for_table('users')->select('id')->where_raw('LOWER(email) = ?', [$params['email']])->find_one();
                if ($e){
                    $errors[3] = $lang['register_email_in_use'];
                }
            }
        }
        
        return $errors;
    }
    
    public function startSession($s,$cookie=true){
        
        $_SESSION['loggeduser_id'] = $s['id'];
        $_SESSION['loggeduser_username'] = $s['username'];
        $_SESSION['loggeduser_details']=$s;
        
        $_SESSION['loggeduser_seen_movies'] = $this->getSeenMovies($s['id'],true);
        $_SESSION['loggeduser_seen_episodes'] = $this->getSeenEpisodes($s['id'],true);
        
        if (!isset($_SESSION['loggeduser_details']['avatar']) || !$_SESSION['loggeduser_details']['avatar']){
            $_SESSION['loggeduser_details']['avatar'] = "nopic.jpg";
        }
        
        if ($cookie){
            $cookiedata = $s['id']."|".md5($s['username'].$s['password']);
            setcookie("guid",$cookiedata,time()+60*60*24*30, "/");
        }
    }
    
    public function cookieLogin($cookie_content){
        $tmp = explode("|",$cookie_content);
        if (count($tmp)==2){
            $user_id = $tmp[0];
            $hash = $tmp[1];
            $user_id = mysql_real_escape_string($user_id);
            if (is_numeric($user_id)){
                $e = ORM::for_table('users')->select_many('id', 'username', 'fb_id', 'fb_session', 'avatar', 'notify_new', 'notify_favorite', 'password')->where('id', $user_id)->find_array();
                if (count($e) == 1){               
                    $check_hash = md5($e[0]['username'].$e[0]['password']);
                    if ($check_hash == $hash){
                        
                        $this->startSession($s, false);
                        
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function facebookLogin($facebook_id,$token){
        $e = ORM::for_table('users')->select_many('id', 'username', 'fb_id', 'fb_session', 'avatar', 'notify_new', 'notify_favorite', 'password')->where('fb_id', $facebook_id)->find_array();     
        if (count($e) > 0){
			if ($token){
				$e->set(['fb_session' => $token])->save();
            }

            $this->startSession($e[0]);
            
            return $true;
            
        } else {      
            
            return false;
        }
    }
    
    public function login($username, $password){
        $username = mysql_real_escape_string($username);
        $old_password = md5($password);
        $new_password = $password;
        $e = ORM::for_table('users')->select_many('id', 'username', 'fb_id', 'fb_session', 'avatar', 'notify_new', 'notify_favorite', 'password')->where('username', $username)->find_array();
        if(count($e) == 1){
			$s = $e[0];
			
			$res[$s['password']] = $s;
			$stored_password 	 = $res[$s['password']];
			$stored_password 	 = $stored_password['password'];
       
			if(password_needs_rehash($stored_password, PASSWORD_BCRYPT) && $old_password === $stored_password) {
				$stored_password = password_hash($new_password, PASSWORD_BCRYPT);
				$update = ORM::for_table('users')->where('username', $username)->find_one();
				$update->set('password', $stored_password);
				$update->save();				
				$this->startSession($s);
              
			} else {
				if (password_verify($new_password, $stored_password)){
					$this->startSession($s);
				} else {
					echo 'Incorrect login details.';
				}
			}            
        } else {
            return "Invalid login details";
        }
   
		return 0;
    }
    
    public function update($user_id, $params){
        $fields = [];
        foreach($params as $key => $val){
            $fields[$key] = $val;
            $loggeduser_details = $this->session->get('loggeduser_details');
			$loggeduser_details[$key] = $val;
            $this->session->set('loggeduser_details', $tokens);
        }
		$up = ORM::for_table('users')->find_one($user_id);
		if($up){
			$up->set($fields)->save();
		}
    }
    
    public function save($params){
        $username = $params['username'];
        $password = password_hash($params['pass1'], PASSWORD_BCRYPT);
        $email 	  = $params['email'];
		
        if (isset($params['fb_id'])){
            $fb_id = $params['fb_id'];
        } else {
            $fb_id = '';
        }
        
        if (isset($params['fb_session'])){
            $fb_session = $params['fb_session'];
        } else {
            $fb_session = '';
        }
        
        if (isset($params['language'])){
            $language = $params['language'];
        } else {
            $language = "en";
        }
        
		$new_user		 		= ORM::for_table('users')->create();
		$new_user->username 	= $username;
		$new_user->password 	= $password;
		$new_user->email 	 	= $email;
		$new_user->fb_id 	 	= $fb_id;
		$new_user->fb_session   = $fb_session;
		$new_user->language 	= $language;
		$new_user->save();
        
		$user_id = $new_user->id;
		
        $cookiedata = "$user_id|".md5($username.$password);
        setcookie("guid",$cookiedata,time()+60*60*24*30, "/");
        return $user_id;
    }
    
    public function getFavoriteMovies($user_id, $just_ids = false, $lang = false){
		
		if (!$user_id){
            $user_id = $this->id;
        }
		
		$e = ORM::for_table('likes')->distinct()->where('vote', 1)->where('target_type', 2)->where('user_id', $user_id)->find_array();
        $res = [];
        if (count($e) > 0){
            $movie_ids = [];
            foreach($e as $s){
                $movie_ids[] = $s['target_id'];
            }
            
            if ($just_ids){
                return $movie_ids;
            }
            
			$e = ORM::for_table('movies')->where_in('id', $movie_ids)->find_array();			
            if (count($e) > 0){
                foreach($e as $s){
                    $res[$s['id']] = $s;
                    if (!$lang){
                        $res[$s['id']]['title'] = json_decode($res[$s['id']]['title'], true);                    
                    } else {
                        $title = json_decode($res[$s['id']]['title'], true);
                        $res[$s['id']]['title'] = $title[$lang];                                              
                    }
                    
                }
            }
        }
        
        return $res;
    }
    
    public function getFavoriteShows($user_id,$just_ids = false, $lang = false){
        
		if (!$user_id){
            $user_id = $this->id;
        }
		
		$e = ORM::for_table('likes')->distinct('target_id')->where('vote', 1)->where('target_type', 1)->where('user_id', $user_id)->find_array();
        $res = [];
        if (count($e) > 0){
            $movie_ids = [];
            foreach($e as $s){
                $show_ids[] = $s['target_id'];
            }
            
            if ($just_ids){
                return $show_ids;
            }
            
			$e = ORM::for_table('shows')->where_in('id', $show_ids)->find_array();
            if (count($e) > 0){
                foreach($e as $s){
                    $res[$s['id']] = $s;
                    if (!$lang){
                        $res[$s['id']]['title'] = json_decode($res[$s['id']]['title'], true);                    
                    } else {
                        $title = json_decode($res[$s['id']]['title'], true);
                        $res[$s['id']]['title'] = $title[$lang];                                              
                    }
                }
            }
        }
        
        return $res;
    }
    
    public function getSeenMovies($user_id, $just_ids = false, $lang = false){
        if (!$user_id){
            $user_id = $this->id;
        }
        
        $e = ORM::for_table('watches')->distinct('target_id')->where('target_type', 2)->where('user_id', $user_id)->find_array();
        
        $res = [];
        if (count($e) > 0){
            $movie_ids = [];
            foreach($e as $s){
                $movie_ids[] = $s['target_id'];
            }
            
            if ($just_ids){
                return $movie_ids;
            }
            
			$e = ORM::for_table('movies')->where_in('id', $movie_ids)->find_array();			
            if (count($e) > 0){
                foreach($e as $s){
                    $res[$s['id']] = $s;
                    if (!$lang){
                        $res[$s['id']]['title'] = json_decode($res[$s['id']]['title'], true);                    
                    } else {
                        $title = json_decode($res[$s['id']]['title'], true);
                        $res[$s['id']]['title'] = $title[$lang];                                              
                    }                    
                }
            }
        }
        
        return $res;
    }
    
    public function getSeenEpisodes($user_id = null, $just_ids = false, $lang = false){
        if (!$user_id){
            $user_id = $this->id;
        }
        $e = ORM::for_table('watches')->distinct('target_id')->where('target_type', 3)->where('user_id', $user_id)->find_array();
		
        $res = [];
		if (count($e) > 0){
            $episode_ids = [];
            foreach($e as $s){
                $episode_ids[] = $s['target_id'];
            }
            
            if ($just_ids){
                return $episode_ids;
            }                 
			$e = ORM::for_table('episodes', 'shows')->select_many('episodes.id', 'episodes.season', 'episodes.episode', ['shows.title' => 'show_title'], ['shows.id' => 'show_id'], 'episodes.thumbnail', ['shows.thumbnail' => 'show_thumbnail'])->find_array();
            if (count($e) > 0){
                foreach($e as $s){
                    $res[$s['id']] = $s;
                }
            }
        }
        
        return $res;
    }
}
