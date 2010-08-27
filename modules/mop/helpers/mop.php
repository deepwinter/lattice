<?

Class mop {

	private static $config;

	private static $dbmaps;

	public static function config($arena, $xpath){
		if(!is_array(self::$config)){
			self::$config = array();
		}

    if(Kohana::config('mop.configuration')){
      $arena = Kohana::config('mop.configuration').'-'.$arena;
    }

		if(!isset(self::$config[$arena])){
			$dom = new DOMDocument();
			$dom->load( "application/config/$arena.xml");
			$xpathObject = new DOMXPath($dom);
			self::$config[$arena] = $xpathObject;
		}
		$xmlNodes = self::$config[$arena]->evaluate($xpath);
		return $xmlNodes;
	}

	public static function dbmap($template_id, $column=null){
		if(!isset(self::$dbmaps[$template_id])){
			$dbmaps = ORM::Factory('objectmap')->where('template_id', $template_id)->find_all();
			self::$dbmaps[$template_id] = array();
			foreach($dbmaps as $map){
				self::$dbmaps[$template_id][$map->column] = $map->type.$map->index;
			}
		}
		if(!isset($column)){
			return self::$dbmaps[$template_id];
		} else {
			return self::$dbmaps[$template_id][$column];
		}
	}

	/*
	 * Function: buildModule
	 This is the same function as in Display_Controller..
	 Obviously these classes should share a parent class or this is a static helper
	 Parameters:
	 $module - module configuration parameters
	 $constructorArguments - module arguments to constructor
	 */
	public static function buildModule($module, $constructorArguments=NULL){
		Kohana::log('debug', 'Loading module: ' . $module['modulename']);
		Kohana::log('debug', 'Loading controller: ' . $module['modulename']);

		if(!Kohana::find_file('controllers', $module['modulename'] ) ){
			if(!isset($module['controllertype'])){
				$view = new View($module['modulename']);
				return $view->render();
			}
      echo 'yo';
      echo $module['modulename'];
      echo "\n";
      try {
			$includeclass = 'class '.$module['modulename'].'_Controller extends '.$module['controllertype'].'_Controller { } ';
			eval($includeclass);
      } catch (Exception $e){
        throw new Kohana_User_Exception('Redeclared Virtual Class', 'Redeclared Virtual Class '.  'class '.$module['modulename'].'_Controller ';
      }
		}

		$fullname = $module['modulename'].'_Controller';
		$module = new $fullname; //this needs to be called with fargs
		call_user_func_array(array($module, '__construct'), $constructorArguments);

		$module->createIndexView();
		$module->view->loadResources();

		//and load resources for it's possible parents
		$parentclass = get_parent_class($module);
		$parentname = str_replace('_Controller', '', $parentclass);
		$module->view->loadResources(strtolower($parentname));

		//build submodules of this module (if any)
		$module->buildModules();

		return $module->view->render();

		//render some html
		//
		//BELOW HERE NEEDS TO BE FIXED IN ALL CHILDREN OF MOP_CONTROLLER
		//CONTROLERS SHOULD JUST ASSIGN TEMPLATE VARS THEN AND THERE
		if($templatevar==NULL){
			$this->view->$module['modulename'] = $module->view->render();
		} else {
			$this->view->$templatevar = $module->view->render();
		}
	}


}



