<?php
	try
	{
		require('../include/core/common.php');
		require(PATHS_INCLUDE . 'libraries/radio.lib.php');
		include_once('shoutcast/ShoutcastInfo.class.php');
		require(PATHS_INCLUDE . 'libraries/articles.lib.php');
		$ui_options['stylesheets'][] = 'radio.css';
		
		$uri_parts = explode('/', $_SERVER['REQUEST_URI']);
		
		// Get information from server
		$radioinfo = radio_shoutcast_fetch();
		
		/* 
			 ###############################################
				What's playing
			 ###############################################
		*/
		if ($radioinfo['status'] == 1) // If the server is broadcasting
		{
			$out .= '<div id="radio_playing">
								<span>' . $radioinfo['track'] . '</span>
							 </div>
							' . "\n";
		}
		/* 
			 ###############################################
				Logo
			 ###############################################
		*/
		$out .= '<img src="http://images.hamsterpaj.net/radio/logo.png" id="radio_logo" />' . "\n";
		
		/* 
			 ###############################################
				Menu
			 ###############################################
		*/
		$out .= '<ul id="radio_menu">
		<li>
			<a id="radio_menu_01' . (($uri_parts[2] == 'lyssna' || $uri_parts[2] == '') ? '_active"' : '') . '" href="/radio/">Lyssna</a>
		</li>
		<li>
			<a id="radio_menu_02' . ($uri_parts[2] == 'crew' ? '_active"' : '') . '" href="/radio/crew">Crew</a>
		</li>
		<li>
			<a id="radio_menu_03' . ($uri_parts[2] == 'program' ? '_active"' : '') . '" href="/radio/program">Program</a>
		</li>
		<li>
			<a id="radio_menu_04' . ($uri_parts[2] == 'schema' ? '_active"' : '') . '" href="/radio/schema">Schema</a>
		</li>
		<li>
			<a id="radio_menu_05" href="/chat/">IRC-kanal</a>
		</li>
		<li>
			<a id="radio_menu_06" href="/diskussionsforum/hamsterradio/">Radioforum</a>
		</li>
		<li>
			<a id="radio_menu_07' . ($uri_parts[2] == 'om_radion' ? '_active"' : '') . '" href="/radio/om_radion/">Om radion</a>
		</li>
		</ul>
		' . "\n";
		
		switch ($uri_parts[2])
		{
			case 'post_settings':
				if (is_privilegied('radio_dj'))
				{
					switch ($uri_parts[3])
					{
						case 'radio_dj_add':
							
							$dj_username = $_POST['radio_dj_add_name'];
							if(strtolower($dj_username) == 'borttagen')
							{
								throw new Exception('Jävla tjockis');
								trace('tjockis_live_at_radio', __FILE__ . ' line ' . __LINE__ . ' : ' . $_SESSION['login']['username']);
							}
							$query = 'SELECT id FROM login WHERE username = "' . $dj_username . '" AND is_removed != 1 LIMIT 1';
							$result = mysql_query($query) or report_sql_error($query, __FILE__, __LINE__);
							if(mysql_num_rows($result) != 1)
							{
								throw new Exception('Den fanns det två av... :D (=det finns fler än en användare med det användarnamnet eller så finns det inga användare med det användarnamnet, vilket inte är så bra...).');
							}
							$data = mysql_fetch_assoc($result);
							$dj_user_id = $data['id'];
							$dj_information = $_POST['radio_dj_add_information'];
							if (intval($dj_user_id) > 0 && strlen($dj_information) > 0)
							{
								radio_dj_add($dj_user_id, $dj_information);
							}
							else
							{
								throw new Exception('Det går inte för sig. Skräddaren säger NEJ!<br />Du _måste_ fylla i formuläret helt och hållet ditt blindstyre.<br /><em>Love, Joar</em>');
							}
						break;
						
						case 'program_add':
							
							if(!isset($_POST['name'], $_POST['dj'], $_POST['sendtime'], $_POST['information']))
							{
								throw new Exception('Getmjölk?');
							}
							
							if(strlen($_POST['name']) < 0 || !is_numeric($_POST['dj']) || strlen($_POST['information']) < 0)
							{
								throw new Exception('Något fält var ju INTE korrekt ifyllt säger jag ju då.');
							}
							
							radio_program_add(array(
								'name' => $_POST['name'],
								'sendtime' => $_POST['sendtime'],
								'information' => $_POST['information'],
								'dj' => $_POST['dj']
							));
						break;
						
						case 'schedule_add':
							
							if(!isset($_POST['program'], $_POST['starttime'], $_POST['endtime']))
							{
								throw new Exception('Getmjölk i soppan?');
							}
							
							if(strlen($_POST['starttime']) < 0 || !is_numeric($_POST['program']) || strlen($_POST['endtime']) < 0)
							{
								throw new Exception('Något fält var ju INTE korrekt ifyllt säger jag ju då.');
							}
							
							radio_schedule_add(array(
								'program_id' => $_POST['program'],
								'starttime' => $_POST['starttime'],
								'endtime' => $_POST['endtime']
							));
						break;
					}
				}
				else
				{
					throw new Exception('Haxx0r!');
				}
			break;
			
			case 'crew':
				$options['order-by'] = 'username';
				$options['order-direction']= 'ASC';
				$radio_djs = radio_djs_fetch($options);
				foreach($radio_djs as $radio_dj)
				{
					$out .= '<div class="radio_crew">' . "\n";
					$out .= insert_avatar($radio_dj['user_id']) . "\n";
					$out .= '<h2>' . $radio_dj['username'] . '</h2>' . "\n";
					if(is_privilegied('radio_admin'))// Only administrators for the whole radio can edit/remove DJs
					{
						$out .= '<div class="admin_tools">' . "\n";
						$out .= '<a href="#" title="Ändra DJ">Ändra</a> | ' . "\n"; // När man klickar edit ska formuläret för att lägga till sändning användas för att ändra sändningen.
						$out .= '<a href="#" title="Ta bort DJ">Ta bort</a>' . "\n"; // Ajax, popup-accept
						$out .= '</div>' . "\n";
					}
					$out .= '<p>' . $radio_dj['information'] . '</p>' . "\n"; // substr to fitting characters
					$out .= '</div>' . "\n";
				}
				if(is_privilegied('radio_admin')) // Only administrators for the whole radio can edit/add DJs
				{
					$ui_options['stylesheets'][] = 'forms.css'; // Includes stylesheet for form.
					
					$out .= '<fieldset>' . "\n";
					$out .= '<legend>Lägg till DJ</legend>' . "\n";
					$out .= '<form action="/radio/post_settings/radio_dj_add/" method="post">';
					$out .= '<table class="form">' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="radio_dj_add_name">Användarnamn <strong>*</strong></label></th>' . "\n";
						$out .= '<td><input type="text" name="radio_dj_add_name" /></td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="radio_dj_add_information">Information <strong>*</strong></label></th>' . "\n"; 
						$out .= '<td><textarea name="radio_dj_add_information" cols="45" rows="5"></textarea></td>' . "\n";
					$out .= '</tr>' . "\n";				
					$out .= '</table>' . "\n";
					$out .= '<input type="submit" id="radio_dj_add_submit" value="Spara" />' . "\n"; // Ajax, privilegiet radio_sender ska ges till personen
					$out .= '</form>';
					$out .= '</fieldset>' . "\n";
				}	
			break;
			
			case 'program':	
				$options['order-by']= 'name';
				$options['order-direction']= 'DESC';
				$radio_programs = radio_programs_fetch($options);
				$out .= '<table>' . "\n";
				foreach($radio_programs as $radio_program)
				{
					$out .= '<tr>' . "\n";
					$out .= '<td>' . $radio_program['name'] . '</td>' . "\n";
					$out .= '<td>' . $radio_program['dj'] . '</td>' . "\n";
					$out .= '<td>' . $radio_program['sendtime'] . '</td>' . "\n";
					$out .= '<td>' . $radio_program['information'] . '</td>' . "\n"; // substr to fitting characters
					if(is_privilegied('radio_sender')) // Only senders can edit programs
					{
						$out .= '<td><a href="#" title="Ändra program">Ändra</a></td>' . "\n"; // När man klickar edit ska formuläret för att lägga till sändning användas för att ändra sändningen.
						$out .= '<td><a href="#" title="Ta bort program">Ta bort</a></td>' . "\n"; // Ajax, popup-accept
					}
					$out .= '</tr>' . "\n";
				}
				$out .= '</table>' . "\n";
				if(is_privilegied('radio_sender')) // Only senders can add/edit programs
				{
					$radio_djs = radio_djs_fetch(); // Fetches DJ's to the Select list in the form
					$ui_options['stylesheets'][] = 'forms.css'; // Inkluderar stilmall för formuläret
					
					$out .= '<fieldset>' . "\n";
					$out .= '<legend>Lägg till program</legend>' . "\n";
					$out .= '<form action="/radio/post_settings/program_add" method="post">';
					$out .= '<table class="form">' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="name">Namn <strong>*</strong></label></th>' . "\n";
						$out .= '<td><input type="text" name="name" /></td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="dj">DJ <strong>*</strong></label></th>' . "\n";
						$out .= '<td><select name="dj">' . "\n";
							foreach($radio_djs as $radio_dj)
							{
								$out .= '<option value="' . $radio_dj['id'] . '">' . $radio_dj['username'] . '</option>' ."\n";
							}
						$out .= '</select>' . "\n";
						$out .= '</td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="sendtime">Sändningstid/Övrigt </label></th>' . "\n";
						$out .= '<td><input type="text" name="sendtime" /></td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="information">Information <strong>*</strong></label></th>' . "\n"; 
						$out .= '<td><textarea name="information" cols="45" rows="10"></textarea></td>' . "\n";
					$out .= '</tr>' . "\n";				
					$out .= '</table>' . "\n";
					$out .= '<input type="submit" id="submit" value="Spara" />' . "\n"; // Ajax
					$out .= '</form>';
					$out .= '</fieldset>' . "\n";
				}	
			break;
			
			case 'schema':	
				$options['show_sent'] = false; 
				$options['limit'] = 30; 
				$options['order-direction']= 'DESC'; // We want them in order by which is coming first
				$radio_events = radio_schedule_fetch($options);
				$out .= '<table>' . "\n";
				foreach($radio_events as $radio_event)
				{
					$out .= '<tr>' . "\n";
					$out .= '<td>' . $radio_event['name'] . '</td>' . "\n";
					$out .= '<td>' . $radio_event['username'] . '</td>' . "\n";
					$out .= '<td>' . $radio_event['starttime'] . '</td>' . "\n"; // Snygga till datumet så det står: Imorgon 22:00 Eller ngt sådant snyggt
					if(is_privilegied('radio_sender'))
					{
						$out .= '<td><a href="#" title="Ändra sändning">Ändra</a></td>' . "\n"; // När man klickar edit ska formuläret för att lägga till sändning användas för att ändra sändningen.
						$out .= '<td><a href="#" title="Ta bort sändning">Ta bort</a></td>' . "\n"; // Ajax
					}
					$out .= '</tr>' . "\n";
				}
				$out .= '</table>' . "\n";
				if(is_privilegied('radio_sender'))
				{
					$ui_options['stylesheets'][] = 'forms.css'; // includes stylesheet for form
					
					$options['order-by']= 'name';
					$options['order-direction']= 'DESC';
					$radio_programs = radio_programs_fetch($options); // For Select list
					unset($options);
					
					$out .= '<fieldset>' . "\n";
					$out .= '<legend>Lägg till sändning</legend>' . "\n";
					$out .= '<form action="/radio/post_settings/schedule_add" method="post">';
					$out .= '<table class="form">' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="program">Program <strong>*</strong></label></th>' . "\n";
						$out .= '<td><select name="program">' . "\n";
							foreach($radio_programs as $radio_program)
							{
								$out .= '<option value="' . $radio_program['id'] . '">' . $radio_program['name'] . '</option>' ."\n";
							}
						$out .= '</select>' . "\n";
						$out .= '</td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="starttime">Starttid <strong>*</strong></label></th>' . "\n"; // Jquery calendar?
						$out .= '<td><input type="text" name="starttime" value="' . date( 'Y-m-d') . ' 00:00:00" /></td>' . "\n";
					$out .= '</tr>' . "\n";
					$out .= '<tr>' . "\n";
						$out .= '<th><label for="endtime">Sluttid <strong>*</strong></label></th>' . "\n"; // jquery calendar?
						$out .= '<td><input type="text" name="endtime" value="' . date( 'Y-m-d') . ' 00:00:00" /></td>' . "\n";
					$out .= '</tr>' . "\n";				
					$out .= '</table>' . "\n";
					$out .= '<input type="submit" id="submit" value="Spara" />' . "\n"; // Ajax
					$out .= '</form>';
					$out .= '</fieldset>' . "\n";
				}
				
			break;
			
			case 'om_radion':		
				$ui_options['stylesheets'][] = 'articles.css'; // Includes stylesheet for article
				$article = articles_fetch(array('id' => '96'));
				$out .= render_full_article($article);
			break;
				
			default:
				$options['broadcasting'] = true; // It should be broadcasting right now
				$options['limit'] = 1; // We only wish to have one
				$options['order-direction']= 'DESC'; // We want the latest
				$radio_sending = radio_schedule_fetch($options);
				if (isset($radio_sending[0]) && $radioinfo['status'] == 1) // If program is sent and server is up
				{
					$out .= '<div id="radio_sending">' . "\n";
						$out .= '<img src="http://images.hamsterpaj.net/images/users/thumb/' . $radio_sending[0]['user_id'] . '.jpg" />' . "\n";
						$out .= '<div class="radio_about">' . "\n";
						$out .= '<h2>' . $radio_sending[0]['name'] . '</h2>' . "\n";
						$out .= '<strong>DJ: ' . $radio_sending[0]['username'] . '</strong><br />' . "\n";
						$out .= '<span>' . $radio_sending[0]['sendtime'] . '</span>' . "\n";
						$out .= '</div>' . "\n";
					$out .= '</div>' . "\n";
				}
				else
				{
					if ($radioinfo['status'] == 1) // If the server is up but no program scheduled
					{
						$out .= '<div id="radio_sending_slinga">' . "\n"; // Displays "Slingan rullar"
						$out .= '</div>' . "\n";
					}
					else
					{
						$out .= '<div id="radio_sending_inactive">' . "\n"; // Displays "Ingen sändning
						$out .= '</div>' . "\n";
					}
				}
				
				$options['broadcasting'] = false; // It shouldn't be broadcasting right now
				$options['limit'] = 1; // We only want the coming one
				$options['order-direcion']= 'DESC'; // We want the coming one
				$radio_next_program = radio_schedule_fetch($options);
				if (isset($radio_next_program[0])) // If there are any next program
				{
					$out .= '<div id="radio_next_program">' . "\n";
						$out .= insert_avatar($radio_next_program[0]['user_id']) . "\n";
						$out .= '<div class="radio_about">' . "\n";
						$out .= '<h2>' . $radio_next_program[0]['name'] . '</h2>' . "\n";
						$out .= '<strong>DJ: ' . $radio_next_program[0]['username'] . '</strong><br />' . "\n";
						$out .= '<span>' . $radio_next_program[0]['sendtime'] . '</span>' . "\n";
						$out .= '</div>' . "\n";
					$out .= '</div>' . "\n";
				}
				else
				{
					$out .= '<div id="radio_next_program_inactive">' . "\n"; // Displays a "Inget inplanerat" box
					$out .= '</div>' . "\n";
				}
				
				if ($radioinfo['status'] == 1) // If the server is broadcasting we will show a list of players to listen in
				{
					$out .= '<ul id="choose_player">
										<li>
											<a id="choose_player_01" href="/radio/lyssna/pls" title="Den här länken fungerar i de flesta spelare. Exempelvis: iTunes, Real player, Winamp, VLC, foobar.">Spela upp radio i normala spelare</a>
										</li>
										<li>
											<a id="choose_player_02" href="/radio/lyssna/asx" title="">Spela upp radio i Windows Media Player</a>
										</li>
										<li>
											<a id="choose_player_03" href="/radio/lyssna/webbspelare" title="">Spela upp radio i webbspelaren</a>
										</li>
									</ul>' . "\n";
				}
				switch ($uri_parts[3])
				{
					case 'pls': // If address is lyssna/pls it will download pls playlist
						header('Content-Type: audio/scpls');
						header('Content-Disposition: attachment;filename="lyssna.pls"');
						$fp=fopen('playlists/lyssna.pls','r');
						fpassthru($fp);
						fclose($fp);
					break;
					case 'asx': // If address is lyssna/asx it will download asx playlist
						header('Content-Type: video/x-ms-asf');
						header('Content-Disposition: attachment;filename="lyssna.asx"');
						$fp=fopen('playlists/lyssna.asx','r');
						fpassthru($fp);
						fclose($fp);
					break;
					case 'webbspelare': // If address is lyssna/webbspelaren it will open the webplayer in a popup-window
					
					break;
				}
			break;
		}
	}
	catch (Exception $error)
	{
		$options['type'] = 'error';
    $options['title'] = 'Nu blev det fel här';
    $options['message'] = $error -> getMessage();
    $options['collapse_link'] = 'Visa felsökningsinformation';
    $options['collapse_information'] = preint_r($error, true);
    $out .= ui_server_message($options);
		preint_r($error);
	}
	ui_top($ui_options);
	echo $out;
	ui_bottom();
?>