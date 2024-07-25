<?php

// ProcController.php
// Funciones para listar tramites, cancelar tramites, asignar un tramite a un tramitador, etc.
// LineaExpressApp

namespace Controllers;

class ProcController extends BaseController
{
    public const REQ_PROC_ASSIGN_PARAMS = [ 'id', 'id_procedure' ];

    public function list(): void
    {
        $this->hasAuthOrDie();

        $idu = $this->auth->getUserId();

        $proc = new \DB\SQL\Mapper($this->f3->get('DB'), 'vw_user_procs');
        $lr = $proc->find(
            array( 'id_procedure_status < 100 and id_user = :idu or id_user_operator = :ido', ':idu' => $idu, ':ido' => $idu ),
            array( 'order' => 'id_procedure_status asc, id_procedure asc, id desc' )
        );

        $res = [];
        if ($lr) {
            foreach ($lr as $row) {
                $res[] = $row->cast();
            }
        }

        echo json_encode($res);
    }

    public function view(): void
    {
        $this->hasAuthOrDie();
        $idu = $this->auth->getUserId();
        $idp = $this->f3->get('PARAMS.idp');
        $id = $this->f3->get('PARAMS.id');

        $proc = null;
        switch ($idp) {
            case 1:
                $proc = new \Data\VUserProc01Model();
                break;
            case 2:
                $proc = new \Data\VUserProc02Model();
                break;
            case 3:
                $proc = new \Data\VUserProc03Model();
                break;
            case 4:
                $proc = new \Data\VUserProc04Model();
                break;
            case 5:
                $proc = new \Data\VUserProc05Model();
                break;
            default:
                $this->respondUnprocessableEntity(["ProcController::view Informacion de tramite invalida: idp=$idp, id=$id"]);
                break;
        }

        if ($proc === null) {
            $this->respondUnprocessableEntity(["ProcController::view Informacion de tramite invalida: idp=$idp, id=$id"]);
        }

        $lr = $proc->loadByID($id);
        if ($lr === false) {
            $this->respondNotFound($id);
        }

        $um = new \Data\UserModel();
        $user = $um->getUserById($idu);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario invalido. Por favor, inicie sesión nuevamente.' ]);
            die;
        }

        // record should belong to user in auth object or the operator
        if (!\in_array($user['usertype'], [2,3,4]) &&
            $idu != $proc[ 'id_user' ]) {
            http_response_code(401);
            echo json_encode([ 'message' => 'Solo el dueño del tramite, un tramitador o administrador pueden consultar este tramite.' ]);
            die;
        }

        echo json_encode($lr->cast());
    }

    public function assignProc(): void
    {
        $this->hasAuthOrDie();

        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_PROC_ASSIGN_PARAMS, $params);

        $id = ( int ) $params[ 'id' ] ;
        $id_procedure = ( int ) $params[ 'id_procedure' ] ;
        $id_user_operator = $this->auth->getUserId();

        $pu = new \Controllers\ProcUtils($this);
        $pu->assignOperatorIfMissing($id, $id_procedure, $id_user_operator);

        echo json_encode([ 'message' => 'Tramitador asignado correctamente.' ]);
    }

    public function assignCita(): void
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_PROC_ASSIGN_PARAMS, $params);

        $id = ( int ) $params[ 'id' ] ;
        $id_procedure = ( int ) $params[ 'id_procedure' ] ;
        $id_user_cita = $this->auth->getUserId();

        $pu = new \Controllers\ProcUtils($this);
        $pu->assignOperatorCitaIfMissing($id, $id_procedure, $id_user_cita);

        echo json_encode([ 'message' => 'Tramitador (cita) asignado correctamente.' ]);
    }

    public function cancelProc()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(['id', 'id_procedure', 'comments'], $params);

        $idu = $this->auth->getUserId();
        $um = new \Data\UserModel();
        $user = $um->getUserById($idu);
        if ($user === false) {
            http_response_code(400);
            echo json_encode([ 'message' => 'Usuario invalido. Por favor, inicie sesión nuevamente.' ]);
            die;
        }

        if (!\in_array($user['usertype'], [2,3,4])) {
            http_response_code(401);
            echo json_encode([ 'message' => 'Solo tramitadpores y administradores pueden eliminar tramites.' ]);
            die;
        }

        $id = ( int ) $params[ 'id' ] ;
        $id_procedure = ( int ) $params[ 'id_procedure' ] ;
        $comments = $params[ 'comments' ];

        $this->cancelProcedure($id_procedure, $id, $comments);

        echo json_encode([ 'message' => 'Tramite cancelado exitosamente.' ]);
    }
}
