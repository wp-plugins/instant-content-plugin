<?php
/*
Plugin Name: Instant Content Plugin
Plugin URI: http://www.BlogsEye.com
Description: Loads content pages from a zipped collection of text files. 
Version: 1.0
Author: Keith P. Graham
Author URI: http://www.BlogsEye.com/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
// personal plugin to load content into pages

  
/************************************************************
*	kpg_Instant_Content_Plugin_admin_menu()
*	Adds the admin menu
*************************************************************/
function kpg_Instant_Content_Plugin_dopostings_menu() {
	add_pages_page('Instant Page Content', 'Instant Page Content', 'manage_options','InstantPages', 'kpg_add_Content_pages_control');
	add_posts_page('Instant Post Content', 'Instant Post Content', 'manage_options','InstantPosts', 'kpg_add_Content_posts_control');
}


// add the the options to the admin menu
add_action('admin_menu', 'kpg_Instant_Content_Plugin_dopostings_menu');

// uninstall routines

function kpg_Instant_Content_Plugin_uninstall() {
	if(!current_user_can('manage_options')) {
		die('Access Denied');
	}
	delete_option('kpg_Instant_Content_Plugin_options'); 
	return;
}
if ( function_exists('register_uninstall_hook') ) {
	register_uninstall_hook(__FILE__, 'kpg_Instant_Content_Plugin_uninstall');
}

// Add instant page logic (posts logic down below this)
function kpg_add_Content_pages_control() {
	// just a quick check to keep out the riff-raff
	if(!current_user_can('manage_options')) {
		die('Access Denied');
	}
	// look for parameters
	// we need to know the name of the file to post as content
	// we do not need to store these as options
	
	$kg_file="";
	$kg_parent_id=1;
	$kg_create_parent="";
	$new_page=0;
	if (array_key_exists('kg_file',$_POST)) $kg_file=$_POST['kg_file'];
	if (array_key_exists('kg_parent_id',$_POST)) $kg_parent_id=$_POST['kg_parent_id'];
	if (array_key_exists('kg_create_parent',$_POST)) $kg_create_parent=$_POST['kg_create_parent'];
	
	// if there is something in $kg_file then we do something
	if(!empty($kg_file)) {
		// see if we need to create a parent page on the fly
		if (!empty($kg_create_parent)) {
			// create a root level 
			$post = array( 
				'post_content' => ' ', //The full text of the post.
				'post_excerpt' => ' ', //For all your post excerpt needs.
				'post_status' =>'publish', //Set the status of the new post. 
				'post_title' => $kg_create_parent, //The title of your post.
				'post_type' => 'page', //Sometimes you might want to post a page - or custom type.
				'post_parent' => $kg_parent_id
			); 
			$kg_parent_id=wp_insert_post($post);
		}
		//open the file, read the content, add the posts
		// open the zip file
		$dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$zipfile=$dir.'/'.$kg_file;
		
		//zip read
		// first pass to find the title.txt file that has the category in it
		$posts=array();
		$cat='';
		
		$cat=getZip($zipfile,"title");
		$zip=getZip($zipfile,'*');
		
		for ($j=0;$j<count($zip);$j++) {
			$name= trim($zip[$j]);
			$buf = getZip($zipfile,$name);
			// $buff has the data - first line is the title of the post
			// second line is the content
			if ($name=='ind') {
				// igore for now - used in complex data files
			} else if ($name=='title'||$name=='title.txt') {
				// alreaddy have it
			} else if (strlen($name)>1) {
				// $buf has the post - split it 
				$postdata=explode("\n",$buf);
				$ptitle=trim($postdata[0]);
				unset($postdata[0]);
				$pbody=implode("\n",$postdata);
				//echo "found file first line=".$ptitle." length of array=".count($postdata)."<br/>";
				$post = array( 
					//'post_category' => array(), //Add some categories. an array()???
					'post_content' => $pbody, //The full text of the post.
					//'post_date' => $ddate, //[ Y-m-d H:i:s ] //The time post was made.
					'post_excerpt' => $pbody, //For all your post excerpt needs.
					//'post_name' =>$media_title, // The name (slug) for your post changed from $id
					'post_status' =>'publish', //Set the status of the new post. 
					'post_title' => $ptitle, //The title of your post.
					'post_type' => 'page', //Eventually I might want to post a page - or custom type.
					'post_parent' => $kg_parent_id
				); 
				$posts[count($posts)]=$post;
			}
		}
		// fix up all the posts with the cat (usually last item in list)
		// and do the inserts
		// check to see if the category exists
		$parent_content="<h3>$cat</h3>";
		for ($j=0;$j<count($posts);$j++) {
			$post=$posts[$j];
			$newpost=wp_insert_post($post);
			// need to get a link to this guy in order to add it to the created parent page
			$parent_content.='<a href="'.get_permalink($newpost).'">'.$post['post_title'].'</a><br/>';
			
			echo "Added: ".$post['post_title']."<br/>";
		}
		// now update the parent content if needed
		if (!empty($kg_create_parent)) {
			$pp=get_post($kg_parent_id,ARRAY_A);
			$pp['post_content']=$parent_content;
			wp_update_post($pp);
		}
		
	}
	
	// now we can create a page to enter the data
	
	// html here
	
	
	?>
<h2>Load Page Content Files </h2>
<form method="post" action="" name="DOIT3" >
<input type="hidden" name="action" value="update" />
<input type="hidden" name="ac_add_action" id="ac_add_action" value="" />
<fieldset style="border:thin black solid;padding:2px;"><legend>Loadable Data Files:</legend>	
 <select name="kg_file">
<?php
// list the zip files in the directory

		$dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$dh='';
		 if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($dir .'/'. $file)) {
				} else if ( strpos($file,'.zip')>0 ) {
					echo "<option value=\"$file\" >$file</option>";
				} else {
					//echo "can't find .php in $file <br/>";
				}
			}
			closedir($dh);
		}
		
	?>		
