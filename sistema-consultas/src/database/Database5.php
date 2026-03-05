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

class Database5 {
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
    $this->dbhost = $_ENV['DB5_HOST'];
    $this->dbuser = $_ENV['DB5_USERNAME'];
    $this->dbpass = $_ENV['DB5_PASSWORD'];
    $this->dbname = $_ENV['DB5_NAME'];

    try {
      $this->connection = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname.';charset=utf8', $this->dbuser, $this->dbpass);
      $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Error handling
    } catch (PDOException $e) {
      die("Falha ao conectar ao banco de dados: " . $e->getMessage());
    }
  }

  // Magic method clone is empty to prevent duplication of connection
  private function __clone () {}

  // Get the connection
  public function getConnection ()
  {
    return $this->connection;
  }
}
