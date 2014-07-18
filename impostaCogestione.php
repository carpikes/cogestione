<?php
require_once("common.php");
$css = Array('css/StiliCogestione.css');
$js = Array('js/imposta.js');

showHeader('ca-nstab-imposta', "Impostazioni cogestione", $css, $js);

$configurator = Configurator::configurator();
$cogestione = new Cogestione();
$authenticated = $_SESSION['auth'];

if(isset($_POST['login'])) {
	if($configurator->isAuthenticated($_POST['username'], $_POST['password'])) {
		$_SESSION['auth'] = $authenticated = TRUE;
		$_SESSION['username'] = $_POST['username'];
		printSuccess('Benvenuto ' . htmlentities($_POST['username']) . ', ti sei autenticato con successo!');
	} else {
		$_SESSION['auth'] = $authenticated = FALSE;
		authenticationFailed();
	}
} else if (isset($_GET['logout'])) {
	unset($_SESSION['auth']);
	unset($_SESSION['username']);
	printSuccess('Logout avvenuto con successo.');
} else if(isset($_POST['submitActivities'])) {
	if($authenticated) {
		$activities = $bl = $deleteAct = $deleteBlocks = Array();
		
		// Escaping dati attività
		if(isset($_POST['activity'])) {
			foreach($_POST['activity'] as $act) {
				if(!empty($act['id'])) {
					$id = intval($act['id']);
					$activities[$id]['block'] = intval($act['block']);
					$activities[$id]['max'] = intval($act['max']);
					$activities[$id]['title'] = htmlspecialchars_decode($act['title'], ENT_QUOTES);
					$activities[$id]['vm'] = intval(!empty($act['vm']));
					$activities[$id]['description'] = htmlspecialchars_decode($act['description'], ENT_QUOTES);
					if(!empty($act['delete'])) {
						$deleteAct[] = $id;
					}
				}
			}
		}
		
		// Escaping dati blocchi
		if(isset($_POST['block'])) {
			foreach($_POST['block'] as $b) {
				if(!empty($b['id'])) {
					$id = intval($b['id']);
					$bl[$id]['title'] = htmlspecialchars_decode($b['title'], ENT_QUOTES);
					$bl[$id]['newRows'] = intval($b['newRows']);
					if(!empty($b['delete'])) {
						$deleteBlocks[] = $id;
					}
				}
			}
		}
		
		// Cancella le attività da cancellare.
		$cogestione->deleteActivities($deleteAct);
		
		// Modifica dati attività.
		foreach($activities as $id => $in) {
			if(in_array($id, $deleteAct))
				continue;
			$cogestione->updateActivity($id, $in['block'], $in['max'], $in['title'], $in['vm'], $in['description']);
		}
		
		// Cancella i blocchi da cancellare
		$cogestione->deleteBlocks($deleteBlocks);
		
		// Modifica dati blocchi
		foreach($bl as $k => $b) {
			if(in_array($k, $deleteBlocks))
				continue;
			$cogestione->updateBlock(intval($k), $b['title']);
			
			// Nuove righe attività
			if($b['newRows']>0) {
				$cogestione->addNewActivities($b['newRows'], $k);
			}
		}
		
		// Nuovi blocchi
		$newBlocks = intval($_POST['newBlocks']);
		$cogestione->addNewBlocks($newBlocks);
			
		printSuccess('La tabella attività è stata modificata.');
		
	} else {
		authenticationFailed();
	}
} else if(isset($_POST['submitDelete'])) {
	if($authenticated) {
		/* Cancellazione di tutte le prenotazioni */
		if(isset($_POST['confermaTruncate'])) {
			$res = $cogestione->clearReservations();
			if($res) {
				printSuccess('Tutte le prenotazioni sono state cancellate.');
			} else {
				printError('Errore nel cancellare le prenotazioni!');
			}
		} else if(isset($_POST['uid_delete']) && $_POST['uid_delete']) {
			/* Cancellazione di un utente singolo */
			$uid = intval($_POST['uid_delete']);
			$uInfo = $cogestione->getUser($uid);
			if($uInfo !== FALSE) {
				$result = $cogestione->deleteUser($uid);
				if($result === TRUE) {
					printSuccess("L'utente " . $uInfo['user_name'] . " " . $uInfo['user_surname']
					. " ($uid) è stato eliminato con successo.");
				} else {
					printError("L'utente $uid non ha potuto essere eliminato.");
				}
			} else {
				printError("L'utente con UID $uid non esiste!");
			}
		}
	} else {
		authenticationFailed();
	}
} else if(isset($_POST['submitEnable'])) {
	if($authenticated) {
		/* Manual mode */
		if(isset($_POST['autoEnable'])) {
			$configurator->setManualMode(!(bool)$_POST['autoEnable']);
		}
		
		if(isset($_POST['manualOn'])) {
			$configurator->setManualOn((bool)$_POST['manualOn']);
		}
		
		/* Start and end times */
		if(isset($_POST['startTime'])) {
			$configurator->setStartTime($_POST['startTime']);
		}
		if(isset($_POST['endTime'])) {
			$configurator->setEndTime($_POST['endTime']);
		}
		
		printSuccess('Impostazioni modificate con successo.');
	} else {
		authenticationFailed();
	}
}

