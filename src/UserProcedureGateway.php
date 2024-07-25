<?php

class UserProcedureGateway
{
    private PDO $conn;
    private int $user_id;

    public function __construct(Database $database, int $user_id)
    {
        $this->conn = $database->getConnection();
        $this->user_id = $user_id;
    }

    public function getAllUserProcs()
    {

        $sql = "SELECT p.description procedurename, up.id_user, up.id_procedure, ps.status_desc proc_status, up.start_dt, up.last_update_dt, up.finish_dt
                from user_procedures up 
                join procedures p on p.id = up.id_procedure
                join procedure_status ps on ps.id = up.id_procedure_status;
                where up.id_user = :id_user
                ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id_user", $this->user_id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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

    public function createUserProc(int $id_proc, array $dataMap): int
    {
        $sql = "INSERT into users_procedures(id_user, id_procedure, id_procedure_status, start_dt, last_update_dt) 
                values(:id_user, :id_procedure, :id_procedure_status, current_timestamp, current_timestamp)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id_user", $this->user_id, PDO::PARAM_STR);
        $stmt->bindValue(":id_procedure", $id_proc, PDO::PARAM_STR);
        $stmt->bindValue(":id_procedure_status", 1, PDO::PARAM_STR);
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