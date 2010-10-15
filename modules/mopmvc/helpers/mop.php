<?

    class MyDOMDocument {
      public $_delegate;
      private $_validationErrors;

      public function __construct (DOMDocument $pDocument) {
        $this->_delegate = $pDocument;
        $this->_validationErrors = array();
      }

      public function __call ($pMethodName, $pArgs) {
        if ($pMethodName == "validate") {
          $eh = set_error_handler(array($this, "onValidateError"));
          $rv = $this->_delegate->validate();
          if ($eh) {
            set_error_handler($eh);
          }
          return $rv;
        }
        else {
          return call_user_func_array(array($this->_delegate, $pMethodName), $pArgs);
        }
      }
      public function __get ($pMemberName) {
        if ($pMemberName == "errors") {
          return $this->_validationErrors;
        }
        else {
          return $this->_delegate->$pMemberName;
        }
      }
      public function __set ($pMemberName, $pValue) {
        $this->_delegate->$pMemberName = $pValue;
      }
      public function onValidateError ($pNo, $pString, $pFile = null, $pLine = null, $pContext = null) {
        $this->_validationErrors[] = preg_replace("/^.+: */", "", $pString).$pLine;
      }
    }
Class mop {

	private static $config;

	private static $dbmaps;

	public static function config($arena, $xpath, $contextNode=null){
		if(!is_array(self::$config)){
			self::$config = array();
		}

    if(Kohana::config('mop.configuration')){
      $arena = Kohana::config('mop.configuration').'-'.$arena;
    }



		if(!isset(self::$config[$arena])){
			$dom = new DOMDocument();
			$dom = new MyDOMDocument($dom);
			$dom->load( "application/config/$arena.xml");
      if(!$dom->validate()){
        print_r($dom->errors);  
        echo('Validation failed on '."application/config/$arena.xml");
        //die();
      }
			$xpathObject = new DOMXPath($dom->_delegate);
			self::$config[$arena] = $xpathObject;
		}
		if($contextNode){
			$xmlNodes = self::$config[$arena]->evaluate($xpath, $contextNode);
		} else {
			$xmlNodes = self::$config[$arena]->evaluate($xpath);
		}
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
				$object = ORM::Factory('page', $module['modulename']);
				if($object->loaded){ // in this case it's a slug for a specific object
					foreach(mop::getViewContent($object->id, $object->template->templatename) as $key=>$content){
						$view->$key = $content;
					}
				}
				return $view->render();
			}
      try {
        if(!class_exists($module['modulename'].'_Controller')){
          $includeclass = 'class '.$module['modulename'].'_Controller extends '.$module['controllertype'].'_Controller { } ';
          eval($includeclass);
        }
      } catch (Exception $e){
        throw new Kohana_User_Exception('Redeclared Virtual Class', 'Redeclared Virtual Class '.  'class '.$module['modulename'].'_Controller ');
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

	public static function getViewContent($view, $slug=null){

		$data = array();

    if($view == 'default'){
			$object = ORM::Factory('page', $slug);
			if(!$object->loaded){
				die('mop::getViewContent : Default view callled with no slug');
			}
			$data['content']['main'] = $object->getPageContent();
      return $data;
    }

		$viewConfig = mop::config('frontend', "//view[@name=\"$view\"]")->item(0);
		if(!$viewConfig){
			die("No View setup in frontend.xml by that name: $view");
		}
		if($viewConfig->getAttribute('loadPage')){
			$object = ORM::Factory('page', $slug);
			if(!$object->loaded){
				die('mop::getViewContent : View specifies loadPage but no page to load');
			}
			$data['content']['main'] = $object->getPageContent();
		}

		if($eDataNodes = mop::config('frontend',"includeData", $viewConfig)){
			foreach($eDataNodes as $eDataConfig){

				$objects = ORM::Factory('page');

				//apply optional parent filter
				if($from = $eDataConfig->getAttribute('from')){
					if($from=='parent'){
						$objects->where('parentid', $object->id);
					} else {
						$from = ORM::Factory('page', $from);
						$objects->where('parentid', $from->id);	
					}
				}

				//apply optional template filter
				$objects->templateFilter($eDataConfig->getAttribute('templateFilter'));


				//apply optional SQL where filter
				if($where = $eDataConfig->getAttribute('where')){
					$objects->where($where);
				}

				$objects = $objects->find_all();

				$label = $eDataConfig->getAttribute('label');
				$data['content'][$label] = array();
				foreach($objects as $includeObject){
					$data['content'][$label][] = $includeObject->getContent();
				}
			}
		}

		if($subViews = mop::config('frontend',"subView", $viewConfig)){
			foreach($subViews as $subview){
				$view = $subview->getAttribute('view');
				$slug = $subview->getAttribute('slug');
				$label = $subview->getAttribute('label');
				if(mop::config('frontend', "//view[@name=\"$view\"]")){

					if($view && $slug){
						$subViewContent = mop::getViewContent($view, $slug);
					} else if($slug){
						$object = ORM::Factory('page', $slug);
						$view = $object->template->templatename;
						$subViewContent = mop::getViewContent($view, $slug);
					} else if($view){
						$subViewContent = mop::getViewContent($view);
					} else {
						die("subview $label must have either view or slug");
					}
					$subView = new View($view);

					foreach($subViewContent as $key=>$content){
						$subView->$key = $content;
					}
					$data[$label] = $subView->render();
				} else {
					//assume it's a module
					$data[$label] = mop::buildModule(array('modulename'=>$view/*, 'controllertype'=>'object'*/), $subview->getAttribute('label'));
				}
			}
		}

		return $data;
	}


}



