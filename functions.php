<?php
require_once('config.php');

function isEnabled() {
	/* Manual override */
	if(COGE_MANUAL) {
		return COGE_MANUAL_ENABLED;
	}
	
	/* Ore di inizio e di fine */
	$dtz = new DateTimeZone('Europe/Rome');
	$beginTime = new DateTime(START_TIME, $dtz);
	$endTime = new DateTime(END_TIME, $dtz);

	$now = new DateTime(null, $dtz);
	
	return ($now >= $beginTime AND $now <= $endTime);
}

function initDB() {
	/* Carica le credenziali giuste per il database.
	   Variabili necessarie:
		$db_host = nome dell'host MySQL
		$db_name = nome del database
		$db_user = nome utente
		$db_password = password
	*/
			
	// Inizializza il database
	$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$db->set_charset('utf8');
	if(!$db)
		die("<p class=\"error\">Errore nella connessione al database!</p>");
	return $db;
}

function blocchi($db) {
	static $blocks = FALSE;
	if($blocks === FALSE) {
		// Ottiene id e nome dei blocchi come array associativo.
		$res = $db->query("SELECT * FROM blocchi ORDER BY id;");
		if(!$res) die("Errore nella selezione dei blocchi!");
		$blocks=Array();
		while($row = $res->fetch_assoc()) {
			$blocks[$row['id']] = $row['title'];
		}
	}
    return $blocks;
}

function getActivityInfo($id, $db) {
    /* Ottiene title, time e n. prenotati di una data attività.
        Restituisce una riga siffatta:
        (id, time, max, title, vm, prenotati)
    */
    $res = $db->query('SELECT attivita.*, COUNT(prenotazioni.id) AS prenotati
                            FROM attivita
                            LEFT JOIN prenotazioni ON attivita.id=prenotazioni.activity
                            WHERE attivita.id=' . $id . '
                            GROUP BY attivita.id;');
    if(!$res) die("Error whiule fetching activity info!");
    $row = $res->fetch_assoc();
    return $row;
}


function classi($db) {
	static $classi = FALSE;
	if($classi === FALSE) {
		// Ottiene l'array delle classi.
		$res = $db->query("SELECT * FROM classi ORDER BY classe;");
		if(!$res) die("Error while selecting classes!");
		$classi=Array();
		while($row = $res->fetch_assoc()) {
			$classi[] = $row['classe'];
		}
	}
    return $classi;
    
}

function isSubscribed($name, $surname, $class, $db) {
    // Determina se l'utente è già iscritto.
    $res = $db->query('SELECT id
                            FROM prenotazioni
                            WHERE name="' . $name . '"
                            AND surname="' . $surname . '"
                            AND class="' . $class . '";');
    if(!$res) die('Errore!');
    $n = $res->num_rows;
    return ( $n ? TRUE : FALSE );
}

function getSubscriptionsNumber($db)
{
	// Ottiene il numero di prenotazioni
	$res = $db->query('SELECT COUNT(prenotazioni.id) AS c
							FROM prenotazioni
							WHERE prenotazioni.time=1;');
	if(!$res) die("Errore nell'ottenere il numero di prenotazioni!");
	$row = $res->fetch_assoc();
	return intval($row['c']);
}

function lastID($db)
{
	// Ottiene l'ultimo ID
	$res = $db->query('SELECT MAX(id) AS m FROM attivita;');
	if(!$res) die("Errore nell'ottenere l'ultimo ID");
	$row = $res->fetch_assoc();
	return intval($row['m']);
}
?>