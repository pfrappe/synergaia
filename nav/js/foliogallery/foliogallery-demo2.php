<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Image Gallery By FolioPages.com</title>
<style type="text/css">
body {
background:#eee;
margin:0;
padding:0;
font:12px arial, Helvetica, sans-serif;
color:#222;
}
</style>
<link rel="stylesheet" type="text/css" href="foliogallery/foliogallery.css" />
<link rel="stylesheet" type="text/css" href="colorbox/colorbox.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script type="text/javascript" src="colorbox/jquery.colorbox-min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    // initiate colorbox
	$(".albumpix").colorbox({rel:'albumpix', maxWidth:'98%', maxHeight:'98%', slideshow:true, slideshowSpeed:3500, slideshowAuto:false});
});	
</script>
</head>
<body>

<br />
<br />

<!-- display images located in "Lake Tahoe" subfolder and paginate in 3 item blocks -->
<?php $_REQUEST['fullalbum']=1; include 'foliogallery.php'; ?>

<br />
<br />

<div align="center">folioGallery - Installation and instructions @ <a href="http://foliopages.com/php-jquery-ajax-photo-gallery-no-database">FolioPages.com</a></div>

<br />
<br />


</body>
</html>