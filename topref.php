<?php
/*
Plugin Name: Top Referrers
Version: 1.2.1
Plugin URI: http://www.seanbluestone.com/wordpress-top-referrers
Author: Dux0r
Author URI: http://www.seanbluestone.com
Description: Automatic top referral tracking script. Displays top referrers with hits in/out and several other stats. Fully automated, highly customizable.

Copyright 2008  Sean Bluestone  (http://www.seanbluestone.com)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


register_activation_hook(__FILE__, 'topref_install');
//register_deactivation_hook(__FILE__, 'topref_uninstall');
add_action('init','topref_gourl');
add_action('admin_menu','topref_menu');
add_shortcode('topref','topref_display_refs');


// The next 3 functions handle the Top Referrers widget

function topref_widget(){
	// This is Top Referrers widget and does a call to topref_display_refs() with the display and number options which were set in the widget controls box
	// I.e. topref_display_refs('Top',10);

	echo '<h2>'.get_option('topref_widget_title').'</h2>';
	topref_log_refs();
	topref_display_refs(get_option('topref_widget_display'),get_option('topref_widget_number'));
}

function topref_widget_control(){

	// Displays the control panel for the widget which are used in topref_widget() above

	if($_POST['topref_widget_submit']){
		update_option('topref_widget_title', $_POST['topref_widget_title']);
		update_option('topref_widget_display', $_POST['topref_widget_display']);
		update_option('topref_widget_number', $_POST['topref_widget_number']);
	}

	echo '<table class="form-table"><tr><td>
	<label for="topref_widget_title">Widget Title: </label>
	</td><td>
	<input type="text" name="topref_widget_title" value="'.get_option('topref_widget_title').'" />
	</td></tr>
	<tr><td>
	<label for="topref_widget_display">Display: </label>
	</td><td>
	<select name="topref_widget_display" value="'.get_option('topref_widget_display').'" />';

	$Current=get_option('topref_widget_display');

	foreach(array('Top','Last') as $Opt){
		echo '<option'.($Opt==$Current ? ' SELECTED' : '').'>'.$Opt.'</option>';
	}

	echo '</select>
	</td></tr>
	<tr><td>
	<label for="topref_widget_number">How Many: </label>
	</td><td>
	<input type="text" size="5" name="topref_widget_number" value="'.get_option('topref_widget_number').'" />
	</td></tr>
	</table>
	<input type="hidden" name="topref_widget_submit" value="1" />';
}

function init_topref_widget(){
	register_sidebar_widget("Top Referrers", "topref_widget");
	register_widget_control('Top Referrers', 'topref_widget_control');
}

add_action("plugins_loaded", "init_topref_widget");



function topref_menu(){
	add_menu_page('Top Referrers', 'Top Referrers', 8, __FILE__, 'toprefs_menu_options');
	add_submenu_page(__FILE__, 'Top 25 Referrers','Top 25 Referrers', 8, 'topref_menu_top', 'toprefs_menu_top');
	add_submenu_page(__FILE__, 'Last 50 Referrers','Last 50 Referrers', 8, 'topref_menu_last', 'toprefs_menu_last');
}


function toprefs_menu_top(){
	global $wpdb;
	$table=$wpdb->prefix.'topref';

	echo '<div class="wrap">';

	// If block button has been pressed, update block list and delete referrals from chosen URL
	if($_POST['Block']){
		$BlockMe=$_POST['url'];
		$Block=get_option('topref_block').'|'.str_replace('.','\.',$BlockMe);
		update_option('topref_block',$Block);
		mysql_query("DELETE FROM $table WHERE url LIKE '%$BlockMe%'");
		echo "<b>Deleted and blocked $BlockMe from your referrals.</b><br><br>";
	}

	$getTopIn=mysql_query("SELECT url,real_url,time,sum(hitsout) AS hitsout, COUNT(*) AS hitsin FROM $table GROUP BY url ORDER BY hitsin DESC LIMIT 25");
	$getTopOut=mysql_query("SELECT url,real_url,time,sum(hitsout) AS hitsout, COUNT(*) AS hitsin FROM $table GROUP BY url ORDER BY hitsout DESC LIMIT 25");

	echo 'Here are the top 25 referrers by hits in and hits out. The first set shows which sites are giving you the most traffic while the second set shows which are most popular with your own readers.<br><br>

	<h2>Top 25 Referrers by Hits In</h2>
	<table><tr><th></th><th>Display URL</th><th>Real URL</th><th>In</th><th>Out</th><th>Time</th><th></th></tr>';

	while($Ref=mysql_fetch_assoc($getTopIn)){
		$x++;
		echo "<tr><td>{$x}.</td><td><a target=\"blank\" href=\"http://{$Ref['url']}\">{$Ref['url']}</a></td><td><a target=\"blank\" href=\"{$Ref['real_url']}\">{$Ref['real_url']}</a></td><td>{$Ref['hitsin']}</td><td>{$Ref['hitsout']}</td><td>".date('g:ia',$Ref['time'])."</td>";
		echo '<td><form method="POST" action="" name="block_url_'.$x.'"><input type="hidden" name="action" value="update" /><input type="hidden" name="url" value="'.$Ref['url'].'"><input type="submit" name="Block" value="Block"></form></td></tr>';
	}
	echo '</table><br><br>';


	echo '<h2>Top 25 Referrers by Hits Out</h2>
	<table><tr><th></th><th>Display URL</th><th>Real URL</th><th>In</th><th>Out</th><th>Time</th><th></th></tr>';

	while($Ref=mysql_fetch_assoc($getTopOut)){
		$x++;
		echo "<tr><td>{$x}.</td><td><a target=\"blank\"  href=\"{$Ref['url']}\">{$Ref['url']}</a></td><td><a target=\"blank\" href=\"{$Ref['real_url']}\">{$Ref['real_url']}</a></td><td>{$Ref['hitsin']}</td><td>{$Ref['hitsout']}</td><td>".date('g:ia',$Ref['time'])."</td>";
		echo '<td><form method="POST" action="" name="block_url_'.(50+$x).'"><input type="hidden" name="url" value="'.$Ref['url'].'"><input type="submit" name="Block" value="Block"></form></td></tr>';
	}
	echo '</table>
	</div>';
}


function toprefs_menu_last(){
	global $wpdb;
	$table=$wpdb->prefix.'topref';

	$getLatestReal=mysql_query("SELECT url,real_url,time,sum(hitsout) AS hitsout, COUNT(*) AS hitsin FROM $table GROUP BY real_url ORDER BY time DESC LIMIT 50");
	$getLatestDisplay=mysql_query("SELECT url,real_url,time,sum(hitsout) AS hitsout, COUNT(*) AS hitsin FROM $table GROUP BY url ORDER BY time DESC LIMIT 50");

	echo '<div class="wrap">
	Here you can check the last 50 referrals to come to your site. The real URL is the actual page the visitor came from whereas the display URL is a converted version which will either be the domain or subdomain of the referring site.<br>
	The second set of results are grouped by the display URL and so should give you a better idea of the sites which are sending you hits.<br><br>

	<h2>Last 50 Referrals Grouped by Real URL</h2>
	<table><tr><th></th><th>Display URL</th><th>Real URL</th><th>In</th><th>Out</th><th>Time</th></tr>';

	$x=0;
	while($Ref=mysql_fetch_assoc($getLatestReal)){
		$x++;
		echo "<tr><td>{$x}.</td><td><a target=\"blank\" href=\"http://{$Ref['url']}\">{$Ref['url']}</a></td><td><a target=\"blank\"  href=\"{$Ref['real_url']}\">{$Ref['real_url']}</a></td><td>{$Ref['hitsin']}</td><td>{$Ref['hitsout']}</td><td>".date('g:ia',$Ref['time'])."</td></tr>";
	}
	echo '</table><br><br>

	<h2>Last 50 Referrals Grouped by Display URL</h2>
	<table><tr><th></th><th>Display URL</th><th>Real URL</th><th>In</th><th>Out</th><th>Time</th></tr>';

	$x=0;
	while($Ref=mysql_fetch_assoc($getLatestDisplay)){
		$x++;
		echo "<tr><td>{$x}.</td><td><a target=\"blank\"  href=\"http://{$Ref['url']}\">{$Ref['url']}</a></td><td><a target=\"blank\" href=\"{$Ref['real_url']}\">{$Ref['real_url']}</a></td><td>{$Ref['hitsin']}</td><td>{$Ref['hitsout']}</td><td>".date('g:ia',$Ref['time'])."</td></tr>";
	}
	echo '</table>
	</div>';

}

function toprefs_menu_options(){
	global $wpdb;
	$table=$wpdb->prefix.'topref';

	echo '<div class="wrap"><h2>Options</h2>';

	if($_POST['updateblocklist']=='Update Block List'){

		$Block=$_POST['topref_block'];
		$BlockList=explode('|',$Block);

		foreach($BlockList as $BlockMe){
			mysql_query("DELETE FROM $table WHERE url LIKE '%$BlockMe%'");
		}

		$Block=str_replace('.','\.',$Block);
		update_option('topref_block',$Block);
	}

	echo '<form method="POST" action="options.php">';

	wp_nonce_field('update-options');

	echo '<table class="form-table">
	<tr>
	<td><b>Ban Time:</b></td>
	<td><input type="text" name="topref_ban_time" value="'.get_option('topref_ban_time').'" /></td>
	<td>Number of minutes to ban another referral from the IP & URL</td>
	</tr>
	<tr>
	<td><b>Track For X Days:</b></td>
	<td><input type="text" name="topref_number_of_days" value="'.get_option('topref_number_of_days').'" /></td>
	<td>Number of days to keep "tracking" for (referrers older than this number will be deleted)</td>
	</tr>
	<tr>
	<td><b>Max URL length</b></td>
	<td><input type="text" name="topref_maxlength" value="'.get_option('topref_maxlength').'" /></td>
	<td>Maximum number of characters when displaying URLs.</td>
	</tr>
	<tr>
	<td><b>Minimum Hits</b></td>
	<td><input type="text" name="topref_minimum_hits" value="'.get_option('topref_minimum_hits').'" /></td>
	<td>Minimum number of hits a site must send before it will show up</td>
	</tr>
	<tr>
	<td><b>Replace Link Names</b></td>
	<td><textarea name="topref_url_replace" rows="3" cols="50">'.get_option('topref_url_replace').'</textarea></td>
	<td>Replace a url with another, more descriptive title in view.php or referrers.php (www.domain.com becomes Cool site 3). One link per line in the format www.example.com:Example Site.</td>
	</tr>
	<tr>
	<td><b>Replace URLs</b></td>
	<td><textarea name="topref_jump_url_replace" rows="3" cols="50">'.get_option('topref_jump_url_replace').'</textarea></td>
	<td>Replace a url with another one when clicking out (www.domain.com becomes www.domain.com/?in=your_user_id). One link per line in the format www.example.com:www.example.com/links.htm.</td>
	</tr>
	</table>
	<input type="hidden" name="action" value="update" />
	<p class="submit">
	<input type="submit" name="Submit" value="Save Changes" />
	</p>
	</form>
	<br><br>

	<form METHOD="POST" action="" name="topref_updateblocklist">
	<table class="form-table">
	<tr valign="top">
	<td><b>Block These URLs:</b></td>
	<td><input type="text" name="topref_block" value="'.str_replace('\.','.',get_option('topref_block')).'" size="50" /></td>
	<td>Keywords and urls to block from your list. Seperate each keyword/url with | like \'google.com|subdomain.site.com\'. If you specify a specific page or subdomain only hits from that URL will be excluded while other pages or sections of the site will be included.</td>
	</tr>
	</table>
	<input type="hidden" name="action" value="update" />
	<p class="submit">
	<input type="submit" name="updateblocklist" value="Update Block List" />
	</p>
	</form>
	</div>';
}


function topref_install(){
	global $wpdb;
	$table=$wpdb->prefix.'topref';

	$getTable=mysql_query("SHOW TABLES LIKE '$table'");

	if(@mysql_result($getTable,0,0)){
		mysql_query("ALTER TABLE `$table` ADD `real_url` VARCHAR( 255 ) NOT NULL AFTER `url`");
	}else{
		mysql_query("CREATE TABLE $table ( `id` int unsigned NOT NULL auto_increment, `url` varchar(255) default NULL, `url_real` varchar(255) default NULL, `hitsout` int unsigned default NULL, `time` int unsigned NOT NULL default '0', `ip` varchar(15) NOT NULL default '', `x_ip` varchar(15) NOT NULL default '', `c_ip` varchar(15) NOT NULL default '', PRIMARY KEY (`id`), KEY `id` (`id`,`time`,`url`,`hitsout`,`ip`,`x_ip`,`c_ip`))");

		$MySite=str_replace('www.','',$_SERVER['HTTP_HOST']);
		add_option('topref_block',$MySite.'|google|yahoo.com');
		add_option('topref_ban_time',30);
		add_option('topref_number_of_days',7);
		add_option('topref_minimum_hits',1);
		add_option('topref_url_replace','www.example.com:Example Site
www.seanbluestone.com:SeanBluestone.com');
		add_option('topref_jump_url_replace','www.example.com:www.example.com?in=37
www.example2.com:www.example2.com/category/file.htm');
		add_option('topref_maxlength',25);
		add_option('topref_widget_title','Top Referrers');
		add_option('topref_widget_display','Top');
		add_option('topref_widget_number','10');
	}
	add_option('topref_version','1.2');
	$now=time();
	mysql_query("INSERT INTO $table VALUES('','www.seanbluestone.com','http://www.seanbluestone.com',0,$now,'','','')");

}


function topref_uninstall(){

	// This function is no longer used but if you want to remove all traces of Top Referrers then uncomment this line near the start of this file:
	// register_deactivation_hook(__FILE__, 'topref_uninstall');
	// Then deactivate the plugin. All tables and options will be removed.

	global $wpdb;
	$table=$wpdb->prefix.'topref';
	mysql_query("DROP TABLE $table");

	delete_option('topref_block');
	delete_option('topref_ban_time');
	delete_option('topref_number_of_days');
	delete_option('topref_minimum_hits');
	delete_option('topref_url_replace');
	delete_option('topref_jump_url_replace');
	delete_option('topref_maxlength');
	delete_option('topref_widget_title');
	delete_option('topref_widget_display');
	delete_option('topref_widget_number');
	delete_option('topref_version');
}


function topref_display_refs($Mode='Top',$number_referrers=10){

	// This is the main function which handles displaying the referrers to the outside world

	global $wpdb;
	$table=$wpdb->prefix.'topref';

	$block=get_option('topref_block');
	$number_of_days=get_option('topref_number_of_days');
	$minimum_hits=get_option('topref_minimum_hits');
	$maxlength=get_option('topref_maxlength');
	$num_per_row=get_option('topref_num_per_row');

	$url_replacements=get_option('topref_url_replace');
	$lines=explode("\n",$url_replacements);

	foreach($lines as $line){
		$urls=explode(':',$line);
		$url_replace[$urls[0]]=$urls[1];
	}

	if(is_int($number_of_days)){
		$seconds=time()-(86400*$number_of_days); 
		mysql_query("DELETE FROM $table WHERE time < $seconds"); 
		if(mysql_affected_rows()){
			mysql_query("OPTIMIZE TABLE $table");
		}
	}

	// This is the html that will appear in your "top x referrers list" that would usually appear on every page
	// Anything starting with $saved will also be in that.

	$saved='<table><tr>';
	$toreturn='<table><tr><td></td><td><b>in</b></td><td><b>out</b></td></tr>';

	if($Mode!='Last'){
		$Mode='hitsin';
	}else{
		$Mode='time';
	}

	$c=mysql_query("SELECT MAX(id) AS id, url, sum(hitsout) AS hitsout, COUNT(*) AS hitsin FROM $table GROUP BY url HAVING hitsin >= $minimum_hits ORDER BY $Mode DESC LIMIT $number_referrers");
	$num=mysql_num_rows($c);
	if($num>0){
		$j=1;

		while($d=mysql_fetch_object($c)){

			$url=stripslashes($d->url);

			if(array_key_exists($url,$url_replace)){
				$url=$url_replace[$url];
			}else{
				$url=ucfirst(str_replace('http://','',str_replace('www.','',$url)));
				$url=substr($url,0,$maxlength);
			}
			$toreturn.="<tr><td><a href=\"".$_SERVER['PHP_SELF']."?goref=$d->id\" title=\"$url\" target=\"$d->id\">$url</a></td><td align=center>$d->hitsin</td><td align=center>$d->hitsout</td></tr>";	
		}

		$saved.="<tr><td colspan=\"$num_per_row\"><a href=\"referrers.php\">view all referrers</a></td></tr>";
	}else{
		$saved.='<td><i><a href="referrers.php">no referrers yet</a></i></td></tr>';
	}

	$saved.='</table>';
	$toreturn.='</table>';

	$saved=mysql_real_escape_string($saved);
	update_option('topref_display',$saved);
	unset($saved);
	echo $toreturn;
}



function topref_log_refs(){

	// This function handles logging the referrers. It checks if the referrer is banned or blocked and if not stores the real URL and display URL in the database
	global $wpdb,$x_ip,$c_ip;
	$table=$wpdb->prefix.'topref';
	$block_head_put=TRUE;

	$ban_time=get_option('topref_ban_time');
	$block=get_option('topref_block');

	if($block_head_put && ($_SERVER['REQUEST_METHOD']=='HEAD' || $_SERVER['REQUEST_METHOD']=='PUT')){
		return;
	}

	if(!empty($_SERVER['HTTP_REFERER']) && !preg_match("!$block!i",$_SERVER['HTTP_REFERER'])){

		preg_match('!^https?://([a-zA-Z\-_\.0-9]+)!',addslashes(htmlspecialchars($_SERVER['HTTP_REFERER'],ENT_QUOTES)),$ref);
		$real_ref=$_SERVER['HTTP_REFERER'];
		$ref=$ref[1];

		if(!empty($ref)){
			$parts=explode('.',$ref);
			$size=count($parts);

			if($size==2 || $size==3 && $parts[1]=='co' && strlen($parts[2])==2){
				$ref='www.'.join('.',$parts);	
			}elseif($parts[0]!='www' && $subdomain_sub){ // elseif($subdomain_sub)
				preg_match('!\.([a-zA-Z\-_0-9]+\.[a-zA-Z]+(\.[a-zA-Z]{2})?)$!',$ref,$new);

				if(substr_count($subdomain_sub,$new[1])){
					$ref='www.'.$new[1];
				}
			}

			$x_ip=addslashes(trim($_SERVER['HTTP_X_FORWARDED_FOR']));
			$c_ip=addslashes(trim($_SERVER['HTTP_CLIENT_IP']));

			if(!is_banned($table,$ref)){
				mysql_query("INSERT INTO $table (url,real_url,hitsout,time,ip,x_ip,c_ip) VALUES ('$ref','$real_ref',0,'".time()."','{$_SERVER['REMOTE_ADDR']}','$x_ip','$c_ip')");
			}
		}
	}
}


function is_banned($table,$ref){
	global $wpdb,$x_ip,$c_ip;
	$table=$wpdb->prefix.'topref';

	$ban_time=get_option('topref_ban_time');

	$xip=(empty($x_ip))?'x':$x_ip;
	$cip=(empty($c_ip))?'x':$c_ip;

	$c=mysql_query("SELECT time FROM $table WHERE (ip = '{$_SERVER['REMOTE_ADDR']}' OR x_ip = '$xip' OR c_ip = '$cip') AND url = '$ref' ORDER BY id DESC LIMIT 1");
	$d=mysql_fetch_object($c);
	if(is_object($d)){
		if($d->time>time()-($ban_time*60)){
			return true;
		}
		return false;
	}
}


function topref_gourl(){

	global $wpdb;
	$table=$wpdb->prefix.'topref';

	$jump_url_replacements=get_option('topref_jump_url_replace');
	if(strlen($jump_url)>3){
		$lines=explode("\n",$jump_url_replacements);

		foreach($lines as $line){
			$urls=explode(':',$line);
			$jump_url_replace[$urls[0]]=$urls[1];
		}
	}else{
		$jump_url_replace=array();
	}

	if($_GET['goref']){
		$id=$_GET['goref'];
		$c=mysql_query("SELECT id,url FROM $table WHERE id = '{$id}'");

		$d=mysql_fetch_object($c);
		if(is_object($d)){
			mysql_query("UPDATE $table SET hitsout = hitsout + 1 WHERE id = '{$d->id}'");
			$url=stripslashes($d->url);

			if(array_key_exists($url,$jump_url_replace)){
				$url=$jump_url_replace[$url];
			}
		}else{
			$url=$_SERVER['SERVER_NAME'];
		}

		if(!strpos($url,'http://')){
			$url='http://'.$url;
		}

		header("Location: $url");
		exit;
	}
}

?>