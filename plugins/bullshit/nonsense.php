<?
/******************************************************************
 * Nonsense Generator                                             *
 * version 3.0.0                                                  *
 * https://sourceforge.net/projects/nonsensegen/                  *
 ******************************************************************
 * Copyright © 2002-2005 Chris Throup, Jeff Holman, et al         *
 *                                                                *
 * This program is free software; you can redistribute it and/or  *
 * modify it under the terms of the GNU General Public License    *
 * as published by the Free Software Foundation; either version 2 *
 * of the License, or (at your option) any later version.         *
 *                                                                *
 * See license.txt for the terms of the GNU GPL.                  *
 *                                                                *
 * See help.html for installation and usage instructions.         *
 ******************************************************************/


// In most cases, you won't need to edit anything in this file.

$this_path = get_included_path('nonsense.php');   // Name of this file.
define("WORDLIST_METHOD", "");                  // Leave blank for local word lists.  Set to 'Db' for MySQL database.
define("NONSENSE_PATH", "{$this_path}db/");       // Path to the word lists.
define("DB_LOCATION", "localhost");               // Location of MySQL database.
define("DB_NAME", "nonsensegen");                 // Name of MySQL database.
define("DB_USER", "root");                        // Username for MySQL access.
define("DB_PASSWORD", "");                        // Password for MySQL access.
define("DB_PREFIX", "nons_");                     // Prefix for database tables.

// No need to edit anything below.

class Wordlist {
	function Wordlist() {
		$this->lists = array("interjections", "determiners", "adjectives", "nouns", "adverbs", "verbs", "prepositions", "conjunctions", "comparatives");
		$this->vowels = array('a','e','i','o','u');
		$this->wordlists = array();
		$this->output = '';

		foreach ($this->lists as $part) {
			$this->wordlists[$part] = file(NONSENSE_PATH."$part.txt");
		}
	}
}

class WordlistDb {
	function WordlistDb() {
		$this->lists = array("interjections", "determiners", "adjectives", "nouns", "adverbs", "verbs", "prepositions", "conjunctions", "comparatives");
		$this->vowels = array('a','e','i','o','u');
		$this->wordlists = array();
		$this->output = '';
		
		$this->database = new Database();
		
		foreach ($this->lists as $part) {
			$this->database->reset_search_variables();
			$this->database->set_table(DB_PREFIX.$part);
			$this->database->run_query();
			if ($this->database->return_number_rows()) {
				$this->wordlists[$part] = array();
				while ($row_data = $this->database->get_row()) {
					$this->wordlists[$part][] = $row_data['word'];
				}
			}
		}
	}
}

	class MySQL {
	//	Database variables
		var $connection;
		var $database;
	// Query variables
		var $last_query;
		var $result;
		var $error;
		var $table;
		var $search_data;
		var $sort_order;
		var $returned_results;
		
		function MySQL($host, $name, $user, $password) {
			$this->search_data = array();

			$this->connection = mysql_connect($host, $user, $password) or die("Problem connecting!");
			$this->database = mysql_select_db($name, $this->connection) or die(mysql_error($this->connection));
			
			return $this->database;
		}
		
	// General functions
	
		function query($SQLquery) {
//			print($SQLquery); // BAD - FOR TESTING
			$this->last_query = $SQLquery;
			$this->result = mysql_query($this->last_query, $this->connection) or die("problem running query");
			return $this->result;
		}
		
		function run_query() {
			$query = 'SELECT *';
			if (isset($this->table)) {
				$query .= " FROM {$this->table}";
			}
			if (sizeof($this->search_data) > 0) {
				$query .= ' WHERE 1';
				foreach ($this->search_data as $search_term) {
					$query .= " AND {$search_term}";
				}
			}
			if (isset($this->sort_order)) {
				$query .= " ORDER BY {$this->sort_order}";
			}
			return $this->query($query);
		}
		
		function get_row() {
			$this->returned_results = mysql_fetch_array($this->result, MYSQL_ASSOC);
			return $this->returned_results;
		}
		
		function set_table($table) {
			$this->table = "$table";
			return true;
		}
		
		function set_search_value($key, $value) {
			$this->search_data[] = "{$key}='{$value}'";
			return true;
		}

		function set_search_null($key) {
			$this->search_data[] = "{$key} IS NULL";
			return true;
		}
		
		function set_search_like($key, $value) {
			$this->search_data[] = "{$key} LIKE '{$value}'";
			return true;
		}
		
		function set_search_contains($key, $value) {
			$this->search_data[] = "{$key} LIKE '%{$value}%'";
			return true;
		}
		
		function set_sort_order($order) {
			$this->sort_order = $order;
			return true;
		}
		
		function reset_search_variables() {
			array_splice($this->search_data, 0);
			unset($this->sort_order);
		}
		
		function return_result($key) {
			if (array_key_exists($key, $this->returned_results)) {
				return $this->returned_results[$key];
			} else {
				return false;
			}
		}
		
		function return_number_rows() {
			return mysql_num_rows($this->result);
		}
	}

	class Database extends MySQL {
		function Database() {
			$this->MySQL(DB_LOCATION, DB_NAME, DB_USER, DB_PASSWORD);
		}
	}

