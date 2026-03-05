<?php
 
namespace Cfo\SisConsultas\database;

use PDO;
use PDOException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

/*  //////////////////////////////////////
    Banco de dados MySQL - Impressão Digital
    Servidor - Banco de Identidade
*/  //////////////////////////////////////

class Database6 {
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
    $this->dbhost = $_ENV['DB6_HOST'] ?? '';
    $this->dbuser = $_ENV['DB6_USERNAME'] ?? '';
    $this->dbpass = $_ENV['DB6_PASSWORD'] ?? '';
    $this->dbname = $_ENV['DB6_NAME'] ?? '';
    $dbport = $_ENV['DB6_PORT'] ?? '3306';

    // Validar se todas as variáveis obrigatórias foram definidas
    if (empty($this->dbhost) || empty($this->dbuser) || empty($this->dbname)) {
      die("Erro de configuração DB6: Verifique se as variáveis DB6_HOST, DB6_USERNAME e DB6_NAME estão definidas no arquivo .env");
    }

    try {
      // Montar string de conexão com porta (força conexão TCP/IP)
      $dsn = 'mysql:host=' . $this->dbhost . ';port=' . $dbport . ';dbname=' . $this->dbname . ';charset=utf8';
      $this->connection = new PDO($dsn, $this->dbuser, $this->dbpass);
      $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      // Error handling
    } catch (PDOException $e) {
      die("Falha ao conectar ao banco de dados DB6: " . $e->getMessage() . "<br>Host: " . htmlspecialchars($this->dbhost) . " | Porta: " . $dbport . " | Banco: " . htmlspecialchars($this->dbname));
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

