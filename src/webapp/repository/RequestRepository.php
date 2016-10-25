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

    private $COUNT_QUERY;
    private $INSERT_QUERY;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->COUNT_QUERY = $pdo->prepare("SELECT COUNT(*) FROM request WHERE ip=:ip AND time>:time");
        $this->INSERT_QUERY = $pdo->prepare("INSERT INTO request (ip, time) VALUES (:ip, :time)");
    }

    public function countAfter($time, $ip){
        $COUNT_QUERY = $this->COUNT_QUERY;
        $COUNT_QUERY->bindParam(':ip', $ip);
        $COUNT_QUERY->bindParam(':time', $time);

        $COUNT_QUERY->execute();
        $row = $COUNT_QUERY->fetch();

        if($row === false) {
            return array(0);
        }
        return $row;
    }

    public function save(Request $request)
    {
        $ip = $request->getIp();
        $time = $request->getTime();

        $INSERT_QUERY = $this->INSERT_QUERY;
        $INSERT_QUERY->bindParam(':ip', $ip);
        $INSERT_QUERY->bindParam(':time', $time);

        $INSERT_QUERY->execute();
        return $this->pdo->lastInsertId();
    }
}
