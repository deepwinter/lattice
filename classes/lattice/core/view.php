<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of core_views
 *
 * @author deepwinter1
 */
/* @package Lattice */

class Lattice_Core_View {

  private static $initial_object = NULL;

  protected $data;
  protected $view;
  protected $object;

  public static function Factory($object_id_or_slug)
  {

    $object = self::get_graph_object($object_id_or_slug);
    $objectTypeName = $object->objecttype->objecttypename;
    if (Kohana::find_file('classes', 'view/model/'.strtolower($objectTypeName)))
    {
      $class_name = 'View_Model_'.$objectTypeName;
      $view_model = new $class_name($object_id_or_slug);
    } else {
      $view_model = new core_view($object_id_or_slug);
    }
    return $view_model;
  }

  /*
   * slug($slug)
   * Gets the a language aware slug based on the requested slug
   */
  public static function slug($slug_or_object_id)
  {

    // look in the cache
    $language_code = Session::instance()->get('language_code');
    if ( ! $language_code)
    {
      return $slug_or_object_id;
    }
    $object = Graph::object($slug_or_object_id);
    $original_slug_base = str_replace(array('0','1','2','3','4','5','6','7','8','9'), '', strtok($object->slug,'_'));
    $translated_object = $object->translate($language_code);
    $redirect_slug = $translated_object->slug;


    if (preg_match("/{$original_slug_base}[0-9]+/", $redirect_slug))
    {
      return $original_slug_base.'_'.$language_code;
    } else {	
      return $redirect_slug;
    }

  }

  /*
   * public static function language()
   * Returns the currently selected language code
   */
  public static function language()
  {
    return Session::instance()->get('language_code');
  }

  public static function within_subtree($slug)
  {
    // Direct links are not within lattice
    if (strstr($slug,'http'))
    {
      return FALSE;
    }
    // Only check the first part of given route
    // This allos support for custom controllers.
    $route = explode('/', $slug);
    $slug = $route[0];
    if ( self::initial_object() )
    {
      try {
        $val = self::initial_object()->is_within_sub_tree($slug);
        return $val;
      } catch( Exception $e )
      {
        return FALSE;
      }
    }

  }

  public function __construct($object_id_or_slug = NULL)
  {
    try{
      if ($object_id_or_slug != NULL)
      {
        $this->object = self::get_graph_object($object_id_or_slug);
        $this->create_view($object_id_or_slug);
      }		 	
    } catch ( Exception $e )
      {
        throw $e; // ("There is no graph object with this object id or slug : " $object_id_or_slug . ' in object ' .  $object->slug);
        return FALSE;
      } 

  }

  public function data()
  {
    return $this->data;
  }

	/*
	 * Function: view()
	 * Returns the rendered view for the current view model
	 */
	public function view($set_view = null){
		if($set_view){
			$this->view = new View($set_view);
		}

		foreach ($this->data as $key => $value) {
			$this->view->$key = $value;
		}

		return $this->view;
	}

	private static function get_graph_object($object_id_or_slug)
	{
		if ( ! is_object($object_id_or_slug))
		{
			$object = Graph::object($object_id_or_slug);
		} else {
			$object = $object_id_or_slug;
		}
		if ( ! $object->loaded())
		{
			throw new Kohana_Exception("Trying to create view, but object is not loaded: $object_id_or_slug ".$object->slug);
		}
		return $object;

	}

	public function set_var($name, $value)
	{
		if ( ! $this->view)
		{
			throw new Kohana_Exception('set_var called but no local variable view has not been set');
		}

		$this->view->$name = $value;
		$this->data[$name] = $value;
	}

	public function create_view($object_id_or_slug = NULL)
	{

		if ($object_id_or_slug)
		{
			$this->object = $this->get_graph_object($object_id_or_slug);
		}

		if ( ! self::$initial_object)
		{
			self::$initial_object = $this->object;
		}

		// some access control
		$view_name = NULL;
		$view = NULL;

		/*
		 * This logic needs to be cleaned up.  Does it really make sense to be calling
		 * lattice view model for views that have no object?  Virtual views - these items can also
		 * have view content, and could have view models, but no object
		 */
		if ($this->object->loaded())
		{
			if ($this->object->activity != NULL)
			{
				throw new Kohana_User_Exception('Page not availabled', 'The object with identifier ' . $id . ' is does not exist or is not available');
			}
			// look for the object_type, if it's not there just print out all the data raw
			$view_name = $this->object->objecttype->objecttypename;
        if (file_exists('application/views/frontend/' . $view_name . '.php'))
        {
          $view_path = 'frontend/'.$view_name;
        } elseif (file_exists('application/views/generated/' . $view_name . '.php'))
        {
          $view_path = 'generated/'.$view_name;
        } else {
          $view_path = 'default';
        }
        $view = new View($view_path);
      }

      // call this->view load data
      // get all the data for the object
      $view_content = $this->get_view_content($view_name, $this->object);

      $this->data = $view_content;
      $this->view = $view;

    }

