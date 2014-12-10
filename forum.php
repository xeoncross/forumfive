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

// Number of seconds a trusted user must wait
define('TRUSTED_WAIT', 30);

// After a user has posted this many topics/comments we trust them
define('TRUST_COUNT', 50);

// Enable IP checking to stop bots (slows site)
define('IP_CHECK', false);

// Allow new users to register (existing users can still login)
define('ALLOW_REGISTER', true);

// List of emails for admin users
define('ADMIN', ' david@xeoncross.com yourfriend@example.com');

// HTTP or local file for email domain blacklist (false to disable checks)
define('EMAIL_BLACKLIST', 'https://raw.githubusercontent.com/martenson/disposable-email-domains/master/disposable_email_blacklist.conf');

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
		. "=?WHERE id=?", $data + array($value))->rowCount();
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
$_SESSION += array('email' => '', 'admin' => '', 'trusted' => 0, 'check' => '', 'posts' => 0);
$ip = getenv('REMOTE_ADDR');

if(IP_CHECK AND ! $_SESSION['check'])
{
	checkdnsrr(join('.',array_reverse(explode('.',$ip))).".opm.tornevall.org","A") && die('Bot IP');
	$_SESSION['check'] = 1;
}

// Append to the array: Topic ID, Topic Headline, Topic/Comment Body, Comment ID, Delete request
extract($_REQUEST + array(
	'userID' => 0, 'topicID' => 0, 'commentID' => 0, 'title' => 0, 'body' => 0, 'delete' => 0
));

if( ! is_file(DB))
{
	//unlink(DB);

	/*
	 * (u)pdated timestamp and (c)reated timestamp
	 */
	query('CREATE TABLE topic (
		id INTEGER PRIMARY KEY,
		u INTEGER,
		c INTEGER,
		ip TEXT,
		email TEXT,
		title TEXT,
		body TEXT
	)');

	query('CREATE TABLE comment (
		id INTEGER PRIMARY KEY,
		topic_id INTEGER,
		c INTEGER,
		ip TEXT,
		email TEXT,
		body TEXT
	)');

	query('CREATE TABLE user (
		id INTEGER PRIMARY KEY,
		email TEXT UNIQUE not null,
		logins INTEGER DEFAULT 0,
		banned INTEGER DEFAULT 0,
		posts INTEGER DEFAULT 0,
		c INTEGER
	)');

	for ($i=0; $i < 3; $i++)
	{
		$id = insert('topic', array(
			'u' => time() + $i,
			'c' => time() + WAIT + $i,
			'ip' => $ip,
			'email' => 'user@example.com',
			'title' => "This is a topic about $i stuff",
			'body' => "<p>This is topic $i</p>"));

		for ($x=0; $x < 5; $x++)
		{
			insert('comment', array(
				'topic_id' => $id,
				'c' => time() + WAIT + $x,
				'ip' => $ip,
				'email' => 'user@example.com',
				'body' => "<p>This is comment $x</p>"));
		}

		unset($id);
	}
}

