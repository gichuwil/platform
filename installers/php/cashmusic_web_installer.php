<?php
/**
 * CASH Music Installer
 *
 * This single file acts as a simple HTML5/JS website, downloading and installing
 * the latest platform files, configuring the database, setting and environment
 * details, and ultimately removing itself and any extra source from the host
 * server. (I kind of feel like a jerk making it delete itself.)
 *
 * Usage: just upload and run.
 *
 * A NOTE ABOUT SECURITY:
 * This file deletes itself for a reason. It clearly opens up some vulnerabilities
 * were it to be a public-facing script. Rather than wrestle with sanitizing all 
 * input we've chosen to use PDO to sanitize the SQL and remove the file when it
 * has run its course to avoid additional file edits. 
 * 
 *
 * @package diy.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2011, CASH Music
 * Licensed under the Affero General Public License version 3.
 * See http://www.gnu.org/licenses/agpl-3.0.html
*/
$cash_root_location = false;

function rrmdir($dir) { 
	if (is_dir($dir)) { 
	$objects = scandir($dir); 
	foreach ($objects as $object) { 
		if ($object != "." && $object != "..") { 
			if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
		} 
	} 
		reset($objects); 
		rmdir($dir); 
	} 
}

function determinedCopy($source,$dest,$retries=1) {
	$retries++;
	while($retries > 0) {
		if (@copy($source,$dest)) {
			return true;
		} else {
			sleep(1);
		}
		$retries--;
	}
	return false;
}

