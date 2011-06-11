<?php

Class MOP_Initializer {

   public static function check($dependencies) {
      try {
         ORM::Factory('initializedmodule');
      } catch (Exception $e) {
         if ($e->getCode() == 1146) { //code for table doesn't exist
            //install the initializedmodules table
            $sqlFile = Kohana::find_file('config', 'initializedmodules', $ext = 'sql');
            $sql = file_get_contents($sqlFile[0]);
            mysql_query($sql);
         }
      }

      $allProblems =  array();
       
      foreach ($dependencies as $dependency) {
         $check = ORM::factory('initializedmodule')
                 ->where('module', '=', $dependency)
                 ->find();
         if (!$check->loaded() || $check->status != 'INITIALIZED') {
            if (Kohana::find_file('classes/initializer', $dependency)) {
               $initializerClass = 'initializer_' . $dependency;
               $initializer = new $initializerClass();
               $problems = $initializer->initialize();
               if (count($problems) == 0) {
                  if (!$check->loaded()) {
                     $check = ORM::Factory('initializedmodule');
                  }
                  $check->module = $dependency;
                  $check->status = 'INITIALIZED';
                  $check->save();
               } else {
                 $allProblems = array_merge($allProblems, $problems);
               }
            }
         }
      }
      
      if(count($allProblems)){
         $view = new View('initializationproblems');
         $view->problems = $allProblems;
         echo $view->render();
         exit;
      }
   }

}