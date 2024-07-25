<?php

// ProcUtils.php
// Funciones Utilitarias para manejo de tramites
// LineaExpressApp

namespace Controllers;

class ProcUtils
{
    private BaseController $controller;

    public function __construct(BaseController $controller)
    {
        $this->controller = $controller;
    }

    public function getProcModel(int $id_proc_type): \DB\SQL\Mapper
    {
        switch ($id_proc_type) {
            case 1:
                $proc = new \Data\Proc01Model();
                break;
            case 2:
                $proc = new \Data\Proc02Model();
                break;
            case 3:
                $proc = new \Data\Proc03Model();
                break;
            case 4:
                $proc = new \Data\Proc04Model();
                break;
            case 5:
                $proc = new \Data\Proc05Model();
                break;
            default:
                $this->$controller->respondNotImplemented("Tipo de proceso invalido ({$id_proc_type})");
        }
        return $proc;
    }

    public function assignOperatorIfMissing(int $id_proc, int $id_proc_type, int $id_operator)
    {
        $proc = $this->getProcModel($id_proc_type);

        $found = $proc->loadByID($id_proc);
        if ($found === false) {
            $this->controller->respondNotFound($id_proc);
        }

        if ($proc[ 'id_user_operator' ] && $proc[ 'id_user_operator' ] > 0) {
            $op = new \Data\UserModel();
            $found = $op->getUserById($id_operator);
            if ($found != false) {
                // http_response_code(400);
                // echo json_encode([ 'message' => "El proceso ya tiene asignado un operador: {$op['fullname']}" ]);
                // die;
            }
        }

        $proc[ 'id_user_operator' ] = $id_operator;
        if ($proc[ 'id_procedure_status' ] === 1) {
            $proc[ 'id_procedure_status' ] = 2;
        }
        $proc->save();
        return true;
    }

    public function assignOperatorCitaIfMissing(int $id_proc, int $id_proc_type, int $id_operator)
    {
        $proc = $this->getProcModel($id_proc_type);
        $found = $proc->loadByID($id_proc);
        if ($found === false) {
            $this->controller->respondNotFound($id_proc);
        }

        if ($proc[ 'id_user_cita' ] && $proc[ 'id_user_cita' ] > 0) {
            $op = new \Data\UserModel();
            $found = $op->getUserById($id_operator);
            if ($found != false) {
                // http_response_code(400);
                // echo json_encode([ 'message' => "El proceso ya tiene asignado un operador: {$op['fullname']}" ]);
                // die;
            }
        }

        $proc[ 'id_user_cita' ] = $id_operator;
        $proc->save();
        return true;
    }

    public function setProcStatus(int $id_proc, int $id_proc_type, int $id_proc_status)
    {
        $proc = $this->getProcModel($id_proc_type);

        $found = $proc->loadByID($id_proc);
        if ($found === false) {
            $this->respondNotFound($id_proc);
        }

        $proc[ 'id_procedure_status' ] = $id_proc_status;
        $proc->save();
        return true;
    }
}
