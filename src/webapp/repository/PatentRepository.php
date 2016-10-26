<?php

namespace tdt4237\webapp\repository;

use PDO;
use tdt4237\webapp\models\Patent;
use tdt4237\webapp\models\PatentCollection;

class PatentRepository
{

    /**
     * @var PDO
     */
    private $pdo;

    private $FIND_BY_PATENT_ID;
    private $DELETE_BY_PATENT_ID;
    private $INSERT_QUERY;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->FIND_BY_PATENT_ID = $pdo->prepare("SELECT * FROM patent WHERE patentId = :patentId");
        $this->DELETE_BY_PATENT_ID = $pdo->prepare("DELETE FROM patent WHERE patentid=:patentid;");
        $this->INSERT_QUERY = $pdo->prepare("INSERT INTO patent (company, date, title, description, file, filename) VALUES (:company, :date, :title, :description, :file, :filename)");
    }

    public function makePatentFromRow(array $row)
    {
        $patent = new Patent($row['patentId'], $row['company'], $row['title'], $row['description'], $row['date'], $row['file']);
        $patent->setPatentId($row['patentId']);
        $patent->setCompany($row['company']);
        $patent->setTitle($row['title']);
        $patent->setDescription($row['description']);
        $patent->setDate($row['date']);
        $patent->setFile($row['file']);
        $patent->setFilename($row['filename']);

        return $patent;
    }


    public function find($patentId)
    {
        $FIND_BY_PATENT_ID = $this->FIND_BY_PATENT_ID;
        $FIND_BY_PATENT_ID->bindParam(':patentId', $patentId);

        $FIND_BY_PATENT_ID->execute();
        $row = $FIND_BY_PATENT_ID->fetch();

        if($row === false) {
            return false;
        }

        return $this->makePatentFromRow($row);
    }

    public function all()
    {
        $sql   = "SELECT * FROM patent";
        $results = $this->pdo->query($sql);

        if($results === false) {
            return [];
            throw new \Exception('PDO error in patent all()');
        }

        $fetch = $results->fetchAll();
        if(count($fetch) == 0) {
            return false;
        }

        return new PatentCollection(
            array_map([$this, 'makePatentFromRow'], $fetch)
        );
    }

    public function deleteByPatentid($patentId)
    {
        $DELETE_BY_PATENT_ID = $this->DELETE_BY_PATENT_ID;
        $DELETE_BY_PATENT_ID->bindParam(':patentid', $patentId);

        return $DELETE_BY_PATENT_ID->execute();
    }


    public function save(Patent $patent)
    {
        if ($patent->getPatentId() === null) {
            $title          = $patent->getTitle();
            $company        = $patent->getCompany();
            $description    = $patent->getDescription();
            $date           = $patent->getDate();
            $file           = $patent->getFile();
			$filename       = $patent->getFilename();

            $INSERT_QUERY = $this->INSERT_QUERY;

            $INSERT_QUERY->bindParam(':company', $company);
            $INSERT_QUERY->bindParam(':date', $date);
            $INSERT_QUERY->bindParam(':title', $title);
            $INSERT_QUERY->bindParam(':description', $description);
            $INSERT_QUERY->bindParam(':file', $file);
            $INSERT_QUERY->bindParam(':filename', $filename);

            $INSERT_QUERY->execute();
        }
        return $this->pdo->lastInsertId();
    }
}