function authenticationFailed() {
	printError('Autenticazione fallita!');
}    
?>

<p>
Cambia le impostazioni della cogestione utilizzando il form sottostante.
Le modifiche saranno applicate soltanto dopo aver confermato cliccando sul pulsante <b>Salva modifiche orario</b> in fondo alla pagina.
</p>
<p>
Per <b>aggiungere un nuovo blocco o una nuova attività</b> occorre dunque:
</p>
<ol>
	<li>incrementare gli appositi contatori;</li>
	<li>salvare le modifiche;</li>
	<li>modificare i dati dei nuovi elementi creati.</li>
</ol>
<p>
Per <b>cancellare un blocco o un'attività</b>, spuntare la casella <b>"DEL"</b> relativa e poi confermare. Saranno automaticamente cancellate:
</p>
<ol>
	<li>le attività non appartenenti ad alcun blocco;</li>
	<li>le prenotazioni non riferite ad un blocco esistente;</li>
	<li>le prenotazioni non riferite ad un'attività esistente.</li>
</ol>
<p>
Per segnare un'attività come <b>riservata alle quarte o alle quinte</b>, spuntare la casella <b>"VM18"</b> relativa e poi confermare.
</p>
<p>
Per motivi di coerenza dei dati, è consigliabile azzerare le prenotazioni dopo aver modificato le attività.
</p>

<!-- Authentication form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Autenticazione</h3>
		</div>
	<div class="panel-body">
		<fieldset class="form-inline">
			<div class="form-group">
				<label class="sr-only" for="username">Username: </label>
				<input class="form-control" type="text" name="username" id="username" size="20" placeholder="utente" />
			</div>
			<div class="form-group">
				<label class="sr-only" for="password">Password: </label>
				<input class="form-control" type="password" name="password" id="password" size="20" placeholder="password" />
			</div>
			<!-- Login button -->
			<div class="form-group">
				<button class="btn btn-primary" type="submit" name="login">Login</button>
			</div>
		</fieldset>
	</div>
</div>

<!-- Abilitation form -->
<div class="row">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Abilitazione delle prenotazioni</h3>
			</div>
			<ul class="list-group">
				<!-- Auto/manual switch -->
				<li class="list-group-item">
					<div class="radio">
						<label>
						<input id="automatic-switch" type="radio" name="autoEnable" value="1" <?php if(!$configurator->getManualMode()) echo "checked"; ?> />
						Automatica
						</label>
					</div>
					<div class="radio">
						<label>
						<input id="manual-switch" type="radio" name="autoEnable" value="0" <?php if($configurator->getManualMode()) echo "checked"; ?> />
						Manuale
						</label>
					</div>
				</li>
				
				<!-- Options for automatic handling -->
				<li class="list-group-item" id="automatic-panel">
					<fieldset class="form-inline">
						<div class="form-group">
							Date inizio e fine (solo modalità automatica):<br />
							<input class="form-control" type="datetime-local" name="startTime" value="<?php echo $configurator->getStartTime();?>" /> –
							<input class="form-control" type="datetime-local" name="endTime" value="<?php echo $configurator->getEndTime();?>" />
						</div>
					</fieldset>
				</li>
				
				<!-- Options for manual handling -->
				<li class="list-group-item" id="manual-panel">
					Switch on/off (solo modalità manuale):<br />
					<div class="radio">
						<label>
						<input type="radio" name="manualOn" value="1" <?php if($configurator->getManualOn()) echo "checked"; ?> />On
						</label>
					</div>
					<div class="radio">
						<label>
						<input type="radio" name="manualOn" value="0" <?php if(!$configurator->getManualOn()) echo "checked"; ?> />Off<br />
						</label>
					</div>
				</li>
				
				<!-- Submit button -->
				<li class="list-group-item">
					<button class="btn btn-primary" type="submit" name="submitEnable">Modifica impostazioni</button>
				</li>
			</ul>
		</div>
	</div>
	
	<!-- Deletion form -->
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Cancellazione prenotazioni</h3>
			</div>
		<ul class="list-group">
			<li class="list-group-item">
				<div class="checkbox">
					<?php
					echo 'Ci sono <b>' . $cogestione->getSubscriptionsNumber() . ' prenotazioni</b> effettuate.';
					?> Se vuoi cancellarle, spunta la casella.
					I dati non potranno essere recuperati.<br />
					<label for="confermaTruncate">
					<input type="checkbox" name="confermaTruncate" id="confermaTruncate" value="Cancella prenotazioni" />
					Cancella tutte le prenotazioni
					</label>
				</div>
			</li>
			<li class="list-group-item" id="delete-single-reservation">
				<div class="form-group">
					Elimina una singola prenotazione.<br />
					<label for="uid_delete">
					UID: <input type="text" name="uid_delete" id="uid_delete" size="20" placeholder="123" />
					</label>
				</div>
			</li>
			<li class="list-group-item">
				<button class="btn btn-danger" type="submit" name="submitDelete">Conferma cancellazione</button>
			</li>
		</ul>
		</div>
	</div>
