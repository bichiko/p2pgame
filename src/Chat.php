<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;


class ConnectToDB{
	protected $dbh;
	protected $stmt;

	public function __construct(){
		try{
			$this->dbh = new PDO("mysql:host=localhost;dbname=ratchet;charset=utf8", 'root', 'password');
		}
		catch(PDOException $e){
		    echo $e->getMessage();
		}
	}

	public function query($query){
		$this->stmt = $this->dbh->prepare($query);
	}

	//Binds the prep statement
	public function bind($param, $value, $type = null){
 		if (is_null($type)) {
  			switch (true) {
    			case is_int($value):
      				$type = PDO::PARAM_INT;
      				break;
    			case is_bool($value):
      				$type = PDO::PARAM_BOOL;
      				break;
    			case is_null($value):
      				$type = PDO::PARAM_NULL;
      				break;
    				default:
      				$type = PDO::PARAM_STR;
  			}
		}
		$this->stmt->bindValue($param, $value, $type);
	}

	public function execute(){
		$this->stmt->execute();
	}

	public function resultSet(){
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function lastInsertId(){
		return $this->dbh->lastInsertId();
	}

	public function single(){
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function rowCount(){
		$this->execute();
		return $this->stmt->rowCount();
	}

	public function __destruct(){
		$this->dbh = null;
		$this->stmt = null;
	}
}


class Chat implements MessageComponentInterface {
    protected $clients;

    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->db = new ConnectToDB();	
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
    	$sessionId = $from->WebSocket->request->getCookies()['PHPSESSID'];

    	echo var_dump($from->WebSocket);
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
            	$this->db->query("INSERT INTO chat (user_id, text) VALUES (:user_id, :text)");
            	$this->db->bind(':user_id', $from->resourceId);
            	$this->db->bind(':text', $msg);
            	$this->db->execute();


                // The sender is not the receiver, send to each client connected
                
            	$this->db->query("SELECT * FROM chat");
            	// var_dump($this->db->resultSet());
                $client->send(json_encode(
                		// array_merge(
                			$this->db->resultSet()//,
                			// [ 'session' => $sessionId ]
                		// )
                	));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
    	$this->db->query("DELETE FROM chat WHERE 1");
    	$this->db->execute();
        echo "Connection {$conn->resourceId} has disconnected and DB is clear\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}



