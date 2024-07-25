<?php

// Proc03Controller.php
// Funciones para manejo de tramites de cambio de vehiculo
// LineaExpressApp

namespace Controllers;

class Proc03Controller extends BaseController
{
    public const REQ_PARAMS = [
        'num_tag',
        'veh1_marca', 'veh1_modelo', 'veh1_color', 'veh1_anio', 'veh1_placas', 'veh1_origen',
        'veh2_marca', 'veh2_modelo', 'veh2_color', 'veh2_anio', 'veh2_placas', 'veh2_origen'
        , 'sentri', 'sentri_vencimiento'
    ];

    public const OPT_PARAMS = ['comments', 'seguro_origen', 'fac_razon_social', 'fac_rfc', 'fac_dom_fiscal', 'fac_email', 'fac_telefono'];

    private int $procedure_id = 3;

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

        $proc = new \Data\VUserProc03Model();
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

        $this->checkRequiredParametersOrDie(Proc03Controller::REQ_PARAMS, $params);

        $this->checkUserSetPasswordOrDie();

        $validation_errors = $this->validate($params, true);
        if (count($validation_errors) > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Uno o mas datos no cumplen con condiciones de validacion.', 'errors' => $validation_errors ]);
            die;
        }

        $usr = new \Data\UserModel();
        $usr->getUserById($this->auth->getUserId());

        $p03 = new \Data\Proc03Model();
        $p03->id_user = $this->auth->getUserId();
        $p03->id_procedure = $this->procedure_id;
        $p03->id_procedure_status = 1;
        $p03->start_dt = date('Y-m-d h:i:s', time());
        $p03->last_update_dt = date('Y-m-d h:i:s', time());
        foreach (Proc03Controller::REQ_PARAMS as $paramName) {
            $p03[ $paramName ] = $params[ $paramName ];
        }

        $subproc = 1;

        // check id sp param was sent
        if (array_key_exists('sp', $params)) {
            $subproc = $params[ 'sp' ];
            if (!is_numeric($subproc)) {
                http_response_code(400);
                echo json_encode([ 'message' => 'ID de subprocedimiento debe ser numerico' ]);
                die;
            }
            $subproc = \intval($subproc);
            if ($subproc >= 2 && $subproc <= 5) {
                $p03->subproc = $subproc;
            }

            if ($subproc == 3)
            {
                if (array_key_exists('seguro_origen', $params)) {
                    $p03->seguro_origen = $params[ 'seguro_origen' ];
                }
                else 
                {
                    http_response_code(400);
                    echo json_encode([ 'message' => 'Debe especificar el origen del seguro' ]);
                    die;
                }
            }
        }

        // opcionales
        foreach (self::OPT_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $p03[ $paramName ] = $params[ $paramName ];
            }
        }

        if (!empty($usr->fac_razon_social)) {
            $p03->fac_razon_social = $usr->fac_razon_social;
            $p03->fac_rfc          = $usr->fac_rfc;
            $p03->fac_dom_fiscal   = $usr->fac_dom_fiscal . " CP: " . $usr->fac_cp;
            $p03->fac_email        = $usr->fac_email;
            $p03->fac_telefono     = $usr->fac_telefono;
        }

        $p03->save();

        $procname = 'Solicitud de cambio de vehiculo';
        if (isset($subproc)) {
            switch($subproc) {
                case 1:
                    $procname = 'Cambio de vehiculo';
                    break;
                case 2:
                    $procname = 'Cambio de TAG';
                    break;
                case 3:
                    $procname = 'Actualizacion de poliza de seguro';
                    break;
                case 4:
                    $procname = 'Actualizacion de placas';
                    break;
                case 5:
                    $procname = 'Desactivar TAG';
                    break;
            }
        }

        $device_id = $usr->device_id;
        if (!empty($device_id)) {
            \Util\Notify::sendFirebaseMessage("Trámite iniciado: $procname", $device_id);
        }

        \Util\Mail::sendNewProcEmail($usr->userlogin, $usr->fullname, $procname); //"Solicitud de cambio de vehiculo"


        echo json_encode([ 'message' => 'Tramite registrado exitosamente.', 'ID' => $p03->id ]);
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

        $proc = new \Data\Proc03Model();
        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $user = $this->getUserData($this->auth->getUserId());

        if ($proc[ 'id_user' ] != $this->auth->getUserId() && !\in_array($user['usertype'], [2,3,4])) {
            http_response_code(401);
            echo json_encode([ 'message' => 'Solo el dueño del tramite, tramitador o administrador pueden hacer cambios.' ]);
            die;
        }

        foreach (Proc03Controller::REQ_PARAMS as $paramName) {
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

        $this->checkUserSetPasswordOrDie();

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $proc = new \Data\Proc03Model();
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

        print_r($res);
    }
}
