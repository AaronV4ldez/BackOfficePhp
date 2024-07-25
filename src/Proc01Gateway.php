<?php

class Proc01Gateway
{
    private PDO $conn;
    private int $user_id;

    public function __construct(Database $database, int $user_id)
    {
        $this->conn = $database->getConnection();
        $this->user_id = $user_id;
    }

    public function getProcById(int $userproc_id)
    {
        $sql = "SELECT * from vw_user_procedures
                where id_user = :id_user and up.id_user_proc = :id_user_prod
                ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id_user", $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(":id_user_prod", $userproc_id, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res;
    }

    public function createUserProc(
        int $id_user,
        string $dom_calle,
        string $dom_numero_ext,
        string $dom_colonia,
        string $dom_ciudad,
        string $dom_estado,
        string $dom_cp,
        string $fac_razon_social,
        string $fac_rfc,
        string $fac_dom_fiscal,
        string $fac_email,
        string $fac_telefono,
        string $veh_marca,
        string $veh_modelo,
        string $veh_color,
        int $veh_anio,
        string $veh_placas,
        string $veh_origen,
        string $conv_saldo,
        string $conv_anualidad
    ): int {
        $sql = "INSERT into proc01(id_user, id_procedure, id_procedure_status, 
                    start_dt, last_update_dt, dom_calle, dom_numero_ext,
                    dom_colonia, dom_ciudad, dom_estado, dom_cp, fac_razon_social,
                    fac_rfc, fac_dom_fiscal, fac_email, fac_telefono, veh_marca,
                    veh_modelo, veh_color, veh_anio, veh_placas, veh_origen, conv_saldo,
                    conv_anualidad)
                values(:id_user, :id_procedure, :id_procedure_status, 
                    current_timestamp, current_timestamp, :dom_calle, :dom_numero_ext,
                    :dom_colonia, :dom_ciudad, :dom_estado, :dom_cp, :fac_razon_social,
                    :fac_rfc, :fac_dom_fiscal, :fac_email, :fac_telefono, :veh_marca,
                    :veh_modelo, :veh_color, :veh_anio, :veh_placas, :veh_origen, :conv_saldo,
                    :conv_anualidad)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id_user", $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(":id_procedure", 1, PDO::PARAM_INT);
        $stmt->bindValue(":id_procedure_status", 1, PDO::PARAM_INT);
        $stmt->bindValue(":dom_calle", $dom_calle, PDO::PARAM_STR);
        $stmt->bindValue(":dom_numero_ext", $dom_numero_ext, PDO::PARAM_STR);
        $stmt->bindValue(":dom_colonia", $dom_colonia, PDO::PARAM_STR);
        $stmt->bindValue(":dom_ciudad", $dom_ciudad, PDO::PARAM_STR);
        $stmt->bindValue(":dom_estado", $dom_estado, PDO::PARAM_STR);
        $stmt->bindValue(":dom_cp", $dom_cp, PDO::PARAM_STR);
        $stmt->bindValue(":fac_razon_social", $fac_razon_social, PDO::PARAM_STR);
        $stmt->bindValue(":fac_rfc", $fac_rfc, PDO::PARAM_STR);
        $stmt->bindValue(":fac_dom_fiscal", $fac_dom_fiscal, PDO::PARAM_STR);
        $stmt->bindValue(":fac_email", $fac_email, PDO::PARAM_STR);
        $stmt->bindValue(":fac_telefono", $fac_telefono, PDO::PARAM_STR);
        $stmt->bindValue(":veh_marca", $veh_marca, PDO::PARAM_STR);
        $stmt->bindValue(":veh_modelo", $veh_modelo, PDO::PARAM_STR);
        $stmt->bindValue(":veh_color", $veh_color, PDO::PARAM_STR);
        $stmt->bindValue(":veh_anio", $veh_anio, PDO::PARAM_INT);
        $stmt->bindValue(":veh_placas", $veh_placas, PDO::PARAM_STR);
        $stmt->bindValue(":veh_origen", $veh_origen, PDO::PARAM_STR);
        $stmt->bindValue(":conv_saldo", $conv_saldo, PDO::PARAM_STR);
        $stmt->bindValue(":conv_anualidad", $conv_anualidad, PDO::PARAM_STR);
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