</select>
 <br />
(<em>Pick the zip file that you want to load.</em>) 	
</fieldset>
<br/>
<fieldset style="border:thin black solid;padding:2px;"><legend>Attach to Parent Page</legend>	

<?php wp_dropdown_pages(array(
    'depth'            => 0,
    'child_of'         => 0,
    'selected'         => 0,
    'echo'             => 1,
    'name'             => 'kg_parent_id',
	'show_option_none' => 'None'
		)); ?>
<br/> 
(<em>Choose a parent page for your new pages. If you create a new parent below, its parent will be this page.</em>) 
</fieldset>

 <br/>
 
<fieldset style="border:thin black solid;padding:2px;"><legend>Create new Parent</legend>	
 
 <input name="kg_create_parent" type="text" size="64" /> 
 <br />
 (<em>Enter name of new parent page to create if needed, or leave blank. Created pages will use this new page as a parent, otherwise the page from the list above</em>) 
</fieldset>	
	
 <p class="submit">
<input type="submit" name="kg_sub1" class="button-primary" value="Add Instant Content" />
</p>
</form>	
	
	
	<?PHP
}
function kpg_add_Content_posts_control() {
	// just a quick check to keep out the riff-raff
	if(!current_user_can('manage_options')) {
		die('Access Denied');
	}
	// look for parameters
	// we need to know the name of the file to post as content
	// Posts need a date. The date can be generated based on start date and frequency
	
	$kg_file=""; // the posts file to load
	$kg_startdate=""; // when to begin posting
	$kg_freq=""; // How often to do posts
	$kg_order="";
	if (array_key_exists('kg_file',$_POST)) $kg_file=$_POST['kg_file'];
	if (array_key_exists('kg_startdate',$_POST)) $kg_startdate=$_POST['kg_startdate'];
	if (array_key_exists('kg_freq',$_POST)) $kg_freq=$_POST['kg_freq'];
	if (array_key_exists('kg_order',$_POST)) $kg_order=$_POST['kg_order'];
	
	// if there is something in $kg_file then we do something
	if(!empty($kg_file)) {
		//open the file, read the content, add the posts
		// open the zip file
		$dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$zipfile=$dir.'/'.$kg_file;
		
		//zip read
		// first pass to find the title.txt file that has the category in it
		$cat='';
		$cat=trim(getZip($zipfile,"title"));
		// check to see if we need to create this as a category
		$cat_id='';
		if (!empty($cat)) {
			if (!get_cat_ID( $cat )) {
				// insert category
				$cat_id=wp_create_category( $cat );
			}
			$cat_id=get_cat_ID( $cat );
		}
		// start with a date
		if (!empty($kg_startdate)) {
			$sdate=strtotime($kg_startdate);
		} else {
			$sdate=time();
		}
		// get the adder for how often we need to insert
		if (empty($kg_freq)) {
			$kg_freq=0;
		}
		// now figure how much to add
		$freq=0;
		switch($kg_freq) {
			case '0':
				$freq=0;
				break;
			case 'D':
				$freq=24 * 60 * 60;
				break;
			case 'DD':
				$freq=2*24 * 60 * 60;
				break;
			case 'W':
				$freq=7*24 * 60 * 60;
				break;
			case 'F':
				$freq=14*24 * 60 * 60;
				break;
			case '30':
				$freq=30*24 * 60 * 60;
				break;
			default;
				$freq=60*60; // just to be perverse
		}
		
		$zip=getZip($zipfile,'*');
		if ($kg_order=="R") {
			shuffle($zip);
		}
		$sdate=$sdate-$freq; // inelegant hack - but that's me all over.
		for ($j=0;$j<count($zip);$j++) {
			$name= trim($zip[$j]);
			$buf = getZip($zipfile,$name);
			// $buff has the data - first line is the title of the post
			// second line is the content
			if ($name=='ind') {
				// igore for now - used in complex data files
			} else if ($name=='title') {
				// alreaddy have it
			} else if (strlen($name)>1) {
				// $buf has the post - split it 
				$postdata=explode("\n",$buf);
				$ptitle=trim($postdata[0]);
				unset($postdata[0]);
				$pbody=implode("\n",$postdata);
				//echo "found file first line=".$ptitle." length of array=".count($postdata)."<br/>";
				$sdate+=$freq;
				$ddate=date('Y-m-d H:i:s',$sdate);
				$post = array( 
					'post_category' => array($cat_id), //Add some categories. an array()???
					'post_content' => $pbody, //The full text of the post.
					'post_date' => $ddate, //[ Y-m-d H:i:s ] //The time post was made.
					'post_excerpt' => $pbody, //For all your post excerpt needs.
					//'post_name' =>$media_title, // The name (slug) for your post changed from $id
					'post_status' =>'publish', //Set the status of the new post. 
					'post_title' => $ptitle, //The title of your post.
					'post_type' => 'post' // this could have been post or even a custom post type
					//'post_parent' => $kg_parent_id
				); 
				$newpost=wp_insert_post($post);
				echo "Added: ".$post['post_title']."<br/>";
			}
		}
		
	}
	// html here
	
	
	?>
<h2>Load Post Content Files</h2>
<form method="post" action="" name="DOIT3" >
<input type="hidden" name="action" value="update" />
<input type="hidden" name="ac_add_action" id="ac_add_action" value="" />
<fieldset style="border:thin black solid;padding:2px;"><legend>Loadable Data Files:</legend>	
 <select name="kg_file">
<?php
// list the zip files in the directory
		


		$dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$dh='';
		 if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($dir .'/'. $file)) {
				} else if ( strpos($file,'.zip')>0 ) {
					echo "<option value=\"$file\" >$file</option>";
				} else {
					//echo "can't find .php in $file <br/>";
				}
			}
			closedir($dh);
		}
		
	?>		
