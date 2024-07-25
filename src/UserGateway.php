<?php

class UserGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAllUsers()
    {
        $sql = "SELECT id, fullname, userlogin, phone, sentri_number, sentri_exp_date from users";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $res;
    }

    public function getUserByLogin(string $userlogin)
    {
        $sql = "SELECT * from users where userlogin = :username";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":username", $userlogin, PDO::PARAM_STR);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // remove password hash for security reasons
        unset($res["password_hash"]);

        return $res;
    }

    public function getUserById(int $user_id)
    {
        $sql = "SELECT * from users where id = :user_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // remove password hash for security reasons
        unset($res["password_hash"]);

        return $res;
    }

    public function createUser(string $fullname, string $userlogin, string $password, string $phone, int $user_type = 1, int $account_verified = 0): int
    {
        $sql = "INSERT into users(fullname, userlogin, password_hash, phone, account_verified, usertype, account_verified) 
                values(:fullname, :userlogin, :password_hash, :phone, 0, :user_type, :account_verified)";

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindValue(":userlogin", $userlogin, PDO::PARAM_STR);
        $stmt->bindValue(":password_hash", $password_hash, PDO::PARAM_STR);
        $stmt->bindValue(":phone", $phone, PDO::PARAM_STR);
        $stmt->bindValue(":user_type", $user_type, PDO::PARAM_INT);
        $stmt->bindValue(":account_verified", $account_verified, PDO::PARAM_INT);
        $stmt->execute();
        return  $this->conn->lastInsertId();
    }

    public function createUserActivationRecord(int $user_id): int
    {
        $sql = "INSERT user_activations (id_user, activation_code) 
                values(:id_user, :activation_code)";

        $act_code = rand(10000, 99999);

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id_user", $user_id, PDO::PARAM_STR);
        $stmt->bindValue(":activation_code", $act_code, PDO::PARAM_INT);
        $stmt->execute();

        return $act_code;
    }

    public function activateUser(string $email, string $act_code): bool
    {
        $sql = "SELECT u.id user_id, u.userlogin, u.account_verified, ua.id ua_id, ua.activation_code
                from users u join user_activations ua on ua.id_user = u.id
                where u.userlogin = :userlogin
                order by ua.id desc
                limit 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userlogin", $email, PDO::PARAM_STR);
        $stmt->execute();

        $user_rec = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($user_rec)) {
            return false;
        }

        if ($user_rec["account_verified"] == 1) {
            return true;
        }

        if ($user_rec["activation_code"] !== $act_code) {
            $sql = "UPDATE user_activations 
                    set last_try_dt = CURRENT_TIMESTAMP, number_of_tries = number_of_tries + 1 
                    where id = :uaid";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(":uaid", $user_rec["ua_id"], PDO::PARAM_INT);
            $stmt->execute();
            return false;
        }

        $sql = "UPDATE users set account_verified = 1 where id = :userid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":userid", $user_rec["user_id"], PDO::PARAM_INT);
        $stmt->execute();

        $sql = "UPDATE user_activations 
                set activation_dt = CURRENT_TIMESTAMP, last_try_dt = CURRENT_TIMESTAMP, number_of_tries = number_of_tries + 1 
                where id = :uaid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":uaid", $user_rec["ua_id"], PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }

    public function updateUser(int $id, array $data): int
    {
        unset($data["id"]); // avoid updating ID
        $fields = [];
        if (!empty($data["fullname"])) {
            $fields["fullname"] = [
                $data["fullname"],
                PDO::PARAM_STR
            ];
        }
        if (array_key_exists("phone", $data)) {
            $fields["phone"] = [
                $data["phone"],
                PDO::PARAM_STR
            ];
        }
        if (array_key_exists("sentri_number", $data)) {
            $fields["sentri_number"] = [
                $data["sentri_number"],
                PDO::PARAM_STR
            ];
        }
        if (array_key_exists("sentri_exp_date", $data)) {
            $fields["sentri_exp_date"] = [
                $data["sentri_exp_date"],
                PDO::PARAM_STR
            ];
        }

        if (empty($fields)) {
            return 0;
        } else {
            $sets = array_map(function ($value) {
                return "$value = :$value";
            }, array_keys($data));

            $sql = "UPDATE users"
                . " set " . implode(", ", $sets)
                . " where id = :id";

            $stmt =  $this->conn->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            foreach ($fields as $name => $values) {
                $stmt->bindValue(":$name", $values[0], $values[1]);
            }
            $stmt->execute();
            return $stmt->rowCount();
        }
    }

}
