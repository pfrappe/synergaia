<!--
	folioGallery v1.2 - 2014-02-01
	(c) 2014 Harry Ghazanian - foliopages.com/php-jquery-ajax-photo-gallery-no-database
	This content is released under the http://www.opensource.org/licenses/mit-license.php MIT License.
-->
<?php
/***** gallery settings *****/
$mainFolder   = 'albums'; // main folder that holds subfolders - this folder resides on root directory of your domain
$no_thumb     = 'foliogallery/noimg.png';  // show this when no thumbnail exists 
$extensions   = array(".jpg",".png",".gif",".JPG",".PNG",".GIF"); // allowed extensions in photo gallery 
$itemsPerPage = '12';    // number of images per page if not already specified in ajax mode 
$thumb_width  = '150';   // width of thumbnails
$sort_by      = 'date';  // 'date' will sort albums by upload date -  change 'date' to anything else to sort by album name 
$show_caption = 'yes';   // 'yes' will display file names as captions on each thumb inside albums
/***** end gallery settings *****/

$numPerPage = (!empty($_REQUEST['numperpage']) ? (int)$_REQUEST['numperpage'] : $itemsPerPage);
$fullAlbum  = (!empty($_REQUEST['fullalbum']) ? 1 : 0);

// function to create thumbnails from images
function make_thumb($folder,$src,$dest,$thumb_width) {

	$ext = strrchr($src, '.');
	$ext = strtolower($ext);
	
	switch($ext)
	{
		case ".jpeg":
		$source_image = imagecreatefromjpeg($folder.'/'.$src);
		break;
		
		case ".jpg":
		$source_image = imagecreatefromjpeg($folder.'/'.$src);
		break;
		
		case ".png":
		$source_image = imagecreatefrompng($folder.'/'.$src);
		break;
		
		case ".gif":
		$source_image = imagecreatefromgif($folder.'/'.$src);
		break;
	}	
	
	$width = imagesx($source_image);
	$height = imagesy($source_image);
	$thumb_height = floor($height*($thumb_width/$width));
	
	$virtual_image = imagecreatetruecolor($thumb_width,$thumb_height);
	
	imagecopyresampled($virtual_image,$source_image,0,0,0,0,$thumb_width,$thumb_height,$width,$height);
	
	imagejpeg($virtual_image,$dest,100);
	imagedestroy($virtual_image); 
	imagedestroy($source_image);
	
}

// display pagination
function paginateAlbum($numPages,$urlVars,$alb,$currentPage) {
        
   $html = '';
   
   if ($numPages > 1) 
   {
      
	   $html .= 'Page '.$currentPage.' of '.$numPages;
	   $html .= '&nbsp;&nbsp;&nbsp;';
   
       if ($currentPage > 1)
	   {
	       $prevPage = $currentPage - 1;
	       $html .= '<a class="pag" rel="'.$alb.'" rev="'.$prevPage.'" href="?'.$urlVars.'p='.$prevPage.'">&laquo;&laquo;</a> ';
	   }	   
	   
	   for( $i=0; $i < $numPages; $i++ )
	   {
           $p = $i + 1;
       
	       if ($p == $currentPage) 
		   {	    
		       $class = 'current-paginate';
	       } 
		   else 
		   {
	           $class = 'paginate';
	       } 
	       
		   $html .= '<a rel="'.$alb.'" rev="'.$p.'" class="'.$class.' pag" href="?'.$urlVars.'p='.$p.'">'.$p.'</a>';	  
	   }
	   
	   if ($currentPage != $numPages)
	   {
           $nextPage = $currentPage + 1;	
		   $html .= ' <a class="pag" rel="'.$alb.'" rev="'.$nextPage.'" href="?'.$urlVars.'p='.$nextPage.'">&raquo;&raquo;</a>';
	   }	  	 
   
   }
   
   return $html;

}
?>

<div class="fg">