// Login with BrowserID
if(isset($_POST['a']))
{
	sleep(1); // rate-limit

	curl_setopt_array($h = curl_init('https://verifier.login.persona.org/verify'),array(
		CURLOPT_RETURNTRANSFER=>1,
		CURLOPT_POST=>1,
		CURLOPT_POSTFIELDS=>"assertion=" .$_POST['a'] . "&audience=http://".getenv('HTTP_HOST')
	));

	if(($d = json_decode(curl_exec($h))) && $d->status == 'okay')
	{
		$emails = file(EMAIL_BLACKLIST, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if(in_array($d->email, $emails)) {
			throw new Exception("EMAIL");
		}

		// Changing emails by logging in while logged in
		if($_SESSION['email'] AND $_SESSION['email'] !== $d->email) {
			query('UPDATE user SET email = ? WHERE email = ?', array($d->email, $_SESSION['email']));
		}

		$user = query('SELECT * FROM user WHERE email = ?', array($d->email))->fetch();

		if($user) {

			if($user->banned) {
				throw new Exception("BANNED");
			}

			// We stop doing flood-limiting as much for trusted members
			$_SESSION['posts'] = $user->posts;

			// We show emails for trusted members
			if($_SESSION['posts'] >= TRUST_COUNT) {
				$_SESSION['trusted'] = 1;
			}

			query('UPDATE "user" SET logins = (logins + 1) WHERE email = ?', array($d->email));

		} else {

			if( ! ALLOW_REGISTER) {
				throw new Exception("REGISTER");
			}

			insert('user', array(
				'email' => $d->email,
				'c' => time()
			));

		}

		if(strpos(ADMIN, ($_SESSION['email'] = $d->email)))
		{
			$_SESSION['admin'] = true;
		}
	}

	ob_end_clean();
	die('{status:true}');
}


// We don't want to waste resources on every page re-loading the user record
// Only check for banning for the account when they try to modify the site
if($_SESSION['email'] AND ($body OR $delete)) {

	$banned = query('SELECT banned FROM user WHERE email = ?', array($_SESSION['email']))->fetchColumn();

	if($banned) {
		$_SESSION['email'] = $_SESSION['admin'] = null;
		throw new Exception("BANNED");
	}
}

// Trying to delete a topic/comment?
if($delete && $_SESSION['admin'])
{
	// Also delete the comments that belong to this topic
	if($delete == 'topic') {

		delete('comment', 'topic_id', $topicID);
		delete('topic', 'id', $topicID);

		return new Exception("REMOVED");
	
	} elseif($delete == 'user') { // We don't actually delete users...
		
		query('UPDATE user SET banned = 1 WHERE email = ?', array($userID));

	} else if($commentID) {

		delete('comment', 'id', $commentID);
	}
}

// Fetch the topic if we are loading it
if($topicID && !($topic = query('SELECT * FROM topic WHERE id = ?', $topicID)->fetch()))
{
	return new Exception("MISSING");
}

// Fetch the user if we are loading them
if($userID && !($user = query('SELECT * FROM user WHERE id = ?', $userID)->fetch()))
{
	return new Exception("MISSING");
}

// We are inserting a new topic or comment
if($body && $_SESSION['email'])
{
	if(mb_strlen($body) > ($topicID ? 2000 : 7000)) {
		return new Exception('LENGTH');
	}

	$wait = WAIT;
	// Admin's and trusted users can post more often
	if($_SESSION['admin'] OR $_SESSION['posts'] >= TRUST_COUNT) {
		$_SESSION['trusted'] = true;
		$wait = TRUSTED_WAIT;
	}

	// Make sure they haven't posted more than twice every 3 minutes
	$sql = 'SELECT COUNT(*) FROM '. ($topicID ? 'comment' : 'topic').' WHERE ip = ? AND c > ?';
	if(query($sql, array($ip, time()-$wait))->fetchColumn() > 2) {
		sleep(1); // Flood control
		return new Exception("OFTEN");
	}

	$body = DOMCleaner::purify($body);

	// Assume we are inserting a topic
	$data = array(
		'c' => time(),
		'ip' => $ip,
		'email' => $_SESSION['email'],
		'body' => $body
	);

	// If this is a comment, add a reference to the topic, then update the topic modified time
	if($topicID) {
	
		$data['topic_id'] = $topicID;
		update('topic', array('u' => time()), $topicID);
	
	} else {
	
		$data['title'] = filter($title);
		$data['u'] = time();
		if( ! $data['title'] OR mb_strlen($data['title']) > 80) {
			return new Exception('HEADER');
		}

	}

	insert($topicID ? 'comment' : 'topic', $data);
	query('UPDATE user SET posts = (posts + 1) WHERE email = ?', $_SESSION['email']);
	$_SESSION['posts']++;
}

// Close the file now so AJAX an use it
session_write_close();

// We are showing a topic
if($userID AND !empty($user)) {
	$rows = query('SELECT * FROM comment WHERE email = ? ORDER BY id DESC LIMIT 10', array($user->email));
} elseif($topicID) {
	$rows = query('SELECT * FROM comment WHERE topic_id = ? ORDER BY id DESC LIMIT 100', array($topicID));
} else {
	$rows = query('SELECT * FROM topic ORDER BY id DESC');
}


/***************************END****************************/


/**
 * DOMCleaner
 *
 * Requires LIBXML / PHP DOM which is now standard in PHP
 * @author David Pennington
 * @url http://github.com/xeoncross
 */
class DOMCleaner {

	public static $whitelist = array(
		'a' => array('href'),
		'b', 'em', 'i', 'u', 'strike', 'sup', 'sub',
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'p', 'blockquote','pre', 'code','ul','ol','li',
		'img' => array('src', 'alt', 'title'),
		'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
		'br', '#text', 'html', 'body',

		// Youtube, vimeo, etc...
		'iframe' => array('src', 'class', 'allowtransparency', 'allowfullscreen', 'width', 'height', 'frameborder')
	);


	public static function decode($string)
	{
		while (strcmp($string, ($temp = html_entity_decode($string, ENT_QUOTES, 'UTF-8'))) !== 0) {
			$string = $temp;
		}

		return $string;
	}

	public static function purify($html, array $whitelist = null, $protocols = 'http|https|ftp')
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();

		if (is_object($html)) {

			if ( ! in_array($html->nodeName, array_keys($whitelist))) {
				$html->parentNode->removeChild($html);
				return;
			}

			if ($html->hasChildNodes() === true) {

				// Purify/Delete child elements in reverse order so we don't messup DOM tree
				foreach (range($html->childNodes->length - 1, 0) as $i) {
					static::purify($html->childNodes->item($i), $whitelist, $protocols);
				}
			}

			if ($html->hasAttributes() === true) {

				foreach (range($html->attributes->length - 1, 0) as $i) {
					$attribute = $html->attributes->item($i);

					if( ! $attribute->value OR ! in_array($attribute->name, $whitelist[$html->nodeName])) {
						$html->removeAttributeNode($attribute);
						continue;
					}

					$value = static::decode($attribute->value);
					if(strpos($value, ':') !== false) {
						if(preg_match('~([^:]{0,10}):~', $value, $match)) {
							if ( ! in_array(strtolower(trim($match[1])), $protocols)) {
								$html->removeAttributeNode($attribute);
							}
						}
					}
				}
			}

			return;
		}

		if( ! trim($html)) {
			return;
		}

		$dom = new DomDocument();
		if(! $dom->loadHTML($html)) {
			return;
		}
		
		if( ! $whitelist) {
			$whitelist = static::$whitelist;
		}

		// Allow tags to be given without the "tag => array()" syntax
		foreach ($whitelist as $tag => $attributes) {
			if (is_int($tag)) {
				unset($whitelist[$tag]);
				$whitelist[$attributes] = array();
			}
		}

		$protocols = explode('|', strtolower($protocols));
		static::purify($dom->documentElement, $whitelist, $protocols);

		return preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());
	}

}