</div>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Modifica tabella attività</h3>
	</div>
	<div class="panel-body">
		<label>Aggiungi <input type="number" min="0" name="newBlocks" value="0" /> nuovi blocchi</label>
		<table id="ActivityTable" class="table table-bordered">
<?php
/* Intestazione con blocchi */
/* Ottiene i nomi delle colonne (blocchi) */
$blocks = $cogestione->blocchi();
echo '<tr>';
foreach($blocks as $id => $b) {
	$id = intval($id);
	echo "\n<th>"
		. "<input type=\"hidden\" name=\"block[$id][id]\" value=\"$id\" />\n"
		. '<div class="input-group">'
		. '<span class="checkbox input-group-addon">'
		. "<label><input type=\"checkbox\" id=\"block-delete-$id\" name=\"block[$id][delete]\" />DEL</label>"
		. "</span>"
		. "<input class=\"form-control\" type=\"text\" size=\"35\" name=\"block[$id][title]\" id=\"block-title-$id\" value=\"". htmlspecialchars($b, ENT_QUOTES, "UTF-8", false) . "\" />"
		. "</div>"
		. "</th>";
}
echo "\n</tr><tr>";
/* Procede colonna per colonna */
foreach($blocks as $i => $b) {
	echo '<td id="block-' . $i . '">';
	$activities = $cogestione->getActivitiesForBlock($i);
	
	/* Stampa tutte le attività che si svolgono contemporaneamente */
	foreach($activities as $row) {
		$title = htmlspecialchars($row['activity_title'], ENT_QUOTES, "UTF-8", false);
		$id = $row['activity_id'];
		$placeholder = htmlspecialchars('Descrizione per "' . $row['activity_title'] . '"');
		echo "\n<div class=\"set-activity\" id=\"activity-$id\">\n"
			. "<input type=\"hidden\" name=\"activity[$id][id]\" value=\"$id\" />\n"
			. "<input type=\"hidden\" name=\"activity[$id][block]\" value=\"$i\" />\n"
			. '<div class="input-group">'
			. '<span class="checkbox input-group-addon">'
			. "<label for=\"activity-delete-$id\">"
			. "<input id=\"activity-delete-$id\" name=\"activity[$id][delete]\" type=\"checkbox\" />"
			. "DEL</label></span>"
			. "<input class=\"form-control\" type=\"text\" class=\"activity-set-title\" id=\"activity-title-$id\" name=\"activity[$id][title]\" value=\"$title\" /><br />\n"
			. "</div>"
			. '<div class="input-group activity-size">'
			. '<span class="checkbox input-group-addon">'
			. "<label for=\"activity-vm-$id\">"
			. "<input id=\"activity-vm-$id\" name=\"activity[$id][vm]\" type=\"checkbox\" "
			. ($row['activity_vm'] ? 'checked="checked"' : '')
			. "/>VM18</label>"
			. "</span>"
			. "<input class=\"form-control\" type=\"number\" min=\"0\" id=\"activity-max-$id\" name=\"activity[$id][max]\" value=\""
			. intval($row['activity_size']) . "\" />\n"
			. '<span class="input-group-addon">posti</span>'
			. "</div>"
			. "<textarea class=\"form-control\" rows=\"4\" name=\"activity[$id][description]\" placeholder=\"$placeholder\">" . htmlspecialchars($row['activity_description']) . "</textarea>"
			. "\n</div>\n";
	}
	echo '</td>';
}
echo '</tr><tr>';
foreach($blocks as $i => $title) {
	echo '<td>';
	echo '<label>Aggiungi <input class="form-control" type="number" min="0" name="block[' . intval($i) . '][newRows]" value="0" /> nuove attività</label>';
	echo '</td>';
}
?>
</tr>
</table>
<button class="btn btn-primary" type="submit" name="submitActivities">Modifica attività</button>
</li>
</div>
</div>
</form>
<?php
	showFooter();
?>