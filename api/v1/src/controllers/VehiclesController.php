<?php

// VehiclesController.php
// Funciones para manejo de vehiculos
// LineaExpressApp

namespace Controllers;

class VehiclesController extends BaseController
{
    public const REQUIRED_CREATE_PARAMS = [ "tag", "tt" ]; //"marca", "linea", "modelo", "placa", "color", "puente"
    public const REQUIRED_SENTRI_PARAMS = ["sentri", "email"];

    public function list()
    {
        $this->hasAuthOrDie();
        $uid = $this->auth->getUserID();

        $user = new \Data\UserModel();
        $res = $user->getUserById($uid);
        if ($res === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el usuario"));
            die;
        }
        // if (empty($res["sentri_number"])) {
        //     \http_response_code(400);
        //     echo json_encode(array("message" => "No ha registrado su numero de SENTRI, por favor actualicelo desde la App."));
        //     die;
        // }

        $this->loadSentriVehicles($res["sentri_number"], $res["userlogin"], $uid);

        $cars = new \Data\VehiclesModel();
        $res = $cars->findByUserID($uid);

        $result = array();
        if ($res !== false) {
            foreach ($res as $row) {
                $r = $row->cast();
                // $r["imgurl"] = "https://cdn.imagin.studio/getImage?customer=mxlineaexpres&make=".$r["marca"]."&modelFamily=".$r["linea"]."&modelYear=".$r["modelo"]."&width=400";
                if ($r["tipo"] == 0) {
                    $r["imgurl"] = \Util\Img::getTPImg();
                } else {
                    $r["imgurl"] = \Util\Img::getCarImg($r["marca"], $r["linea"], $r["modelo"]);
                }

                $result[] = $r;
            }
        }

        echo json_encode($result);
    }

    public function details()
    {
        $this->hasAuthOrDie();

        $uid = $this->auth->getUserID();
        $vid = $this->f3->get('PARAMS.id');

        // check if $vid is a number
        if (!is_numeric($vid)) {
            \http_response_code(400);
            echo json_encode(array("message" => "El ID del vehículo debe ser numérico"));
            die;
        }

        $user = $this->getUserData($uid);
        if ($user === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el usuario"));
            die;
        }
        if ($user->usertype == 1) {
            $cars = new \Data\VehiclesModel();
            $res = $cars->findByVehicleIdUserID($vid, $uid);
        } else {
            $cars = new \Data\VehiclesModel();
            $res = $cars->findById($vid);
        }

        if ($res === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el vehículo"));
            die;
        }

        echo json_encode($res->cast());
    }

