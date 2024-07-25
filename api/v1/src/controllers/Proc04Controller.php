<?php

// Proc04Controller.php
// Funciones para manejo de tramites de transferencia de saldo
// LineaExpressApp

namespace Controllers;

class Proc04Controller extends BaseController
{
    public const REQ_PARAMS = [
        'num_tag1',
        'veh1_marca', 'veh1_modelo', 'veh1_color', 'veh1_anio', 'veh1_placas', 'veh1_origen',
        'num_tag2',
        'veh2_marca', 'veh2_modelo', 'veh2_color', 'veh2_anio', 'veh2_placas', 'veh2_origen'
        , 'sentri', 'sentri_vencimiento'
    ];

    public const OPT_PARAMS = ['comments'];

    private int $procedure_id = 4;

    public function view(): void
    {
        $this->hasAuthOrDie();

        $id = $this->f3->get('PARAMS.id');
        // since para is part of route, we are sure param will be there

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $proc = new \Data\VUserProc04Model();
        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        echo json_encode($proc->cast());
    }

    public function create(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $params = $this->paramArrayToUppercase($params);

        $this->checkRequiredParametersOrDie(Proc04Controller::REQ_PARAMS, $params);

        $validation_errors = $this->validate($params, true);
        if (count($validation_errors) > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Uno o mas datos no cumplen con condiciones de validacion.', 'errors' => $validation_errors ]);
            die;
        }

        $this->checkUserSetPasswordOrDie();

        $usr = new \Data\UserModel();
        $usr->getUserById($this->auth->getUserId());

        $p04 = new \Data\Proc04Model();
        $p04->id_user = $this->auth->getUserId();
        $p04->id_procedure = $this->procedure_id;
        $p04->id_procedure_status = 1;
        $p04->start_dt = date('Y-m-d h:i:s', time());
        $p04->last_update_dt = date('Y-m-d h:i:s', time());
        foreach (Proc04Controller::REQ_PARAMS as $paramName) {
            $p04[ $paramName ] = $params[ $paramName ];
        }
        // opcionales
        foreach (self::OPT_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $p04[ $paramName ] = $params[ $paramName ];
            }
        }

        // if (!empty($usr->fac_razon_social)) {
        //     $p04->fac_razon_social = $usr->fac_razon_social;
        //     $p04->fac_rfc          = $usr->fac_rfc;
        //     $p04->fac_dom_fiscal   = $usr->fac_dom_fiscal . " CP: " . $usr->fac_cp;
        //     $p04->fac_email        = $usr->fac_email;
        //     $p04->fac_telefono     = $usr->fac_telefono;
        // }

        $p04->save();

        \Util\Mail::sendNewProcEmail($usr->userlogin, $usr->fullname, "Solicitud de transferencia de saldo");

        $device_id = $usr->device_id;
        if (!empty($device_id)) {
            \Util\Notify::sendFirebaseMessage("Trámite iniciado: Solicitud de transferencia de saldo", $device_id);
        }

        echo json_encode([ 'message' => 'Tramite registrado exitosamente.', 'ID' => $p04->id ]);
    }

    public function update(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $id = $this->f3->get('PARAMS.id');
        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $params = $this->getParamsFromRequestBodyOrDie();
        $params = $this->paramArrayToUppercase($params);

        $this->checkUserSetPasswordOrDie();

        $proc = new \Data\Proc04Model();
        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $user = $this->getUserData($this->auth->getUserId());

        if ($proc[ 'id_user' ] != $this->auth->getUserId() && !\in_array($user['usertype'], [2,3,4])) {
            http_response_code(401);
            echo json_encode([ 'message' => 'Solo el dueño del tramite, el tramitador o administrador pueden hacer cambios.' ]);
            die;
        }

        foreach (Proc04Controller::REQ_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $proc[ $paramName ] = $params[ $paramName ];
            }
        }
        $proc->save();

        echo json_encode($proc->cast());
    }

    public function delete(): void
    {
        $this->hasAuthOrDie();

        $id = $this->f3->get('PARAMS.id');
        if (empty($id)) {
        }

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $this->checkUserSetPasswordOrDie();

        $proc = new \Data\Proc04Model();
        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $proc->id_procedure_status = 100;
        $proc->save();

        echo json_encode([ 'message' => "Tramite {$id} cancelado" ]);
    }

    public function validate(array $data, bool $is_new = false): array
    {
        $res = [];
        return $res;
    }
}
