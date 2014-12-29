<?php
namespace gazelle\services;

use gazelle\errors\SystemError;
use gazelle\core\Master;

class DB extends Service {

    protected $pdo;

    public function connect() {
        if (is_null($this->pdo)) {
            $dbc = $this->master->settings->database;
            # TODO: specify port & socket in case they differ from default
            $this->pdo = new \PDO("mysql:host={$dbc->host};dbname={$dbc->db}", $dbc->username, $dbc->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }
    }

    public function raw_query($sql, $parameters = array()) {
        $this->connect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parameters);
        return $stmt;
    }

}