function findReplaceInFile($filename,$find,$replace) {
	if (is_file($filename)) {
		$file = file_get_contents($filename);
		$file = str_replace($find, $replace, $file);
		if (file_put_contents($filename, $file)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

ob_start();

if (!isset($_POST['installstage'])) {
	/**
	 * BASE MARKUP
	 *
	 * This is the basic markup file that will push through all of the other 
	 * stages of the installer, all of which will be accessed via AJAX as this
	 * script reuses itself.
	*/
	?>
	<!DOCTYPE html> 
	<html> 
	<head> 
	<title>Install / CASH Music</title> 
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 

	<style type="text/css" /> 
	/* TAG RESETS */
	html {margin:0;padding:0;}
	body {color:#000;background-color:#fff;text-align:left;font:75%/1.5em "helvetica neue",helvetica,arial,sans-serif;margin:0;padding:0;min-height:300px;min-width:750px;}
	a {color:#999;text-decoration:none;}
	a:hover {text-decoration:underline;}
	code {display:block;padding:2em;margin:0 0 1.5em 0;background-color:#ddd;background-image:url(../images/currentnav.png);background-position:left top;background-repeat:no-repeat;margin:0 auto;}
	img {border:0;}
	ul {padding:0.5em 0 0.5em 1.5em;}
	p {margin:0 0 1.5em 0;}
	h1 {font-size:3.75em;line-height:1.25em;margin:0;padding:0;}
	h3 {font-size:1.5em;line-height:1em;margin:20px 0 0 0;padding:0;padding-bottom:0.35em;}
	small {font-size:0.85em;line-height:1.25em;}
	small a {color:#000 !important;font-weight:bold;}
	table {font-size:0.85em;}

	/* GENERAL PAGE LAYOUT */
	#cash_sitelogo {position:absolute;top:8px;right:8px;text-align:right;overflow:hidden;height:30px;width:30px;z-index:2345;}
	#cash_sitelogo img {display:block;padding:0;margin:0;border:0;}

	/* FORMS */
	input {padding:6px 2% 6px 2%;border:1px solid #ccc;width:96%;}
	input:active, input:focus {outline:0;border:1px solid #888;}
	input.button, a.mockbutton {background-color:#ccc;padding:6px 18px 6px 18px;font-weight:bold;cursor:pointer;width:auto;}
	input.button:hover {background-color:#aaa;}
	input.checkorradio {width:auto;}
	select {padding:6px 3px 6px 3px;border:1px solid #ccc;width:100%;}
	select:active, select:focus {outline:2px solid #ff0;}
	label {font-size:0.85em;text-transform:uppercase;color:#999;}
	.nextstep {text-align:right;font-weight:bold;}

	/* COLORS */
	.bgcolor1, div.usecolor1 input.button, div.usecolor1 a.mockbutton {background-color:#df0854;color:#fff;}
	.bgcolor2, div.usecolor2 input.button, div.usecolor2 a.mockbutton {background-color:#df9b08;color:#fff;}
	.bgcolor3, div.usecolor3 input.button, div.usecolor3 a.mockbutton {background-color:#aacd07;color:#fff;}
	.bgcolor4, div.usecolor4 input.button, div.usecolor4 a.mockbutton {background-color:#0891df;color:#fff;}
	.bgcolor5, div.usecolor5 input.button, div.usecolor5 a.mockbutton {background-color:#9b08df;color:#fff;}
	div.usecolor1 input.button:hover, div.usecolor1 a.mockbutton:hover {background-color:#cd074d;color:#fff;}
	div.usecolor2 input.button:hover, div.usecolor2 a.mockbutton:hover {background-color:#ca8c07;color:#fff;}
	div.usecolor3 input.button:hover, div.usecolor3 a.mockbutton:hover {background-color:#97b800;color:#fff;}
	div.usecolor4 input.button:hover, div.usecolor4 a.mockbutton:hover {background-color:#0080c9;color:#fff;}
	div.usecolor5 input.button:hover, div.usecolor5 a.mockbutton:hover {background-color:#8a00ca;color:#fff;}
	a.mockbutton {color:#000 !important;font-size:0.9em;}
	a.mockbutton:hover {text-decoration:none !important;}
	a.loginlink {color:#000;background-color:#ff0;font-size:2.5em;font-weight:bold;padding:0 12px 0 12px;}
	a.loginlink:hover {background-color:#fff;}
	
	/* PROGRESS BAR */
	#progressspc {position:relative;width:400px;height:30px;font-size:20px;line-height:30px;font-weight:bold;margin:0 auto;overflow:hidden;color:#eee;background-color:#ccc;visibility:hidden;}
	#progressbar {position:absolute;top:0;left:0;width:0;height:30px;color:#fff;background-color:#0080c9;z-index:100;overflow:hidden;}
	p.progressamount {position:absolute;top:0;left:0;margin:0;padding:0 0 0 8px;z-index:10;}

	/* FOOTER (base code taken from cssstickyfooter.com) */
	* {margin-top:0;padding:0;} 
	html, body, #wrap {height:100%;}
	body > #wrap {height:auto;min-height:100%;}
	#mainspc {padding-bottom:90px;padding-top:150px;width:400px;margin:0 auto;}
	#footer {position:relative;margin-top:-36px;height:36px;color:#666;text-align:left;font-size:0.9em;line-height:1em;clear:both;background-color:#000;}
	#footer p {padding:12px 8px 0px 8px;}
	#footer a {color:#666;margin-left:24px;}
	#footer a:hover {color:#fff;}
	#footer .donatelink {color:#999;}

	/* ACCESSIBILITY STUFFS */
	* a:active, * a:focus, #footer a.donatelink:active, #footer a.donatelink:focus, input.checkorradio:focus, input.checkorradio:active
	{outline:2px solid #ff0;background-color:#ff0;color:#000;}
	</style>
	<link rel="icon" type="image/png" href="https://cashmusic.org/assets/images/icons/cash.png" /> 

	<script src="https://ajax.googleapis.com/ajax/libs/mootools/1.3.1/mootools-yui-compressed.js" type="text/javascript"></script> 
	<script type="text/javascript"> 
		var currentColor = 1;
		var progressIsVisible = false;
	
		function prepPage() {
			if (document.id('nextstepform')) {
				document.id('nextstepform').addEvent('submit', function(e) {
					if (e) { e.stop(); }
					var targetEl = document.id('mainspc').removeClass('usecolor'+currentColor);
					if (document.id('installstageinput')) {
						currentColor = document.id('installstageinput').get('value');
					}

					var myHTMLRequest = new Request.HTML({onComplete: function(response){
						targetEl.addClass('usecolor'+currentColor);
						targetEl.empty().adopt(response);
						document.id('mainspc').fade('in');
						prepPage();
					}});
					
					var that = this;
					if (document.id('installstagefade')) {
						document.id('mainspc').fade('out');
						(function(){myHTMLRequest.post(that);}).delay(600);
					} else {
						myHTMLRequest.post(that);
					}
				});
			}
		}

		function showProgress(amount) {
			if (document.id('progressspc').get('visibility') !== 'visible') {
				document.id('progressspc').fade('in');
			}
			if (amount > 0) {
				$$('p.progressamount').each(function(p){
					p.set('text',amount + '%');
				});
				document.id('progressbar').tween('width',Math.floor(400 * (amount / 100)));
			}
		}

		function hideProgress() {
			document.id('progressspc').fade('out');
		}

		window.addEvent('domready',function() {
			prepPage();
		});
	</script>

	</head> 

	<body> 

	<div id="wrap"> 
	 	<div id="mainspc" class="usecolor1">
			<h1>Hi.</h1>
			<p>
				This is the development installer for the CASH Music platform. By <b>development</b> 
				we mean it'll grab the latest working version of the platform, install it, and configure
				the bits like database settings. Be cautious because this is installing in-progress
				software, and it's not an updater. So you know...it'll blow any current CASH files away without a care. 
			</p><p>
				Because it doesn't care. 
			</p><p>
				But we do.
			</p><p>
				xo,<br />
				<a href="http://cashmusic.org/"><b>CASH Music</b></a>
			</p>
		
			<br />
			<div class="nextstep">
				Whenever you're ready:<br /><br />
				<form action="" method="post" id="nextstepform">
					<input type="hidden" name="installstage" id="installstageinput" value="2" />
					<input type="hidden" id="installstagefade" value="1" />
					<input type="submit" class="button" value="Start the installing" />
				</form>
			</div>
		</div>
		
		<div id="progressspc"><p class="progressamount">0%</p><div id="progressbar"><p class="progressamount">0%</p></div></div>
	</div>

	<div id="footer">
		<p><b>&copy; 2011 CASH Music.</b> All our code is open-source. <a href="http://cashmusic.org/why/" style="margin-left:0;">Learn more</a>. <a href="http://help.cashmusic.org/">Get help</a> <a href="http://cashmusic.org/donate" class="donatelink"><b>Donate</b></a></p>
	</div>

	</body>
	</html>
	<?php
} else {
	/**
	 * AJAX FUNCTIONALITY (Pretty much all the actual install steps)
	 *
	 * Basic output that will replace the initial message in the "mainspc" div above.
	*/
	switch ($_POST['installstage']) {
		case "2":
			/**
			 * INSTALL CURRENT SOURCE
			 *
			 * Don't even bother checking for git. Set up directories and hit the
			 * github API, grab the files, looped AJAX delay so we don't make
			 * anyone at github mad.
			*/
			$source_message = '<h1>Installing Source.</h1><p>Copying files from the repo. '
				. 'This should take a few minutes. We throttle the downloads to play nice with github.</p>'
				. '<div class="nextstep">Copying files from repository:</div>';
			if (!file_exists('./manifest.diy.org.cashmusic')) {
				$admin_dir = dirname($_SERVER['REQUEST_URI']) . '/admin';
				$source_dir = dirname($_SERVER['REQUEST_URI']) . '/source';

				// create the directory structure: remove any existing source files and re-download
				// we'll make a proper update script later.
				if (is_dir('./source')) {
					rrmdir('./source');
					//echo 'removed old source directory at ' . $source_dir . '<br />';
				}
				if (is_dir('./admin')) {
					rrmdir('./admin');
					//echo 'removed old admin directory at ' . $admin_dir . '<br />';
				}
				if (mkdir('./source')) {
					// get repo from github, strip unnecessary files and write manifest:
					$repo = json_decode(file_get_contents('https://github.com/api/v2/json/blob/all/cashmusic/DIY/latest_stable'));
					$files = array_keys((array)$repo->blobs);
					foreach ($files as $key => $file) {
						if (preg_match("/^(tests|installers|interfaces\/php\/demos|docs|db|Makefile|index.html)/", $file)) {
							unset($files[$key]);
						}
					}
					$files = json_encode(array_merge($files)); // resets keys
					file_put_contents('./manifest.diy.org.cashmusic',$files);
					
					echo $source_message;
					echo '<form action="" method="post" id="nextstepform"><input type="hidden" name="installstage" id="installstageinput" value="2" /></form>';
					echo '<script type="text/javascript">showProgress(0);(function(){document.id("nextstepform").fireEvent("submit");}).delay(250);</script>';
				} else {
					echo '<h1>Oh. Shit. Something\'s wrong.</h1>error creating directory: ' . $source_dir . '<br />';
				}
			} else {
				// grab our manifest:
				$files = json_decode(file_get_contents('./manifest.diy.org.cashmusic'));
				$filecount = count($files);
				$currentfile = 1;

				foreach ($files as $file) {
					//if (preg_match("/^(tests|installers)/", $file)) { // Don't install tests or installers
					//	continue;
					//}
					if (!file_exists('./source/'.$file)) {
						$path = pathinfo($file);
						if (!is_dir('./source/'.$path['dirname'])) mkdir('./source/'.$path['dirname'],0777,true);
						if (determinedCopy('https://raw.github.com/cashmusic/DIY/latest_stable/'.$file,'./source/'.$file)) {
							echo $source_message;
							if ($currentfile != $filecount) {
								echo '<form action="" method="post" id="nextstepform"><input type="hidden" name="installstage" id="installstageinput" value="2" /></form>';
								echo '<script type="text/javascript">showProgress(' . ceil(100 * ($currentfile / $filecount)) . ');(function(){document.id("nextstepform").fireEvent("submit");}).delay(650);</script>';
							} else {
								// we're done; remove the manifest file
								if (file_exists('./manifest.diy.org.cashmusic')) {
									unlink('./manifest.diy.org.cashmusic');
								}
								echo '<form action="" method="post" id="nextstepform"><input type="hidden" id="installstagefade" value="1" /><input type="hidden" name="installstage" id="installstageinput" value="3" /></form>';
								echo '<script type="text/javascript">hideProgress();(function(){document.id("nextstepform").fireEvent("submit");}).delay(500);</script>';
							}
							break;
						} else {
							echo '<h1>Oh. Shit. Something\'s wrong.</h1>error copying file: ' . (string)$file . '<br />';
							break;
						}
					}
					$currentfile = ++$currentfile;
				}
			}
			break;
		case "3":
			/**
			 * GET SETTINGS FROM USER
			 *
			 * Locations, MySQL, and email address
			*/
			$settings_message = '<h1>Basic Settings.</h1><p>You don\'t want us to just start putting '
				. 'files all over the place, do you? The form is auto-loaded with our best guess at a '
				. 'location for the core files, but you can put them wherever. Ideally these should be '
				. 'above the web root.</p> '
				. '<p>For the development installer we also need MySQL info. Don\'t worry, you\'re an '
				. 'expert. While you\'re at it, add an email address for the main admin account.'
				. ' (<b>Hint:</b> use a real email address so you can reset your password if need be.)</p>';
			if (is_dir('./source/')) {
				if (!$cash_root_location) {
					// if $cash_root_location is not set above make a directory above the root
					$cash_root_location = dirname($_SERVER['DOCUMENT_ROOT']) . '/cashmusic';
				}
				echo $settings_message;
				echo '<form action="" method="post" id="nextstepform"><input type="hidden" id="installstagefade" value="1" /><input type="hidden" name="installstage" id="installstageinput" value="4" /> '
				. '<h3>Install core files to:</h3><input type="text" name="frameworklocation" value="' . $cash_root_location . '" /> '
				. '<h3>Admin email account:</h3><input type="text" name="adminemailaccount" value="admin@' . $_SERVER['SERVER_NAME'] . '" /> '
				. '<h3><br />MySQL settings:</h3> '
				. '<label for="mysqlhost">Host (hostname or hostname:port)</label> <input type="text" name="mysqlhost" id="mysqlhost" value="localhost" /> '
				. '<br /><br /><label for="mysqluser">Username</label> <input type="text" name="mysqluser" id="mysqluser" value="root" /> '
				. '<br /><br /><label for="mysqlpass">Password</label> <input type="text" name="mysqlpass" id="mysqlpass" value="root" /> '
				. '<br /><br /><label for="mysqldbname">Database name</label> <input type="text" name="mysqldbname" id="mysqldbname" value="cashmusic" /> '
				. '<div class="nextstep"><br /><br />Alright then:<br /><br /><input type="submit" class="button" value="Set it all up" /></div> '
				. '</form>';
			} else {
				echo '<h1>Oh. Shit. Something\'s wrong.</h1> No source directory found.<br />';
			}
			break;
		case "4":
			/**
			 * MAKE IT ALL HAPPEN
			 *
			 * Edit and move files, write redirects, set up DBs, remove the installer script, party.
			*/
			
			$admin_dir = dirname($_SERVER['REQUEST_URI']) . '/admin';
			$user_settings = array(
				'frameworklocation' => (string)$_POST['frameworklocation'],
				'adminemailaccount' => (string)$_POST['adminemailaccount'],
				'mysqlhost' => (string)$_POST['mysqlhost'],
				'mysqluser' => (string)$_POST['mysqluser'],
				'mysqlpass' => (string)$_POST['mysqlpass'],
				'mysqldbname' => (string)$_POST['mysqldbname'],
				'systemsalt' => md5($user_settings['adminemailaccount'] . time())
			);

			if ($user_settings['frameworklocation']) {
				if (!is_dir($user_settings['frameworklocation'])) {
					if (!mkdir($user_settings['frameworklocation'])) {
						echo "<h1>Oh. Shit. Something's wrong.</h1><p>Couldn't create a directory at" . $user_settings['frameworklocation'] . ".</p>";
						break;
					}
				} else {
					if (is_dir($user_settings['frameworklocation'] . '/framework')) {
						rrmdir($user_settings['frameworklocation'] . '/framework');
						//echo 'removed old framework directory at ' . $cash_root_location . '/framework' . '<br />';
					}
				}
			} else {
				echo "<h1>Oh. Shit. Something's wrong.</h1><p>No core location specified.</p>";
				break;
			}
		
			// modify settings files
			if (
				!findReplaceInFile('./source/interfaces/php/admin/.htaccess','RewriteBase /interfaces/php/admin','RewriteBase ' . $admin_dir) || 
				
				!findReplaceInFile('./source/interfaces/php/admin/constants.php','$cashmusic_root = $root . "/../../../framework/php/cashmusic.php','$cashmusic_root = "' . $user_settings['frameworklocation'] . '/framework/cashmusic.php') || 
				!findReplaceInFile('./source/interfaces/php/admin/constants.php','define(\'ADMIN_WWW_BASE_PATH\', \'/interfaces/php/admin','define(\'ADMIN_WWW_BASE_PATH\', \'' . $admin_dir) || 
				
				!findReplaceInFile('./source/framework/php/settings/cashmusic_template.ini.php','hostname = "localhost:8889','hostname = "' . $user_settings['mysqlhost']) || 
				!findReplaceInFile('./source/framework/php/settings/cashmusic_template.ini.php','username = "root','username = "' . $user_settings['mysqluser']) || 
				!findReplaceInFile('./source/framework/php/settings/cashmusic_template.ini.php','password = "root','password = "' . $user_settings['mysqlpass']) || 
				!findReplaceInFile('./source/framework/php/settings/cashmusic_template.ini.php','database = "seed','database = "' . $user_settings['mysqldbname']) ||
				!findReplaceInFile('./source/framework/php/settings/cashmusic_template.ini.php','salt = "I was born of sun beams; Warming up our limbs','salt = "' . $user_settings['systemsalt'])
			) {
				echo "<h1>Oh. Shit. Something's wrong.</h1><p>We had trouble editing a few files. Please try again.</p>";
				break;
			}

			// move source files into place
			if (
				!rename('./source/framework/php/settings/cashmusic_template.ini.php', './source/framework/php/settings/cashmusic.ini.php') ||
				!rename('./source/framework/php', $user_settings['frameworklocation'] . '/framework') || 
				!rename('./source/interfaces/php/admin', './admin')
			) {
				echo '<h1>Oh. Shit. Something\'s wrong.</h1> <p>We couldn\'t move files into place. Please make sure you have write access in '
				. 'the directory you specified for the core.</p>';
				break;
			}

			// set up database, add user / password
			$user_password = substr(md5($user_settings['systemsalt'] . 'password'),4,7);
			$db_server = $user_settings['mysqlhost'];
			$db_port = 3306;
			if (strpos($db_server,':') !== false) {
				$host_and_port = explode(':',$db_server);
				$db_server = $host_and_port[0];
				$db_port = $host_and_port[1];
			}
			try {
				$pdo = new PDO ("mysql:host=$db_server;port=$db_port;dbname={$user_settings['mysqldbname']}",$user_settings['mysqluser'],$user_settings['mysqlpass']);
			} catch (PDOException $e) {
				echo '<h1>Oh. Shit. Something\'s wrong.</h1> <p>Couldn\'t connect to the database. Files are all in-place, so you can manually edit settings or start over.';
				break;
			}

			if ($pdo->query(file_get_contents($user_settings['frameworklocation'] . '/framework/settings/sql/cashmusic_db.sql'))) {
				$password_hash = hash_hmac('sha256', $user_password, $user_settings['systemsalt']);
				$data = array(
					'email_address' => $user_settings['adminemailaccount'],
					'password' => $password_hash,
					'creation_date' => time()
				);
				$query = "INSERT INTO user_users (email_address,password,creation_date) VALUES (:email_address,:password,:creation_date)";

				try {  
					$q = $pdo->prepare($query);
					$success = $q->execute($data);
					if (!$success) {
						echo '<h1>Oh. Shit. Something\'s wrong.</h1> <p>Couldn\'t add the user to the database. Files are all in-place, so you can manually edit settings or start over.';
						break;
					}
				} catch(PDOException $e) {  
					echo '<h1>Oh. Shit. Something\'s wrong.</h1> <p>Couldn\'t add the user to the database. Files are all in-place, so you can manually edit settings or start over.';
					break;
				}	
			} else {
				echo '<h1>Oh. Shit. Something\'s wrong.</h1> <p>Couldn\'t create database tables. Files are all in-place, so you can manually edit settings or start over.';
				break;
			}

			// success message
			echo '<h1>All done.</h1><p>Okay. Everything is set up, configured, and ready to go. Follow the link below and login with the given '
			. 'credentials</p><p><br /><br /><a href="./admin/" class="loginlink">Click to login</a><br /><br /><b>Email address:</b> ' . $user_settings['adminemailaccount']
			. '<br /><b>Password:</b> ' . $user_password;
			
			echo '<br /><br /><br /><br /><small>I feel compelled to point out that in the time it took you to read this, I, your helpful installer script, have deleted '
			. 'myself in the name of security. It is a far, far better thing that I do, than I have ever done; it is a far, far better rest that I go to, than I '
			. 'have ever known.</small>';
			
			// create an index.php with a redirect to ./admin in the current directory, 
			// remove the installer and the remaining source directory
			if (is_dir('./source')) rrmdir('./source');
			if (is_file('./index.php')) unlink('./index.php');
			@file_put_contents('./index.php',"<?php header('Location: ./admin/'); ?>");
			if (is_file('./install.php')) unlink('./install.php');
			
			break;
		default:
			echo "<h1>Oh. Shit. Something's wrong.</h1><p>We ran into an error. Please make sure you have write permissions in this directory and try again.</p>";
	}
}

ob_end_flush(); 
?>