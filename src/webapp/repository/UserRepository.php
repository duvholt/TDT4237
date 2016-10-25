<?php

namespace tdt4237\webapp\repository;

use PDO;
use tdt4237\webapp\models\Phone;
use tdt4237\webapp\models\Email;
use tdt4237\webapp\models\NullUser;
use tdt4237\webapp\models\User;

class UserRepository
{
    const SELECT_ALL     = "SELECT * FROM users";

    /**
     * @var PDO
     */
    private $pdo;

    private $FIND_BY_NAME;
    private $INSERT_QUERY;
    private $UPDATE_QUERY;
    private $DELETE_BY_NAME;
    private $FIND_FULL_NAME;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->FIND_BY_NAME = $pdo->prepare("SELECT * FROM users WHERE user=:user");
        $this->INSERT_QUERY = $pdo->prepare("INSERT INTO users(user, pass, first_name, last_name, phone, company, isadmin) VALUES(:user, :pass, :first_name, :last_name, :phone, :company, :isadmin)");
        $this->UPDATE_QUERY = $pdo->prepare("UPDATE users SET email=:email, first_name=:first_name, last_name=:last_name, isadmin=:isadmin, phone =:phone , company =:company WHERE id=:id");
        $this->DELETE_BY_NAME = $pdo->prepare("DELETE FROM users WHERE user=:user");
        $this->FIND_FULL_NAME   = $pdo->prepare("SELECT * FROM users WHERE user=:user");
   }

    public function makeUserFromRow(array $row)
    {
        $user = new User($row['user'], $row['pass'], $row['first_name'], $row['last_name'], $row['phone'], $row['company']);
        $user->setUserId($row['id']);
        $user->setFirstName($row['first_name']);
        $user->setLastName($row['last_name']);
        $user->setPhone($row['phone']);
        $user->setCompany($row['company']);
        $user->setIsAdmin($row['isadmin']);

        if (!empty($row['email'])) {
            $user->setEmail(new Email($row['email']));
        }

        if (!empty($row['phone'])) {
            $user->setPhone(new Phone($row['phone']));
        }

        return $user;
    }

    public function getNameByUsername($username)
    {
        $FIND_FULL_NAME = $this->FIND_FULL_NAME;
        $FIND_FULL_NAME->bindParam(':user', $username);

        $FIND_FULL_NAME->execute();
        $row = $FIND_FULL_NAME->fetch(PDO::FETCH_ASSOC);
        $name = $row['first_name'] + " " + $row['last_name'];
        return $name;
    }

    public function findByUser($username)
    {
        $FIND_BY_NAME = $this->FIND_BY_NAME;
        $FIND_BY_NAME->bindParam(':user', $username);
        $FIND_BY_NAME->execute();
        $row = $FIND_BY_NAME->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return false;
        }

        return $this->makeUserFromRow($row);
    }

    public function deleteByUsername($username)
    {
        $DELETE_BY_NAME = $this->DELETE_BY_NAME;
        $DELETE_BY_NAME->bindParam(':user', $username);
        return $DELETE_BY_NAME->execute();
    }

    public function all()
    {
        $rows = $this->pdo->query(self::SELECT_ALL);
        
        if ($rows === false) {
            return [];
            throw new \Exception('PDO error in all()');
        }

        return array_map([$this, 'makeUserFromRow'], $rows->fetchAll());
    }

    public function save(User $user)
    {
        if ($user->getUserId() === null) {
            return $this->saveNewUser($user);
        }

        $this->saveExistingUser($user);
    }

    public function saveNewUser(User $user)
    {
        $username = $user->getUsername();
        $pass = $user->getHash();
        $first_name = $user->getFirstName();
        $last_name = $user->getLastName();
        $phone = $user->getPhone();
        $company = $user->getCompany();
        $isadmin = $user->isAdmin();

        $INSERT_QUERY = $this->INSERT_QUERY;
        $INSERT_QUERY->bindParam(':user', $username);
        $INSERT_QUERY->bindParam(':pass', $pass);
        $INSERT_QUERY->bindParam(':first_name', $first_name);
        $INSERT_QUERY->bindParam(':last_name', $last_name);
        $INSERT_QUERY->bindParam(':phone', $phone);
        $INSERT_QUERY->bindParam(':company', $phone);
        $INSERT_QUERY->bindParam(':isadmin', $isadmin);

        return $INSERT_QUERY->execute();
    }

    public function saveExistingUser(User $user)
    {
        $email = $user->getEmail();
        $first_name = $user->getFirstName();
        $last_name = $user->getLastName();
        $isadmin = $user->isAdmin();
        $phone = $user->getPhone();
        $company = $user->getCompany();
        $userId = $user->getUserId();

        $UPDATE_QUERY = $this->UPDATE_QUERY;
        $UPDATE_QUERY->bindParam(':email', $email);
        $UPDATE_QUERY->bindParam(':first_name', $first_name);
        $UPDATE_QUERY->bindParam(':last_name', $last_name);
        $UPDATE_QUERY->bindParam(':isadmin', $isadmin);
        $UPDATE_QUERY->bindParam(':phone', $phone);
        $UPDATE_QUERY->bindParam(':company', $company);
        $UPDATE_QUERY->bindParam(':id', $userId);

        return $UPDATE_QUERY->execute();
    }

}
