<?php

class Server
{
	private $db = null;

	function __construct($db)
	{
		$this->db = $db;

	}

	public function play()
	{

		$key = !is_array($_GET['key']) ? $_GET['key'] : null;
		if (!isset($key)) {
			die("Invalid data");
		}
		if (isset($key) && !is_array($key)) {
			//Validera spelarens nyckel mot den i databasen.
			$sth = $this->db->prepare("SELECT `id`,`match_id` FROM `players` WHERE `player`=:key", array(':key' => $key));
			$sth->bindParam(':key', $key);
			$sth->execute();
			//Fanns inte i databasen => ogiltig förfrågan.
			if ($sth->rowCount() == 0) {
				header("HTTP/1.1 403 Unauthorized");
				die("Unauthorized");
			}
			$matchID = $sth->fetch()['match_id'];
			unset($_SESSION['player']); //Rensa gammalt spel
			$_SESSION['player'] = $key; //Skapa en session för detta spel
			header("location: index.php?match=" . $matchID);
			exit();
		} else {
			header("HTTP/1.1 403 Unauthorized");
			die("Unauthorized");
		}
	}

	public function authenticateServer($serverToken)
	{
		if (isset($_POST['token']) && $_POST['token'] === $serverToken && !is_array($serverToken)) {
			//Nu vet vi att servern har skickat förfrågan samt att vi nu måste lägga till spelarna i vår databas.
			$this->db->beginTransaction();

			//Börjar med att skapa en match
			$sth = $this->db->query("INSERT INTO `matches`() VALUES()");
			$matchID = $this->db->lastInsertId();

			//Lägg sedan till alla spelare i players vektorn med den matchens id som returnerades ovan.
			$players = isset($_POST['keys']) ? json_decode($_POST['keys']) : array();
			$this->createPlayers($players, $matchID);
		} else {
			header("HTTP/1.1 403 Unauthorized");
			die("Unauthorized");
		}
	}

	private function createPlayers($players, $matchID)
	{
		$sth = $this->db->prepare("INSERT INTO `players`(`player`,`match_id`,`name`) VALUES(:player,:matchID, :name)");
		foreach ($players as $player) {
			$sth->bindParam(':player', $player[0]);
			$sth->bindParam(':name', $player[1]);
			$sth->bindParam(':matchID', $matchID);
			$sth->execute();
		}
		$this->db->commit();
	}
}