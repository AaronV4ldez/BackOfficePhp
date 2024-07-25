<?php

// Proc02Controller.php
// Funciones para majeo de tramites de cambio de puente o convenio
// LineaExpressApp

namespace Controllers;

class Proc02Controller extends BaseController
{
    public const REQ_PARAMS = [
        'dom_calle', 'dom_numero_ext', 'dom_colonia', 'dom_ciudad', 'dom_estado', 'dom_cp',
        // 'fac_razon_social', 'fac_rfc', 'fac_dom_fiscal', 'fac_email', 'fac_telefono',
        // datos de facturacion opcionanales, Nayo, 2022-12-09
        'veh_marca', 'veh_modelo', 'veh_color', 'veh_anio', 'veh_placas', 'veh_origen',
        'conv_saldo', 'conv_anualidad', 'sentri', 'sentri_vencimiento'
    ];

    public const OPT_PARAMS = ['comments', 'fac_razon_social', 'fac_rfc', 'fac_dom_fiscal', 'fac_email', 'fac_telefono'];


    private int $procedure_id = 2;

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

        $proc = new \Data\VUserProc02Model();
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

        $this->checkRequiredParametersOrDie(Proc02Controller::REQ_PARAMS, $params);

        $validation_errors = $this->validate($params, true);
        if (count($validation_errors) > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Uno o mas datos no cumplen con condiciones de validacion.', 'errors' => $validation_errors ]);
            die;
        }

        $this->checkUserSetPasswordOrDie();

        $usr = new \Data\UserModel();
        $usr->getUserById($this->auth->getUserId());

        $p02 = new \Data\Proc02Model();
        $p02->id_user = $this->auth->getUserId();
        $p02->id_procedure = $this->procedure_id;
        $p02->id_procedure_status = 1;
        $p02->start_dt = date('Y-m-d h:i:s', time());
        $p02->last_update_dt = date('Y-m-d h:i:s', time());
        foreach (Proc02Controller::REQ_PARAMS as $paramName) {
            $p02[ $paramName ] = $params[ $paramName ];
        }
        // opcionales
        foreach (self::OPT_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $p02[ $paramName ] = $params[ $paramName ];
            }
        }

        // check id sp param was sent
        if (array_key_exists('sp', $params)) {
            $subproc = $params[ 'sp' ];
            if (!is_numeric($subproc)) {
                http_response_code(400);
                echo json_encode([ 'message' => 'ID de subprocedimiento debe ser numerico' ]);
                die;
            }
            $subproc = \intval($subproc);
            if ($subproc == 6 || $subproc == 7) {
                $p02->subproc = $subproc;
            }
        }

        if (!empty($usr->fac_razon_social)) {
            $p02->fac_razon_social = $usr->fac_razon_social;
            $p02->fac_rfc          = $usr->fac_rfc;
            $p02->fac_dom_fiscal   = $usr->fac_dom_fiscal . " CP: " . $usr->fac_cp;
            $p02->fac_email        = $usr->fac_email;
            $p02->fac_telefono     = $usr->fac_telefono;
        }

        $p02->save();

        \Util\Mail::sendNewProcEmail($usr->userlogin, $usr->fullname, "Inscripcion en otro puente");

        $device_id = $usr->device_id;
        if (!empty($device_id)) {

            $procname = 'Inscripcion en otro puente';
            if (isset($subproc)) {
                if ($subproc == 6) {
                    $procname = 'Cambio de puente';
                } else if ($subproc == 7) {
                    $procname = 'Cambio de convenio';
                }
            }

            \Util\Notify::sendFirebaseMessage("Trámite iniciado: $procname", $device_id);
        }


        echo json_encode([ 'message' => 'Tramite registrado exitosamente.', 'ID' => $p02->id ]);
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

        $proc = new \Data\Proc02Model();
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

        foreach (Proc02Controller::REQ_PARAMS as $paramName) {
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

        $proc = new \Data\Proc02Model();
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