<?php
// if no album is selected show all albums
if (empty($_REQUEST['album'])) 
{	
	$ignore  = array('.', '..', 'thumbs');
	$albums = array();
	$captions = array();
	$random_pics = array();
	
	if($sort_by == 'date') 
	{	
		$folders = array_diff(scandir($mainFolder), array('..', '.'));
		$sort_folders = array();
	
		foreach ($folders as $key=>$folder) 
		{
			$stat_folders = stat($mainFolder .'/'. $folder);
			$folder_time[$key] = $stat_folders['ctime'];
		}
		array_multisort($folder_time, SORT_DESC, $folders); 
	
	} 
	else 
	{
		$folders = scandir($mainFolder, 0);
	}

	foreach ($folders as $album)
	{	    
			    
		if(!in_array($album, $ignore))
		{ 
			array_push($albums, $album);	 
			
			$caption = $album;
		    //$caption = substr($album,0,20); // show only the 1st 20 characters
			array_push($captions, $caption);
			
		    $rand_dirs = glob($mainFolder.'/'.$album.'/thumbs/*.*', GLOB_NOSORT);
            if (count($rand_dirs) > 0)
			{  
			   $rand_pic = $rand_dirs[array_rand($rand_dirs)]; // display random thumb for each album
			   //$rand_pic = $rand_dirs[0]; // display the first thumb of each album 
			} 
			else
			{
			   $rand_pic = $no_thumb;
			}
			array_push($random_pics, $rand_pic); 		 
		 }
		  
	 }
	 
     $numAlbums = count($albums); // number of albums
	 
	 if( $numAlbums == 0 ) 
	 {
		 echo 'There are currently no albums.';     
     }
	 else
	 {
		 $numPages = ceil( $numAlbums / $numPerPage );

         if(isset($_REQUEST['p']))
		 {
	         $currentPage = (int)$_REQUEST['p'];
             if($currentPage > $numPages)
			 {
                 $currentPage = $numPages;
             }
         } 
		 else 
		 {
            $currentPage=1;
         } 
 
		 $start = ($currentPage * $numPerPage) - $numPerPage;
	     ?>
	     
		 <div class="titlebar">
             <span class="title">Photo Gallery</span> - <?php echo $numAlbums; ?> albums
         </div>
	  
         <div class="clear"></div>
	  	 
		 <?php 			 
	     for( $i=$start; $i<$start + $numPerPage; $i++ ) 
		 {
	  						
			if( isset($albums[$i]) ) 
			{ ?>
			 		 			 
				<div class="thumb-wrapper">
					<div class="thumb">
					   <a class="showAlb" rel="<?php echo $albums[$i]; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?album=<?php echo urlencode($albums[$i]); ?>">
					     <img src="<?php echo $random_pics[$i]; ?>" width="<?php echo $thumb_width; ?>" alt="<?php echo $albums[$i]; ?>" /> 
					   </a>	
					</div>
					<div class="caption"><?php echo $captions[$i]; ?></div>
				</div>
			<?php	  
		    }		  	  

	     }
	     ?>
	      
		 <div class="clear"></div>
  
         <div align="center" class="paginate-wrapper">
        	<?php
			$urlVars = "";
			$alb = "";
            echo paginateAlbum($numPages,$urlVars,$alb,$currentPage);
			?>
         </div>   
    <?php
     }

} 
else 
{

     // display photos in album
     $src_folder = $mainFolder.'/'.$_REQUEST['album']; 
	 $src_files = array_diff(scandir($src_folder ), array('..', '.'));
	 $files = array();
	
	 /*** sort by most recent uploaded file ***/
	 foreach ($src_files as $key=>$img) 
	 {
		$stat_folders = stat($src_folder .'/'. $img);
		$file_time[$key] = $stat_folders['ctime'];
	 }
	 array_multisort($file_time, SORT_DESC, $src_files);
	 /*** end sort ***/

	foreach ($src_files as $file)
    {		
		
		$ext = strrchr($file, '.');
        if(in_array($ext, $extensions)) 
		{  
		    array_push( $files, $file );
		  
		    if (!is_dir($src_folder.'/thumbs')) 
			{
               mkdir($src_folder.'/thumbs');
               chmod($src_folder.'/thumbs', 0777);
               //chown($src_folder.'/thumbs', 'apache'); 
            }
		   
		    $thumb = $src_folder.'/thumbs/'.$file;
            if (!file_exists($thumb))
			{
               make_thumb($src_folder,$file,$thumb,$thumb_width); 
		    }
        
		}

    }
 

    $numFiles = count($files); // number of images
   
    if ( $numFiles == 0 ) 
	{
		echo 'There are no photos in this album!';
    } 
	else
	{
      $numPages = ceil( $numFiles / $numPerPage );

      if(isset($_REQUEST['p']))
	  {
		  $currentPage = (int)$_REQUEST['p'];
          if($currentPage > $numPages)
		  {
              $currentPage = $numPages;
          }
      } 
	  else
	  {
         $currentPage=1;
      } 

	  $start = ($currentPage * $numPerPage) - $numPerPage; 	
	?>

	<div class="titlebar">
		<?php if($fullAlbum==1) { ?>
		    <span class="title"><a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="refresh">Albums</a></span>
			<span class="title">&raquo;</span>
		<?php } ?>
		<span class="title"><?php echo $_REQUEST['album']; ?></span> - <?php echo $numFiles; ?> images
   </div>  
   
   <div class="clear"></div>
	
    <?php 	
    for( $i=0; $i <= $numFiles; $i++ )
	{   
		if(isset($files[$i]) && is_file( $src_folder .'/'. $files[$i]))
		{   		    
			$ext = strrchr($files[$i], '.');
		    $caption = substr($files[$i], 0, -strlen($ext)); ?>		   
		   
		    <?php if($i<$start || $i>=$start + $numPerPage) { ?><div style="display:none;"><?php } ?>
		    <div class="thumb-wrapper">
				<div class="thumb">
					<a href="<?php echo $src_folder; ?>/<?php echo $files[$i]; ?>" title="<?php echo $caption; ?>" class="albumpix">
				       <img src="<?php echo $src_folder; ?>/thumbs/<?php echo $files[$i]; ?>" width="<?php echo $thumb_width; ?>" alt="<?php echo $files[$i]; ?>" />
				    </a>
			    </div> 
			    <?php if($show_caption=='yes') { ?><div class="caption"><?php echo $caption; ?></div><?php } ?> 
			</div>
			<?php if($i<$start || $i>=$start + $numPerPage) { ?></div><?php }
   
	    } 
	
	} ?> 

     <div class="clear"></div>
  
     <div align="center" class="paginate-wrapper">
        <?php	 
        $urlVars = "album=".urlencode($_REQUEST['album'])."&amp;";
		$alb = $_REQUEST['album'];
        echo paginateAlbum($numPages,$urlVars,$alb,$currentPage);
        ?>
     </div>
	 
   <?php	 
   } // end else	 

}
?>
</div>