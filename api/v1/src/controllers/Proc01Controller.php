<?php

// Proc01Controller.php
// funciones para manejo de tramite de inscripcion
// LineaExpressApp

namespace Controllers;

class Proc01Controller extends BaseController
{
    public const REQ_PARAMS = [
        'dom_calle', 'dom_numero_ext', 'dom_colonia', 'dom_ciudad', 'dom_estado', 'dom_cp',
        'veh_marca', 'veh_modelo', 'veh_color', 'veh_anio', 'veh_placas', 'veh_origen',
        'conv_saldo', 'conv_anualidad', 'sentri', 'sentri_vencimiento'
    ];

    public const OPT_PARAMS = [
        'comments', 'fac_razon_social', 'fac_rfc', 'fac_dom_fiscal', 
        'fac_email', 'fac_telefono', 'puente_sel'
    ];

    private int $procedure_id = 1;

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

        $proc = new \Data\VUserProc01Model();
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

        $this->checkRequiredParametersOrDie(Proc01Controller::REQ_PARAMS, $params);

        $validation_errors = $this->validate($params, true);
        if (count($validation_errors) > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Uno o mas datos no cumplen con condiciones de validacion.', 'errors' => $validation_errors ]);
            die;
        }

        $this->checkUserSetPasswordOrDie();

        $usr = new \Data\UserModel();
        $usr->getUserById($this->auth->getUserId());
        //se agregó el valor de $token
        //$token = 'null';

        $p01 = new \Data\Proc01Model();
        $p01->id_user = $this->auth->getUserId();
        $p01->id_procedure = $this->procedure_id;
        $p01->id_procedure_status = 1;
        $p01->start_dt = date('Y-m-d h:i:s', time());
        $p01->last_update_dt = date('Y-m-d h:i:s', time());
        foreach (Proc01Controller::REQ_PARAMS as $paramName) {
            $p01[ $paramName ] = $params[ $paramName ];
        }
        // opcionales
        foreach (self::OPT_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $p01[ $paramName ] = $params[ $paramName ];
            }
        }

        if (!empty($usr->fac_razon_social)) {
            $p01->fac_razon_social = $usr->fac_razon_social;
            $p01->fac_rfc          = $usr->fac_rfc;
            $p01->fac_dom_fiscal   = $usr->fac_dom_fiscal . " CP: " . $usr->fac_cp;
            $p01->fac_email        = $usr->fac_email;
            $p01->fac_telefono     = $usr->fac_telefono;
        }

        $p01->save();

        \Util\Mail::sendNewProcEmail($usr->userlogin, $usr->fullname, "Solicitud de inscripción");

        $device_id = $usr->device_id;
        if (!empty($device_id)) {
            \Util\Notify::sendFirebaseMessage("Trámite iniciado: Solicitud de inscripción", $device_id);
        }

        echo json_encode([ 'message' => 'Tramite registrado exitosamente.', 'ID' => $p01->id ]);
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

        $this->checkUserSetPasswordOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();
        $params = $this->paramArrayToUppercase($params);

        $proc = new \Data\Proc01Model();
        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $user = $this->getUserData($this->auth->getUserId());

        if ($proc[ 'id_user' ] != $this->auth->getUserId() && !\in_array($user['usertype'], [2,3,4])) {
            http_response_code(401);
            echo json_encode([ 'message' => 'Solo el dueño del tramite o el tramitador asignado pueden hacer cambios.' ]);
            die;
        }

        foreach (self::REQ_PARAMS as $paramName) {
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

        $proc = new \Data\Proc01Model();
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

        if (isset($data[ 'fac_email' ]) && trim($data[ 'fac_email' ]) != '' && !filter_var($data[ 'fac_email' ], FILTER_VALIDATE_EMAIL)) {
            $res[] = 'Formato de correo electronico para facturacion es invalido.';
        }

        return $res;
    }
}
