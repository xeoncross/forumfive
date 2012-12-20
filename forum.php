<?php

// Error default reporting
//error_reporting(E_ALL ^ E_NOTICE);

// Enable the error display
//ini_set('display_errors', 'On');

// Disable PHP error logs
//ini_set('log_errors', 'Off');

// PHP 5 requires a default timezone to be set
date_default_timezone_set('GMT');

// Place the DB files outside of the public web directory so people don't download it!
define('DB', dirname(dirname(__FILE__)) . '/database.sq3');

// Number of seconds a user must wait to post more than two topics or comments
define('WAIT', 180);

// List of emails for admin users
define('ADMIN', ' you@example.com yourfriend@example.com');

function db($args = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION))
{
	static $db;
	$db = $db ?: (new PDO('sqlite:' . DB, 0, 0, $args));
	return $db;
}

function query($sql, $params = NULL)
{
	$s = db()->prepare($sql);
	$s->execute(array_values((array) $params));
	return $s;
}

function insert($table, $data)
{
	query("INSERT INTO $table(" . join(',', array_keys($data)) . ')VALUES('
		. str_repeat('?,', count($data)-1). '?)', $data);
	return db()->lastInsertId();
}

function update($table, $data, $value)
{
	return query("UPDATE $table SET ". join('`=?,`', array_keys($data))
		. "=?WHERE i=?", $data + array($value))->rowCount();
}

function delete($table, $field, $value)
{
	return query("DELETE FROM $table WHERE $field = ?", $value)->rowCount();
}

function filter($string)
{
	return nl2br(htmlspecialchars(trim(@iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $string))));
}

session_start();
$_SESSION += array('email' => '', 'admin' => '', 'check' => '');
$ip = getenv('REMOTE_ADDR');

if( ! $_SESSION['check'])
{
	checkdnsrr(join('.',array_reverse(explode('.',$ip))).".opm.tornevall.org","A") && die('Bot');
	$_SESSION['check'] = 1;
}

// Append to the array: Topic ID, Topic Headline, Topic/Comment Body, Comment ID, Delete request
extract($_REQUEST + array('topicID' => 0, 'headline' => 0, 'body' => 0, 'commentID' => 0, 'delete' => 0));

if( ! is_file(DB))
{
	//unlink(DB);

	/*
	 * Topic: (I)D, (O) Last Modified, (C)reated Timestamp, IP (A)ddress, (E)mail, (H)eadline, (B)ody Text
	 * Comment: (I)D, (O) Topic ID, (C)reated Timestamp, IP (A)ddress, (E)mail, (B)ody Text
	 */
	query('CREATE TABLE t (i INTEGER PRIMARY KEY,o INTEGER,c INTEGER,a TEXT,e TEXT, h TEXT, b TEXT)');
	query('CREATE TABLE c (i INTEGER PRIMARY KEY,o INTEGER,c INTEGER,a TEXT,e TEXT, b TEXT)');

	for ($i=0; $i < 3; $i++)
	{
		$id = insert('t', array(
			'o' => time() + $i,
			'c' => time() + WAIT + $i,
			'a' => $ip,
			'e' => 'user@example.com',
			'h' => "This is a topic about $i stuff",
			'b' => 'This is topic '. $i));

		for ($x=0; $x < 5; $x++)
		{
			insert('c', array(
				'o' => $id,
				'c' => time() + WAIT + $x,
				'a' => $ip,
				'e' => 'user@example.com',
				'b' => 'This is comment '. $x));
		}

		unset($id);
	}
}

// Login with BrowserID
if(isset($_POST['a']))
{
	curl_setopt_array($h = curl_init('https://verifier.login.persona.org/verify'),array(
		CURLOPT_RETURNTRANSFER=>1,
		CURLOPT_POST=>1,
		CURLOPT_POSTFIELDS=>"assertion=" .$_POST['a'] . "&audience=http://".getenv('HTTP_HOST')
	));

	if(($d = json_decode(curl_exec($h))) && $d->status == 'okay')
	{
		if(strpos(ADMIN, ($_SESSION['email'] = $d->email)))
		{
			$_SESSION['admin'] = true;
		}
	}
	ob_end_clean();
	die('{status:true}');
}

// Trying to delete a topic/comment?
if($delete && $_SESSION['admin'])
{
	// Also delete the comments that belong to this topic
	if($delete == 't')
	{
		delete('c', 'o', $topicID);
		delete('t', 'i', $topicID);

		return new Exception("REMOVED");
	}
	else if($commentID)
	{
		delete('c', 'i', $commentID);
	}
}

// Fetch the topic if we are loading it
if($topicID && !($topic = query('SELECT * FROM t WHERE i=?',$topicID)->fetch()))
{
	return new Exception("MISSING");
}

// We are inserting a new topic or comment
if($body && $_SESSION['email'])
{
	$headline = filter($headline);

	// Make sure they haven't posted more than twice every 3 minutes
	if(query('SELECT COUNT(*) FROM '. ($topicID ? 't' : 'c').' WHERE a=? AND c>?', array($ip, time()-WAIT))->fetchColumn() > 2)
	{
		return new Exception("OFTEN");
	}

	// Assume we are inserting a topic
	$data = array('o' => time(), 'c' => time(), 'a' => $ip, 'e' => $_SESSION['email'], 'b' => filter($body));

	// If this is a comment, add a reference to the topic, then update the topic modified time
	if($topicID)
	{
		$data['o'] = $topicID;
		update('t', array('o' => time()), $topicID);
	}
	else
	{
		$data['h'] = filter($headline);
		if( ! $data['h'] OR mb_strlen($data['h']) > 80) return new Exception('HEADER');
	}

	insert($topicID ? 'c' : 't', $data);
}

// We are showing a topic
$rows = $topicID ? query('SELECT * FROM c WHERE o=? ORDER BY o DESC', array($topicID)) : query('SELECT * FROM t ORDER BY o DESC');