    public function create()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $data = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQUIRED_CREATE_PARAMS, $data);
        $this->checkUserSetPasswordOrDie();

        $uid = $this->auth->getUserID();

        $cnt = $this->f3->get('DB')->exec("SELECT COUNT(id) AS cnt FROM vehicles WHERE tag = ?", $data["tag"]);
        if ($cnt[0]["cnt"] > 0) {
            \http_response_code(400);
            echo json_encode(array("message" => "El tag ya está asignado a otro vehículo."));
            die;
        }
        // echo var_export($data, true);
        if (!\in_array($data["tt"], ["0", "2"])) {
            \http_response_code(400);
            echo json_encode(array("message" => "Parametro TT debe ser 0 ó 2."));
            die;
        }

        if ($data["tt"] == "2") {
            if (!isset($data["marca"]) || trim($data["marca"]) == '' ||
                !isset($data["linea"]) || trim($data["linea"]) == '') {
                \http_response_code(400);
                echo json_encode(array("message" => "Parametros marca y linea son requeridos para tags tipo 2."));
                die;
            }
        }

        $cars = new \Data\VehiclesModel();

        // $cars->copyFrom($data);
        $cars->marca = isset($data["marca"]) && trim($data["marca"]) != '' ? $data["marca"] : "N/D";
        $cars->linea = isset($data["linea"]) && trim($data["linea"]) != '' ? $data["linea"] : "N/D";
        $cars->placa = isset($data["placa"]) && trim($data["placa"]) != '' ? $data["placa"] : "N/D";
        $cars->modelo = isset($data["modelo"]) && trim($data["modelo"]) != '' ? $data["modelo"] : "N/D";
        $cars->color = isset($data["color"]) && trim($data["color"]) != '' ? $data["color"] : "N/D";
        $cars->is_active = 1;
        $cars->tag = $data["tag"];
        $cars->tipo = $data["tt"]; // 0=TP, 2=peaton
        $cars->puente = isset($data["puente"]) && trim($data["puente"]) != '' ? $data["puente"] : "N/D";
        $cars->id_user = $uid;
        $cars->save();

        echo json_encode($cars->cast());
    }

    public function update()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $data = $this->getParamsFromRequestBodyOrDie();
        // $this->checkRequiredParametersOrDie(self::REQUIRED_CREATE_PARAMS, $data);
        $this->checkUserSetPasswordOrDie();

        $uid = $this->auth->getUserID();
        $vid = $this->f3->get('PARAMS.id');

        // check if $vid is a number
        if (!is_numeric($vid)) {
            \http_response_code(400);
            echo json_encode(array("message" => "El ID del vehículo debe ser numérico"));
            die;
        }

        $cars = new \Data\VehiclesModel();
        $res = $cars->findByVehicleIdUserID($vid, $uid);

        if ($res === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el vehículo"));
            die;
        }

        if ($cars["tipo"] != 0) {
            \http_response_code(400);
            echo json_encode(array("message" => "No se puede editar un vehículo de Linea Expres"));
            die;
        }

        foreach (self::REQUIRED_CREATE_PARAMS as $paramName) {
            if (array_key_exists($paramName, $data)) {
                $cars[ $paramName ] = $data[ $paramName ];
            }
        }
        $cars->save();

        echo json_encode($cars->cast());
    }

    public function delete()
    {
        $this->hasAuthOrDie();
        $this->checkUserSetPasswordOrDie();

        $uid = $this->auth->getUserID();
        $vid = $this->f3->get('PARAMS.id');

        // check if $vid is a number
        if (!is_numeric($vid)) {
            \http_response_code(400);
            echo json_encode(array("message" => "El ID del vehículo debe ser numérico"));
            die;
        }

        $cars = new \Data\VehiclesModel();
        $res = $cars->findByVehicleIdUserID($vid, $uid);

        if ($res === false) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el vehículo"));
            die;
        }
        
        //cambio del 23 02 24 se corrigió
        /*if ($res["tipo"] != 0) {
            \http_response_code(400);
            echo json_encode(array("message" => "No se puede eliminar un vehículo de Linea Expres"));
            die;
        }*/

        $cars->erase();

        echo json_encode(array("message" => "Vehículo eliminado"));
    }

    private function loadSentriVehicles($sentri, $email, $uid)
    {
        $userInfo = \Util\LEApi::getUserInfo($sentri, $email);
        $userInfo = \json_decode($userInfo, true);
        // \var_dump($userInfo);

        if (\is_array($userInfo) && \count($userInfo) > 0) {
            $this->f3->get('DB')->exec("DELETE FROM vehicles WHERE id_user = ? AND tipo = 1", $uid);

            foreach ($userInfo as $ctl_user) {
                // \var_dump($ctl_user);
                $vs = \Util\LEApi::getUserCars($ctl_user["id"]);
                $vs = \json_decode($vs, true);

                if (\is_array($vs) && \count($vs) > 0) {
                    foreach ($vs as $v) {
                        $car = new \Data\VehiclesModel();
                        $car->marca = $v["brand"];
                        $car->linea = $v["model"];
                        $car->placa = $v["license_plate"];
                        $car->modelo = $v["year"];
                        $car->color = $v["color"];
                        if (\trim(\strtoupper($v["contract_type"])) == "C") {
                            $car->saldo = $v["crossings_available"];
                        } else {
                            $car->saldo = 0;
                        }
                        $car->clt_expiration_date = $v["expiration_date"];
                        $car->is_active = 1;
                        $car->tag = $v["contract_tag"];
                        $car->tipo = 1;
                        $car->puente = "P"; //$v["puente"];
                        $car->id_user = $uid;

                        // fields needed for controles API
                        $car->ctl_id = $v["id"];
                        $car->ctl_contract_type = $v["contract_type"];
                        $car->ctl_stall_id = $v["stall_id"];
                        $car->ctl_user_id = $ctl_user["id"];
                        $car->save();
                        // $car->reset();

                        // echo "Vehículo {$car["id"]} agregado...\n";
                    }
                }
            }
        } else {
            // No se encontró el usuario en base de datos de Controles de Acceso
            // \http_response_code(400);
            // echo json_encode(array("message" => "Este usuario / Sentri ya esta registrado, intenta con otra Sentri."));
            // die;
        }
    }

    public function getSentriVehicles()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $data = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQUIRED_SENTRI_PARAMS, $data);
        $this->checkUserSetPasswordOrDie();

        $uid = $this->auth->getUserID();
        $user = new \Data\UserModel();
        $user->load(array("id = ?", $uid));

        if ($user->dry()) {
            \http_response_code(404);
            echo json_encode(array("message" => "No se encontró el usuario"));
            die;
        }

        if ($user->userlogin != $data["email"]) {
            \http_response_code(400);
            echo json_encode(array("message" => "El correo electronico no coincide con el usuario logueado. "));
            die;
        }

        $this->loadSentriVehicles($data["sentri"], $data["email"], $uid);
    }
}
