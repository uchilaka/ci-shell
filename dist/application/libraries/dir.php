<?php
//define("DDS", DIRECTORY_SEPARATOR);

class Dir {
    //put your code here
    //put your code here
    public $imageFileType = array(
        1=>".gif",
        2=>".jpg",
        3=>".png",
        6=>".bmp"
    );
    
    private $shortFormats = array(
        /*'Folders'=>"application/vnd.google-apps.folder",*/
        'image/png'=>'png',
        'image/jpeg'=>'jpg',
        'application/vnd.ms-powerpoint'=>'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx'
    );
    
    const IMAGE_HIGH_RES = "1200:800";
    const IMAGE_THUMB = "200:300";
    
    public $components = array('Image');
    
    /** @TODO use to govern the root location images / files get saved to **/
    public $imageRoot = "";
    public $fileRoot = "";

    static function mime_type($file) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime= finfo_file($fi, $file);
        finfo_close($fi);
        return $mime;
    }
    
    static function make_file ($path,$name,$stuff) {
    $path = $path;
    $name = $path.$name;

    if (!file_exists($path)) {
    $md = mkdir($path, 0777);
    }

    $x=func_num_args();
            if ($x>3) {
            $mode=func_get_arg(2);
                    if ($mode==0) {
                    $k=@file_put_contents($name,$stuff);
                    return $k;
                    }
            } else {
            $try =  file_put_contents($name,$stuff);
            }
    //echo $try;

    if ($try !== FALSE) { return $name;}

    else {return 0;}
    }


    function make_log($log) {
    $x = 0;
    $text = "";
    while($x<count($log)) {$text.=$log[$x].'
    '; ++$x;}
    return $text;
    }

/*
    function get_all_files($dir) {

    $x=0;

    if ($handle = opendir($dir)) {
       while (false !== ($file = readdir($handle))) {
           if ($file != "." && $file != "..") {
                       $files[$x] = $file; $types[$x] = filetype($dir.$file);
                       ++$x;
           }
       }
       closedir($handle);
    }

    $output[0] = $files;
    $output[1] = $types;

    return $output;
    }
*/

    static function scratch_file ($file) {
    $x = filetype($file);
    if ($x == 'dir') {return 0;}
    else {
    return unlink($file);
    }
    }


    static function remove_dir($dir) {
    $dir_contents = scandir($dir);
    foreach ($dir_contents as $item) {
    if (is_dir($dir.$item) && $item != '.' && $item != '..') {
    remove_dir($dir.$item.DIRECTORY_SEPARATOR);
    }
    elseif (file_exists($dir.$item) && $item != '.' && $item != '..') {
    unlink($dir.$item);
    }
    }
    return rmdir($dir);
    }


    static function trailblazer ($trail) {
    $set = explode(DIRECTORY_SEPARATOR,$trail);
    $dir = "";
    foreach ($set as $x) 
    {
    $dir .= $x.DIRECTORY_SEPARATOR; if (!@is_dir($dir)) {@mkdir($dir,0777);}
    }
    return @is_dir($dir);
    }

    static function copy_dir($srcdir, $dstdir, $verbose) {
     $num = 0;
     if (!file_exists($srcdir)) {if ($verbose) {echo 'The source directory does not exist.';} return 0;}
     $dirpath = explode(DIRECTORY_SEPARATOR,$dstdir);
     for ($x = 0; $x < count($dirpath); $x++) 
     {
     $path.=$dirpath[$x].DIRECTORY_SEPARATOR; if (!file_exists($path)) {mkdir($path,0777); if ($verbose) {echo 'dir created: '.$path.'<br/>';} }
     }

    // if(!file_exists($dstdir)) mkdir($dstdir);
     if($curdir = opendir($srcdir)) {
       while($file = readdir($curdir)) {
         if($file != FALSE && $file != '.' && $file != '..') {
           $srcfile = $srcdir . DIRECTORY_SEPARATOR . $file;
           $dstfile = $dstdir . DIRECTORY_SEPARATOR . $file;
           if(is_file($srcfile)) {
             if(is_file($dstfile)) $ow = filemtime($srcfile) - filemtime($dstfile); else $ow = 1;
             if($ow > 0) {
               if($verbose) echo "Copying '$srcfile' to '$dstfile'...";
               if(copy($srcfile, $dstfile)) {
                 touch($dstfile, filemtime($srcfile)); $num++;
                 if($verbose) echo "OK\n";
               }
               else echo "Error: File '$srcfile' could not be copied!\n";
             }                   
           }
           else if(is_dir($srcfile)) {
             $num += copy_dir($srcfile, $dstfile, $verbose);
           }
         }
       }
       closedir($curdir);
     }
     return $num;
    }


    function csv_to_array($input, $delimiter=',') 
    { 
        $header = null; 
        $data = array(); 
        $csvData = str_getcsv($input, "\n"); 

        foreach($csvData as $csvLine){
            // make sure line is not a comment line
            if (preg_match('/^#/', $csvLine)<1 || !preg_match('/^#/', $csvLine)) {
                if(is_null($header)) $header = explode($delimiter, $csvLine); 
                else{ 

                    $items = explode($delimiter, $csvLine); 

                    for($n = 0, $m = count($header); $n < $m; $n++){ 
                        $prepareData[$header[$n]] = $items[$n]; 
                    } 

                    $data['values'][] = $prepareData; 
                }
            }

        } 

        $data['header']=$header;
        return $data; 
    }
    
    function getShortFormat($mime_type) {
        if(!empty($this->shortFormats[$mime_type])):
            return $this->shortFormats[$mime_type];
        else:
            return $mime_type;
        endif;
    }
    
    function unzip($src_file, $dest_dir) {
        if(!preg_match("/\/$/", $dest_dir)):
            $dest_dir = $dest_dir . DIRECTORY_SEPARATOR;
        endif;
        $files = array();
        $errors = array();
        $zip = zip_open($src_file);
        if (is_resource($zip)) {
            $this->trailblazer($dest_dir);
            while ($zip_entry = zip_read($zip)) {
                $fn = $dest_dir.zip_entry_name($zip_entry);
                $fp = fopen($fn, "w");
                if (zip_entry_open($zip, $zip_entry, "r")) {
                      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                      try {
                          fwrite($fp,"$buf");
                          zip_entry_close($zip_entry);
                          fclose($fp);
                          $files[] = $fn;
                      } catch (Exception $ex) {
                          $errors[] = $fn;
                      }
                }
        }
        zip_close($zip);
        } else {
            return false;
        }
        return array('output'=>$files, 'error'=>$errors);
    }
    
    static function file_type($fn) {
        $parts = explode(".", $fn);
        return array_pop($parts);
    }
    
    static function file_name($fn, $delim=null) {
        if(empty($delim)) {
            $delim = DIRECTORY_SEPARATOR;
        }
        $parts = explode($delim, $fn);
        return array_pop($parts);
    }
    
    static function get_url($svr_uri, $base_url=null) {
        if(empty($base_url)) {
            $url = str_replace(DIRECTORY_SEPARATOR, "/", str_replace(FCPATH, base_url(), $svr_uri));
        } else {
            $url = str_replace(DIRECTORY_SEPARATOR, "/", str_replace(FCPATH, $base_url, $svr_uri));
        }
        return preg_replace("/^http(s)?\:/", "", $url, 1);
    }
    
    static function get_server_uri($url) {
        if(preg_match("/^http(s)?/", $url)) {
            return str_replace("/", DIRECTORY_SEPARATOR, str_replace(base_url(), FCPATH, $url));
        } else {
            $base_url = preg_replace("/^http(s)?\:/", "", base_url(), 1);
            return str_replace("/", DIRECTORY_SEPARATOR, str_replace($base_url, FCPATH, $url));
        }
    }

}
