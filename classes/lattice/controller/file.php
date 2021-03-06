<?php
class Lattice_Controller_File extends Controller{

  public function __construct($request, $response)
  {
    parent::__construct($request, $response);
  }

  public function action_download($file_id = null)
  {
		if($file_id == null){
			return;
		}
    $file = Graph::file($file_id);

    // check access
    // don't have object wise access checking at this point

    $filename = Graph::mediapath().$file->filename;
    $ctype = $file->mime;

    if ( ! file_exists($filename))
    {
      throw new Kohana_Exception("NO FILE HERE");
    }

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",FALSE);
    header("Content-Type: $ctype");
    header("Content-Disposition: attachment; filename=\"".basename($filename)."\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".@filesize($filename));
    set_time_limit(0);
    if ( ! @readfile("$filename") )
    {
      throw new Kohana_Exception("File not found.");	
    }
    exit;
  }


  public function action_directlink($file_id)
  {
    $file = Graph::file($file_id);

    $filename = Graph::mediapath().$file->filename;
    $ctype = $file->mime;

    if ( ! file_exists($filename))
    {
      throw new Kohana_Exception("NO FILE HERE");
    }

    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",FALSE);
    header("Content-Type: $ctype");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".@filesize($filename));
    header("Content-Disposition: inline;  filename=\"".basename($filename)."\";");
    set_time_limit(0);
    if ( ! @readfile("$filename") )
    {
      throw new Kohana_Exception("File not found.");	
    }
    exit;
  }

}


