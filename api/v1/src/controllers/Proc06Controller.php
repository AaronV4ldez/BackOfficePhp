<?php

// modulo descontinuado

namespace Controllers;

class Proc06Controller extends BaseController
{
    public const REQ_PARAMS = [
        'num_tag',
        'veh_marca', 'veh_modelo', 'veh_color', 'veh_anio', 'veh_placas', 'veh_origen',
        'motivo', 'veh_adscrip_zaragoza', 'veh_adscrip_lerdo', 'sentri', 'sentri_vencimiento'
    ];

    public const OPT_PARAMS = ['comments'];

    private int $procedure_id = 6;

    public function view(): void
    {
        $this->hasAuthOrDie();

        $id = $this->f3->get('PARAMS.id');
        // since param is part of route, we are sure param will be there

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([ 'message' => 'ID debe ser numerico' ]);
            die;
        }

        $proc = new \Data\VUserProc06Model();
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

        $this->checkRequiredParametersOrDie(Proc06Controller::REQ_PARAMS, $params);

        $validation_errors = $this->validate($params, true);
        if (count($validation_errors) > 0) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Uno o mas datos no cumplen con condiciones de validacion.', 'errors' => $validation_errors ]);
            die;
        }

        $this->checkUserSetPasswordOrDie();

        $p06 = new \Data\Proc06Model();
        $p06->id_user = $this->auth->getUserId();
        $p06->id_procedure = $this->procedure_id;
        $p06->id_procedure_status = 1;
        $p06->start_dt = date('Y-m-d h:i:s', time());
        $p06->last_update_dt = date('Y-m-d h:i:s', time());
        foreach (Proc06Controller::REQ_PARAMS as $paramName) {
            $p06[ $paramName ] = $params[ $paramName ];
        }
        // opcionales
        foreach (self::OPT_PARAMS as $paramName) {
            if (array_key_exists($paramName, $params)) {
                $p06[ $paramName ] = $params[ $paramName ];
            }
        }

        $p06->save();

        $usr = new \Data\UserModel();
        $usr->getUserById($this->auth->getUserId());
        \Util\Mail::sendNewProcEmail($usr->userlogin, $usr->fullname, "Registro de vehiculo adicional");

        echo json_encode([ 'message' => 'Tramite registrado exitosamente.', 'ID' => $p06->id ]);
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

        $proc = new \Data\Proc06Model();
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

        foreach (Proc06Controller::REQ_PARAMS as $paramName) {
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

        $proc = new \Data\Proc06Model();
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

        if ($is_new) {
            if ($data[ 'veh_adscrip_zaragoza' ] == 0 && $data[ 'veh_adscrip_lerdo' ] == 0) {
                $res[] = 'Se debe dar de alta en cuando menos uno de los dos puentes.';
            }
        }

        return $res;
    }
}
