<?php

namespace Cfo\SisConsultas\database;

use Redis;
use Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

/*  //////////////////////////////////////
    Banco de dados Redis
    Servidor Local Docker
*/  //////////////////////////////////////

class Database4 {

  private $connection;
  private static $_instance;

  private $dbhost;
  private $dbport;
  private $dbpass;
  
  /*
   * Get an instance of the Database
   * @return Instance
  */
  public static function getInstance () {
    if (!self::$_instance) {
        self::$_instance = new self();
    }
    return self::$_instance;
  }

  // Constructor
  private function __construct () {
    $this->dbhost = $_ENV['REDIS_HOST'];
    $this->dbport = $_ENV['REDIS_PORT'];
    $this->dbpass = $_ENV['REDIS_PASSWORD'];

    try {
          $this->connection = new Redis();
          $this->connection->connect($this->dbhost, $this->dbport);
          $this->connection->auth($this->dbpass);

      // Error handling
      } catch (Exception $e) {
          error_log("Database4 (Redis) - Falha ao conectar: " . $e->getMessage());
          $this->connection = null;
      }
  }

  // Magic method clone is empty to prevent duplication of connection
  private function __clone () {}

  public function isConnected(): bool {
    return $this->connection !== null;
  }

  // Get the connection
  public function getConnection () {
      if ($this->connection === null) {
        throw new \Exception("Conexão com Redis indisponível.");
      }
      return $this->connection;
  }

}