</select>	
 <br />
(<em>Pick the zip file that you want to load.</em>) 
</fieldset>
<br/>
<fieldset style="border:thin black solid;padding:2px;">
<legend>Enter Start Date/Time:</legend>	
<input name="kg_startdate" type="text" size="64" /> 
<br />
(<em>most any date time format such as July 4, 2020 8:00AM - this is not validated so be careful!</em>)<br/>
</fieldset><fieldset style="border:thin black solid;padding:2px;">
<legend>Randomize Orderof Posts:</legend>	

Random:<input type="radio" name="kg_order" value="R" checked="true" /><br/>
Alphabetic:<input type="radio" name="kg_order" value="A" />
<br />
(<em>Most archives are in Alphabetical Order. Use this to mix up the order. </em>)
</fieldset>

 <br/>
 
<fieldset style="border:thin black solid;padding:2px;">
<legend>Frequency of future postings: </legend>	
 <select name="kg_freq">
 	<option value="0">All at once</option>
 	<option value="30">Every 30 days</option>
 	<option value="F">Every Fortnight</option>
 	<option value="W">Weekly</option>
 	<option value="DD">Every Other Day</option>
 	<option value="D">Daily</option>
 	<option value="0">All at once</option>
 </select>
 <br />
 (<em>How often do you want future posts to appear.</em>)
 </fieldset>	
	

 <p class="submit">
<input type="submit" name="kg_sub2" class="button-primary" value="Add Instant Content" />
</p>
</form>	
	
	
	<?PHP
}