    /*
     * Returns the first object that was sent to create_view
     */
    public static function initial_object()
    {
      return self::$initial_object;
    }


    public function create_virtual_view($view_name)
    {

      // check for a virtual object specified in frontend.xml
      // a virtual object will be one that does not match a object_type

      if (file_exists('application/views/frontend/' . $view_name . '.php'))
      {
        $view_path = 'frontend/'.$view_name;
      } elseif (file_exists('application/views/generated/' . $view_name . '.php'))
      {
        $view_path = 'generated/'.$view_name;
      } else {
        $view_path = 'default';
      }
      $view = new View($view_path);

      // call this->view load data
      // get all the data for the object
      $view_content = $this->get_view_content($view_name);
      foreach ($view_content as $key => $value)
      {
        $view->$key = $value;
      }

      return $view;

    }

    public function get_view_content($view, $slug=NULL)
    {

      if (( ! $view OR $view=='') AND (!$slug || $slug==''))
      {
        throw new Kohana_Exception('get_view_content called with NULL parameters');
      }

      $data = array();

      $object = NULL;
      $object_id = NULL;
      if  ($slug)
      {

        if ( ! is_object($slug))
        {
          $object = Graph::object($slug);
        } else {
          $object = $slug;
        }
      }
      if ($object)
      {
        $object_id = $object->id;
      }

      if ($view == 'default')
      {
        if ( ! $object->loaded())
        {
          throw new Kohana_Exception('core_views::get_view_content : Default view callled with no slug');
        }
        $data['content']['main'] = $object->get_page_content();
        return $data;
      }

      $view_config = core_lattice::config('frontend', "//view[@name=\"$view\"]")->item(0);
      if ( ! $view_config)
      {
        //  throw new Kohana_Exception("No View setup in frontend.xml by that name: $view");
        //  we are allowing this so that objects automatically can have basic views
      }
      $loaded =  $object->loaded();
      if ($slug)
      {
        if ($object->loaded() != 1)
        {
          throw new Kohana_Exception('core_views::get_view_content : view called with slug: :slug, but no object to load',
            array(
              ':slug'=>$slug,
            )
          );
        }
      }
      if ($object)
      {
        $data['content']['main'] = $object->get_page_content();
      }

      $include_content = $this->get_include_content($view_config, $object_id);
      foreach ($include_content as $key => $values)
      {
        $data['content'][$key] = $values;
      }

      if ($sub_views = core_lattice::config('frontend', "subView", $view_config))
      {
        foreach ($sub_views as $subview)
        {
          $view = $subview->getAttribute('view');
          $slug = $subview->getAttribute('slug');
          $label = $subview->getAttribute('label');
          if (core_lattice::config('frontend', "//view[@name=\"$view\"]"))
          {

            if ($view AND $slug)
            {
              $sub_view_content = $this->get_view_content($view, $slug);
            } elseif ($slug)
            {
              $object = Graph::object($slug);
              $view = $object->objecttype->objecttypename;
              $sub_view_content = $this->get_view_content($view, $slug);
            } elseif ($view)
            {
              $sub_view_content = $this->get_view_content($view);
            } else {
              die("subview $label must have either view or slug");
            }
            $sub_view = new View($view);

            foreach ($sub_view_content as $key => $content)
            {
              $sub_view->$key = $content;
            }
            $data[$label] = $sub_view->render();
          } else {
            // assume it's a module
            $data[$label] = core_lattice::build_module(array('modulename' => $view/* , 'controllertype'=>'object' */), $subview->getAttribute('label'));
          }
        }
      }

      return $data;
    }

    public function get_include_content($include_tier, $parent_id)
    {
      $content = array();
      if ($include_content_queries = core_lattice::config('frontend', 'includeData', $include_tier))
      {
        foreach ($include_content_queries as $include_content_query_params)
        {
          $query = new Graph_Objectquery();
          $query->init_with_xml($include_content_query_params);
          $include_content = $query->run($parent_id);

          for ($i = 0; $i < count($include_content); $i++)
          {
            $children = $this->get_include_content($include_content_query_params, $include_content[$i]['id']);
            $include_content[$i] = array_merge($include_content[$i], $children);
          }
          $content[$query->attributes['label']] = $include_content;

            /*
            if ($sort_by = $include_content_query_params->getAttribute('sort_by'))
{
              $sort_function = function($a, $b) use ($sort_by)
{
                $a = $a[$sort_by];
                $b = $b[$sort_by];
                if ($a == $b)
{
                  return 0;
                }
                return ($a < $b) ? -1 : 1;
              }

              uasort($content, $sort_function);

            }
             */
        }
      }
      return $content;
    }


  }

