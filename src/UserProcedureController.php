<?php

class UserProcedureController extends BaseController
{
    private ProcedureGateway $gateway;
    private int $user_id; // user who's performing the request

    public function __construct(
        Database $db,
        int $user_id
    ) {
        parent::__construct($db);
        $this->gateway = new UserProcedureGateway($this->getDatabase(), $user_id);
        $this->user_id = $user_id;
    }

    public function doGetSingle()
    {
        $this->checkFormParamOrDie("id");
        $user = $this->gateway->getUserById($this->getParams()["id"]);
        if (!$user) {
            $this->respondNotFound($this->getParams()["id"]);
            die;
        }
        echo json_encode($user);
    }

    public function doDelete()
    {
        $this->responseMethodNotAllowed("");
        echo json_encode(["message" => "No se permite eliminar usuarios."]);
    }

    public function doUpdate()
    {
        $this->checkFormParamOrDie("id");
        $errors = $this->getValidationErrors($this->getParams(), false);
        if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
        }
        $rows = $this->gateway->updateUser($this->getParams()["id"], $this->getParams());
        echo json_encode(["message" => "Usuario actualizado", "renglones" => "$rows"]);
    }

    public function doGetAll()
    {
        echo json_encode($this->gateway->getAllUsers());
    }

    protected function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["name"])) {
            $errors[] = "Nombre requerido.";
        }
        if ($is_new && empty($data["email"])) {
            $errors[] = "Nombre requerido.";
        }
        if ($is_new && empty($data["assword"])) {
            $errors[] = "Password requerido.";
        }
        if ($is_new && empty($data["phone"])) {
            $errors[] = "Telefono requerido.";
        }
        if ($is_new && empty($data["usertype"])) {
            $errors[] = "Tipo de usuario requerido.";
        }

        if (!empty($data["password"]) && strlen($data["password"]) < 8) {
            $errors[] = "Password debe ser de 8 o más caracteres.";
        }

        if (!empty($data["email"])) {
            if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Direccion de correo electrónico no está en formato correcto.";
            }
        }

        // if (!empty($data["priority"])) {
        //     if (filter_var($data["priority"], FILTER_VALIDATE_INT) === false) {
        //         $errors[] = "Priority must be an integer";
        //     }
        // }
        return $errors;
    }
}