<?php

//error_reporting(E_ALL);
//ini_set('display_errors', true);

// PHP 5 requires a default timezone to be set
date_default_timezone_set('GMT');
// Place the DB files outside of the public web directory so people don't download it!
define('DB', '../db.sq3');
// Number of seconds a user must wait to post more than two topics or comments
define('WAIT', 180);
// List of emails for admin users
define('ADMIN', ' you@example.com yourfriend@example.com');

function db()
{
	static $d;
	return $d = $d ?: (new PDO('sqlite:'.DB,0,0,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION)));
}

function q($q, $p=NULL)
{
	/*echo "<pre>$q</pre>";*/$s=db()->prepare($q);$s->execute(array_values((array)$p)); return $s;
}

function i($t, $d)
{
	q("INSERT INTO $t(" . join(',', array_keys($d)) . ')VALUES('
		. str_repeat('?,', count($d)-1). '?)', $d);
	return db()->lastInsertId();
}

function u($t, $d, $v)
{
	return q("UPDATE $t SET ". join('`=?,`', array_keys($d))
		. "=?WHERE i=?", $d + array($v))->rowCount();
}

function d($t, $c, $v)
{
	return q("DELETE FROM $t WHERE $c=?", $v)->rowCount();
}

function c($s)
{
	return nl2br(htmlspecialchars(trim(@iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $s))));
}

session_start();
$_SESSION += array('email' => '', 'admin' => '', 'check' => '');

if( ! $_SESSION['check'])
{
	checkdnsrr(join('.',array_reverse(explode('.',$p=getenv('REMOTE_ADDR')))).".opm.tornevall.org","A") && die('Bot');
	$_SESSION['check'] = 1;
}

$s = time();

// $t = Topic ID
// $h = Topic Headline
// $b = Topic/Comment Body
// $c = Comment ID
// $a = Auth assertion request (Login)
// $d = Delete request
extract($_REQUEST + array('b' => 0, 'h' => 0, 't' => 0, 'c' => 0, 'a' => 0, 'd' => 0));

if( ! is_file(DB))
{
	//unlink(DB);

	/*
	 * (T)opic's and (C)omments
	 * ID, Created Timestamp, IP Address, Email, Body Text
	 * o = Parent ID (if Comment) OR o = last modified (for ranking if Topic)
	 */
	$t='CREATE TABLE t (i INTEGER PRIMARY KEY,o INTEGER,c INTEGER,a TEXT,e TEXT, h TEXT, b TEXT)';
	q($t);
	$t='CREATE TABLE c (i INTEGER PRIMARY KEY,o INTEGER,c INTEGER,a TEXT,e TEXT, b TEXT)';
	q($t);
	
	for ($i=0; $i < 3; $i++)
	{
		$id = i('t', array(
			'o' => $s + $i,
			'c' => $s + $i,
			'a' => $p,
			'e' => 'user@example.com',
			'h' => "This is a topic about $i stuff",
			'b' => 'This is topic '. $i));
		
		for ($x=0; $x < 5; $x++)
		{ 
			i('c', array(
				'o' => $id,
				'c' => $s + $x,
				'a' => $p,
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
if($d && $_SESSION['admin'])
{
	// Also delete the comments that belong to this topic
	if($d == 't')
	{
		d('c', 'o', $t);
		d('t', 'i', $t);

		return new Exception("REMOVED");
	}
	else if($c)
	{
		d('c', 'i', $c);
	}
}

// Fetch the topic if we are loading it
if($t && !($o = q('SELECT * FROM t WHERE i=?',$t)->fetch()))
{
	return new Exception("MISSING");
}

// We are inserting a new topic or comment
if($b && $_SESSION['email'])
{
	$h = c($h);

	// Make sure they haven't posted more than twice every 3 minutes
	if(q('SELECT COUNT(*) FROM '. ($t?'t':'c').' WHERE a=? AND c>?', array($p, $s-WAIT))->fetchColumn() > 2)
	{
		return new Exception("OFTEN");
	}

	// Assume we are inserting a topic
	$d = array('o' => $s, 'c' => $s, 'a' => $p, 'e' => $_SESSION['email'], 'b' => c($b));

	// If this is a comment, then update the topic modified and add a reference to the topic
	if($t)
	{ 
		$d['o'] = $t;
		u('t', array('o' => $s), $t);
	}
	else
	{
		$d['h'] = c($h);
		if( ! $d['h']) return new Exception('HEADER');
	}

	i($t?'c':'t', $d);
}

// We are showing a topic
$rows = $t ? q('SELECT * FROM c WHERE o=? ORDER BY o DESC', array($t)) : q('SELECT * FROM t ORDER BY o DESC');