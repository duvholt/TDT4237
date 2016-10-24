<?php

namespace tdt4237\webapp\repository;

use PDO;
use tdt4237\webapp\models\Request;

class RequestRepository
{
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countAfter($timestamp, $ip){
        $sql  = sprintf("SELECT COUNT(*) FROM request WHERE ip='%s' AND time>$timestamp", $ip);
        $result = $this->pdo->query($sql);
        $row = $result->fetch();
        if($row === false) {
            return array(0);
        }
        return $row;
    }

    public function save(Request $request)
    {
        $ip          = $request->getIp();
        $time        = $request->getTime();

        $query = "INSERT INTO request (ip, time) "
            . "VALUES ('$ip', '$time')";

        $this->pdo->exec($query);
        return $this->pdo->lastInsertId();
    }
}