class Nonsense {
	function Nonsense() {
		switch(WORDLIST_METHOD) {
			case "Db":
				$this->collection = new WordlistDb();
				break;
			case "":
			default:
				$this->collection = new Wordlist();
				break;
		}
	}

	function sentence($numSentences = 1) {
		$type = mt_rand(0,1);
		
		for ($i=0; $i<2; $i++) {
			foreach ($this->collection->lists as $part) {
				${$part}[$i] = trim($this->collection->wordlists[$part][mt_rand(0,count($this->collection->wordlists[$part]) - 1)]);
			}
		
			if ($determiners[$i] == "a") {
				foreach ($this->collection->vowels as $vowel) {
					if (($type && ($adjectives[$i][0] == $vowel)) || (!$type && ($nouns[$i][0] == $vowel))) {
						$determiners[$i] = "an";
					}
				}
			}
		}
		
		$sentence = ($type ?
		"$interjections[0], $determiners[0] $adjectives[0] $nouns[0] $adverbs[0] $verbs[0] $prepositions[0] $determiners[1] $adjectives[1] $nouns[1]." :
		"$interjections[0], $determiners[0] $nouns[0] is $comparatives[0] $adjectives[0] than $determiners[1] $adjectives[1] $nouns[1].");
		
		if ($numSentences > 1) {
			$sentence .= " " . $this->sentence($numSentences-1);
			$this->output = $sentence;
			return $this->output;
		}
		$this->output = $sentence;
		return $this->output;
	}
	
	function word($numWords = 1) {
		$word_list = '';
		
		for ($count = 1; $count <= $numWords; $count++) {
			if ($count > 1) {
				$word_list .= ' ';
			}
			$list_to_use = mt_rand(0, sizeof($this->collection->wordlists) - 1);
			$word_to_use = mt_rand(0, sizeof($this->collection->wordlists[$this->collection->lists[$list_to_use]]) - 1);
			
			$word = $this->collection->wordlists[$this->collection->lists[$list_to_use]][$word_to_use];
			
			if (strpos($word, ' ')) {
				$word = substr_replace($word, '', strpos($word, ' '));
			}
			
			$word = trim($word);
			$word_list .= strtolower($word);
		}
		$this->output = $word_list;
		return $this->output;
	}
}

function get_included_path($file_name) {
	$included_files = get_included_files();
	$path = '';
	foreach ($included_files as $file) {
		if (preg_match("/({$file_name})$/", $file)) {
			$path = preg_replace("/({$file_name})$/", '', $file);
		}
	}
	return $path;
}
?>