// get zip for hostgator and others that don't know how to do zips
function getZip($zip,$zfile) {
// get the size of the file
$fsize=-1;
$zfiles=array(); // used when the $zfile is '*'
if (file_exists($zip)) {
	$fsize=filesize($zip);
} 
if ($fsize<64) {
	// I'm guessing this is not a zip
	return "$zip not a zip: $fsize";
}
// zip signature

// open the file and seek to the end of file
$fp = fopen($zip, 'rb');
fseek($fp, $fsize-2048);
// read the last 2048 bytes 
$hd=fread($fp,2048);
// put the string into an array of bytes
$bindata=array_merge(unpack("C*",$hd));
// no check for the n-22 byte
$goodhead=false;
$central=-1;
for ($j=2048-22;$j>=0;$j--) {
	if (chr($bindata[$j])=="P" && chr($bindata[$j+1])=="K" && $bindata[$j+2]==5 && $bindata[$j+3]==6) {
		$goodhead=true;
		$central=$j;
		break;
	} 
}
if (!$goodhead) {
	fclose($fp);
	return "not a zip file";
}
// got the header
$dircount=$bindata[$j+10]+($bindata[$j+11]*256);
$dirseek=$bindata[$j+16]+($bindata[$j+17]*256)+($bindata[$j+18]*256*256)+($bindata[$j+19]*256*256*256);
// seek to central
fseek($fp, $dirseek);
$z2="";

for ($j=0;$j<$dircount;$j++) {
	$hd=fread($fp,46);
	$bindata=array_merge(unpack("C*",$hd));	// this is a directory record
	// check for the pk
	if (chr($bindata[0])!="P" || chr($bindata[1])!="K" || $bindata[2]!=1 || $bindata[3]!=2) {
		fclose($fp);
		return "<br>bad file: malformed directory record";
	}
	// the filename is at the end. the length of the file name is at offset 28
    $fnlen=$bindata[28]+($bindata[29]*256);
	$tname=fread($fp,$fnlen);
	//echo "<br>filename = $tname";
	// get the other data
    $elen=$bindata[30]+($bindata[31]*256)+$bindata[32]+($bindata[33]*256);
	$compmethod=$bindata[10]+($bindata[11]*256);
	if ($elen>0) {
		$ename=fread($fp,$elen);
	}
	// if ($tname==$zfile) { // fix to case compare
	if ($zfile=='*') {
		// add the tname to the array
		$zfiles[count($zfiles)]=$tname;
	} else if (strcasecmp($tname, $zfile) == 0) {
		// now we need to seek to the file entry in the zip
		$seeker=$bindata[42]+($bindata[43]*256)+($bindata[44]*256*256)+($bindata[45]*256*256*256);
		fseek($fp, $seeker);
		$hd=fread($fp,30);
		$sk+=30;
		$bindata=array_merge(unpack("C*",$hd));
	    $hd=chr($bindata[0]);
		if (chr($bindata[0])!="P"||chr($bindata[1])!="K") {
			fclose($fp);
			//echo "<br> - not found - not a header";
			return("<br>bad file: malformed file record");
		}
		// now lets get the file name and extra name
		$fnlen=$bindata[26]+($bindata[27]*256);
		$filename=fread($fp,$fnlen);
		//echo $filename."<br>";
		$sk+=$fnlen;
        $elen=$bindata[28]+($bindata[29]*256);
		if ($elen>0) {
			$sk+=$elen;
			$etra=fread($fp,$elen); // throw away for now
		}
		// check the file name
		$clen=$bindata[18]+($bindata[19]*256)+$bindata[20]*256*256+($bindata[21]*256*256*256);
		if ($filename==$zfile) {
			// return the uncompressed data from the zip
			$cdata=fread($fp,$clen);
			//compression method is 0 it's stored otherwise deflate
			if ($compmethod==0) {
				$udata=$cdata;
			} else {
			   $udata=gzinflate($cdata);
			}
			fclose($fp);
			if (substr($zfile,0,1)=='z') {
				return($udata.$z2);
			} else {
				return($udata);
			}
		}
		// There was no hit on filename it lived in the index but bad seek??
		fclose($fp);
		return "<br>File not found";
	}
	// try next entry
}	
	fclose($fp);
	// if we hit here there is a no match on file name - so return nothing!!
	// this allows us to exit gracefully when the read is bad.
	if (count($zfiles)>0) return $zfiles;
	return "";

}



?>