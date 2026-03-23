<?php
 
namespace Cfo\SisConsultas\database;

use PDO;
use PDOException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();
 
/*  //////////////////////////////////////
    Banco de dados MySQL
    Servidor Tesla - Locaweb
*/  //////////////////////////////////////

class Database2 {
  private $connection;
  private static $_instance;

  private $dbhost;
  private $dbuser;
  private $dbpass;
  private $dbname;
  /*
      * Get an instance of the Database
      * @return Instance
  */
  public static function getInstance () {
    if (! self::$_instance) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  // Constructor
  private function __construct () {

    // Define os valores dos atributos de configuração do banco de dados
    $this->dbhost = $_ENV['DB2_HOST'];
    $this->dbuser = $_ENV['DB2_USERNAME'];
    $this->dbpass = $_ENV['DB2_PASSWORD'];
    $this->dbname = $_ENV['DB2_NAME'];

    try {
      $this->connection = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname.';charset=utf8', $this->dbuser, $this->dbpass);
      $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Error handling
    } catch (PDOException $e) {
      error_log("Database2 (MySQL) - Falha ao conectar: " . $e->getMessage());
      $this->connection = null;
    }
  }

  // Magic method clone is empty to prevent duplication of connection
  private function __clone () {}

  public function isConnected(): bool {
    return $this->connection !== null;
  }

  // Get the connection
  public function getConnection ()
  {
    if ($this->connection === null) {
      throw new PDOException("Conexão com MySQL (DB2) indisponível.");
    }
    return $this->connection;
  }
}
