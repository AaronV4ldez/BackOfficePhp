<?php

class ProcedureController extends BaseController
{
    private ProcedureGateway $gateway;
    private int $user_id; // user who's performing the request

    public function __construct(
        Database $db,
        int $user_id
    ) {
        parent::__construct($db);
        $this->gateway = new ProcedureGateway($this->getDatabase());
        $this->user_id = $user_id;
    }

    public function doGetSingle()
    {
        $this->checkFormParamOrDie("id");
        $user = $this->gateway->getProcedureById($this->getParams()["id"]);
        if (!$user) {
            $this->respondNotFound($this->getParams()["id"]);
            die;
        }
        echo json_encode($user);
    }

    public function doGetAll()
    {
        echo json_encode($this->gateway->getAllProcedures());
    }

}
