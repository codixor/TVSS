<?php

class Settings{
 
   	public function __construct(){
	
   	}
   
	public function getModules(){
		$e = ORM::for_table('modules')->find_array();
		$modules = [];
		if (count($e) > 0){
			foreach($e as $s){
				$modules[$s['perma']]=$s;
			}
		}
		
		return $modules;
	}
	
	public function setModule($module_id, $status){
		$up = ORM::for_table('modules')->find_one($module_id);
		$up->set('status', $status);
		$up->save();
	}
	
	public function addWidget($params){
		$widget = [];
		$reference 			= "widget_".$params['widget_reference'];
		$widget['content']  = $params['widget_content'];
		if (!isset($params['widget_logged']) || !$params['widget_logged']){
			$widget['logged'] = 0;
		} else {
			$widget['logged'] = 1;
		}
		
		$widget = json_encode($widget);
		
		$ins = ORM::for_table('settings')->create();
		$ins->title = $reference; 
		$ins->value = $widget;
		$ins->save();
		return $ins->id;
	}
	
	public function updateWidget($params, $widget_id){
		$widget = [];
		
		$widget_id = $widget_id;
		$reference 		   = "widget_".$params['widget_reference'];
		$widget['content'] = $params['widget_content'];
		if (!isset($params['widget_logged']) || !$params['widget_logged']){
			$widget['logged'] = 0;
		} else {
			$widget['logged'] = 1;
		}
		
		$widget = json_encode($widget);
		
		$up = ORM::for_table('settings')->find_one($widget_id);
		$up->set('title', $title);
		$up->value = $widget;
		$up->save();
	}
	
	public function deleteWidget($widget_id){		
		$del = ORM::for_table('settings')->find_one($widget_id)->delete();
	}
	
	public function getWidgets(){
		$widgets = [];		
		$e = ORM::for_table('settings')->where_like('title', 'widget_%')->find_array();
		if (count($e) > 0){
			foreach($e as $s){
				$reference 			 		= explode("widget_",$s['title']);
				$reference 			 		= $reference[1];
				$widgets[$reference] 		= json_decode($s['value'],true);
				$widgets[$reference]['id']  = $s['id'];
				
			}
		}
		return $widgets;
	}
	
	public function validateWidget($params, $widget_id = null){
		
		$errors = array();
		if (!isset($params['widget_reference']) || !$params['widget_reference']){
			$errors[1] = "Please enter a reference for this widget";
		} else {
			preg_match("/[^a-zA-Z0-9_]/",$params['widget_reference'],$matches);
			if (count($matches)){
				$errors[1] = "Reference can only contain alphanumeric characters and underscores";
			} else {
				$reference = "widget_".$params['widget_reference'];
				if (!$widget_id){
					$check = ORM::for_table('settings')->where('title', $reference)->find_one();					
				} else {
					$check = ORM::for_table('settings')->where_equal('title', $reference)->where_not_equal('id', $widget_id)->find_one();					
				}
				if ($check){
					$errors[1] = "This reference is already in use";
				}
			}
		}
		
		if (!isset($params['widget_content']) || !$params['widget_content']){
			$errors[2] = "Please enter the content of the widget";
		}
		
		return $errors;
		
	}
	
	public function getMultiSettings($settings, $get_array=false){
		$res = [];
		
		$e = ORM::for_table('settings')->where_in('title', $settings)->find_array();
		if (count($e) > 0){
			foreach($e as $s){
				extract($s);	
				if (isset($value[0]) && $value[0]=="{"){
		   			if (!$get_array){
						$res[$title] = json_decode($value);
		   			} else {
		   				$res[$title] = json_decode($value,true);
		   			}
				} else {
					$res[$title] = $value;
				}
			}
		}
		
		return $res;
	}
   
   	public function getSetting($title, $get_array=false){
     	$setting = [];
		$e = ORM::for_table('settings')->where('title', $title)->find_array();
	 	if (count($e) == 1){
			foreach($e as $s){
				extract($s);
				if (isset($value[0]) && $value[0]=="{"){
					if (!$get_array){
						$setting = json_decode($value);
					} else {
						$setting = json_decode($value,true);
					}
				} else {
					$setting = $value;
				}
			}
	 	}
	 
	 	return $setting;
   	}
   
   	public function addSetting($title,$set){
		$e = ORM::for_table('settings')->where('title', $title)->find_one();
	 	if (!$e){
			$e = ORM::for_table('settings')->create();
			$e->title = $title; 
			$e->value = $set;
			$e->save();
	 	} else {
			$e->set('title', $title);
			$e->value = $set;
			$e->save();
	 	}	
   	}
   
   	public function deleteSetting($title){
		$del = ORM::for_table('settings')->where('title', $title)->delete();
   	}

}
?>