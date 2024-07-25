<?php

// AppointmentsController.php 
// funciones para manejo de citas
// LineaExpressApp

namespace Controllers;

class AppointmentsController extends BaseController
{
    public const REQ_VIEWAVAIL_PARAMS = ['date'];
    public const REQ_CREATEAPPT_PARAMS = ['id_proc', 'id_proc_type', 'date', 'time'];
    public const REQ_FINISHAPPT_PARAMS = ['id', 'tag', 'comments'];
    public const REQ_CANCELAPPT_PARAMS = ['id', 'comments'];

    public function availableByDate()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $this->checkUserSetPasswordOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_VIEWAVAIL_PARAMS, $params);

        $citas = $this->f3->get('DB')->exec("call sp_citas_disponibles(:fecha);", array(':fecha' => $params['date']));
        echo json_encode($citas);
    }

    private function checkValidDateOrDie($date)
    {
        if (!\Util\Misc::isValidISODate($date)) {
            \http_response_code(400);
            echo json_encode(array('message' => 'La fecha no es válida'));
            die;
        }
    }

    private function checkValidTimeOrDie($time)
    {
        if (!\Util\Misc::isValid24hTime($time)) {
            \http_response_code(400);
            echo json_encode(array('message' => 'La hora no es válida'));
            die;
        }
    }

    private function getAppointmentPadding()
    {
        $config = new \Data\ConfigModel();
        $config->load(array('id = :id', ':id' => 1));
        try {
            $padding = intval($config->cita_dias_despues);
            $limit = intval($config->cita_dias_limite);
        } catch (\Exception $e) {
            $padding = 3;
            $limit = 60;
        }
        return array($padding, $limit);
    }

    private function checkAvailableAppointmentsOrDie($date, $time)
    {
        $citas = $this->f3->get('DB')->exec("call sp_citas_disponibles(:fecha);", array(':fecha' => $date));
        // \var_dump($citas);
        $citas = array_filter($citas, function ($cita) use ($time) {
            return $cita['inicio'] == $time;
        });

        if (\sizeof($citas) === 0) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No hay citas disponibles para ese dia o su cita esta fuera de horario.'));
            die;
        }
        $cita = array_pop(array_reverse($citas));

        if ($cita['citas'] < 1) {
            \http_response_code(400);
            var_dump($cita);
            echo json_encode(array('message' => 'No hay citas disponibles para esa hora.'));
            die;
        }
    }

    private function checkAppointmentPaddingOrDie($date, $time)
    {
        list($padding, $limit) = $this->getAppointmentPadding();
        // var_dump($padding);
        // var_dump($limit);

        $dt = date("Y-m-d H:i", strtotime("$date $time"));
        $date_padding = date("Y-m-d H:i", strtotime("+ " .  \strval($padding) . " days"));
        $date_limit = date("Y-m-d H:i", strtotime("+ " .  \strval($limit) . " days"));

        // \var_dump($date_padding);
        // \var_dump($dt);

        if ($dt < $date_padding) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No puede agendar una cita con menos de 3 días de anticipación'));
            die;
        }

        if ($date > $date_limit) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No puede agendar una cita con más de ' . \strval($limit) . ' días de anticipación'));
            die;
        }

        return $dt;
    }

    private function checkProcedureOwnershipAndPreAuthOrDie($id, $idp)
    {
        $userproc = new \Data\VUserProcsModel();
        $fnd = $userproc->load(array('id = :id and id_procedure = :idproc and (id_user = :iduser or id_user_operator = :iduserop)',
            ':id' => (int)$id, ':idproc' => (int)$idp, ':iduser' => $this->auth->getUserId(), ':iduserop' => $this->auth->getUserId()));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El tramite no pertenece al usuario que solicita la cita.'));
            die;
        }

        if ($userproc->tramite_status !== 'Tramite pre-autorizado') {
            \http_response_code(400);
            echo json_encode(array('message' => "No se puede agendar una cita si el tramite no esta pre-autorizado (estado actual: $userproc->tramite_status)."));
            die;
        }
    }

    private function checkProcedureOwnershipAndScheduledOrDie($id, $idp)
    {
        $userproc = new \Data\VUserProcsModel();
        $fnd = $userproc->load(array('id = :id and id_procedure = :idproc and (id_user = :iduser or id_user_operator = :iduserop)',
            ':id' => (int)$id, ':idproc' => (int)$idp, ':iduser' => $this->auth->getUserId(), ':iduserop' => $this->auth->getUserId()));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        if ($userproc->tramite_status !== 'Cita agendada') {
            \http_response_code(400);
            echo json_encode(array('message' => "No se puede cambiar una cita si el tramite no tiene Cita agendada (estado actual: $userproc->tramite_status)."));
            die;
        }
    }

    private function checkIfHolidayOrDie($date)
    {
        $hday = $this->f3->get('DB')->exec("select comment from holidays where dt = :fecha limit 1", array(':fecha' => $date));
        if (count($hday) === 0) {
            return;
        }

        \http_response_code(400);
        echo json_encode(array('message' => 'No se puede agendar una cita para un día festivo: ' . $hday[0]['comment']));
        die;
    }

    private function checkIfWeekendOrDie($date)
    {
        $day = date('N', strtotime($date));
        if ($day == 7) {
            // $day == 6 ||
            \http_response_code(400);
            echo json_encode(array('message' => 'No se puede agendar una cita para un fin de semana'));
            die;
        }
    }

    public function createAppointment()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $this->checkUserSetPasswordOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_CREATEAPPT_PARAMS, $params);

        $id = $params['id_proc'];
        $idp = $params['id_proc_type'];
        $date = $params['date'];
        $time = $params['time'];

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id_up = :idup and id_procedure = :idp ', ':idup' => $id, ':idp' => $idp));

        if ($fnd !== false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'Ya existe una cita para este procedimiento'));
            die;
        }

        $this->checkValidDateOrDie($date);

        $this->checkValidTimeOrDie($time);

        $dt = $this->checkAppointmentPaddingOrDie($date, $time);

        $this->checkAvailableAppointmentsOrDie($date, $time);

        $this->checkProcedureOwnershipAndPreAuthOrDie($id, $idp);

        $proc = $this->getProcModelInstance($idp);
        $fnd = $proc->load(array('id = :id and id_user = :idu', ':id' => $id, ':idu' => $this->auth->getUserId()));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        $this->checkIfWeekendOrDie($date);

        $this->checkIfHolidayOrDie($date);

        $proc->id_procedure_status = 5; // cita agendada
        $proc->save();

        $appt->reset();
        $appt->id_up = $id;
        $appt->id_procedure = $idp;
        $appt->dt = $dt;
        $appt->save();

        $pd = $this->f3->get('DB')->exec("select * from vw_user_procs where id = :id and id_procedure = :idp", array(':id' => $id, ':idp' => $idp));
        if (count($pd) > 0) {
            $pd = $pd[0];
            \Util\Mail::sendNewApptEmail($pd["usuario_email"], $pd["usuario_nombre"], $pd["tramite"], $date, $time);
        }

        echo json_encode(array('message' => "Cita creada: $date $time"));
    }

    public function changeAppointmentDate()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $this->checkUserSetPasswordOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_CREATEAPPT_PARAMS, $params);

        $id = $params['id_proc'];
        $idp = $params['id_proc_type'];
        $date = $params['date'];
        $time = $params['time'];

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id_up = :idup and id_procedure = :idp ', ':idup' => $id, ':idp' => $idp));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe una cita para este procedimiento. No se puede cambiar la fecha.'));
            die;
        }

        $this->checkValidDateOrDie($date);

        $this->checkValidTimeOrDie($time);

        $dt = $this->checkAppointmentPaddingOrDie($date, $time);

        // var_dump($dt);
        // var_dump($appt->dt);
        // die;

        if (strtotime($appt->dt) == strtotime($dt)) {
            \http_response_code(400);
            echo json_encode(array('message' => 'La cita ya esta agendada para esa fecha y hora.'));
            die;
        }

        $this->checkAvailableAppointmentsOrDie($date, $time);

        $this->checkProcedureOwnershipAndScheduledOrDie($id, $idp);

        $proc = $this->getProcModelInstance($idp);
        $fnd = $proc->load(array('id = :id and id_user = :idu', ':id' => $id, ':idu' => $this->auth->getUserId()));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        $this->checkIfWeekendOrDie($date);

        $this->checkIfHolidayOrDie($date);

        $appt->dt = $dt;
        $appt->save();

        $pd = $this->f3->get('DB')->exec("select * from vw_user_procs where id = :id and id_procedure = :idp", array(':id' => $id, ':idp' => $idp));
        if (count($pd) > 0) {
            $pd = $pd[0];
            \Util\Mail::sendNewApptEmail($pd["usuario_email"], $pd["usuario_nombre"], $pd["tramite"], $date, $time);
        }

        echo json_encode(array('message' => "Cita cambiada: $date $time"));
    }

    public function getAppointmentDetail()
    {
        $this->hasAuthOrDie();

        $id = $this->f3->get('PARAMS.id');

        if (!is_numeric($id)) {
            \http_response_code(400);
            echo json_encode(array("message" => "El ID de la cita debe ser numérico"));
            die;
        }

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id = :id', ':id' => $id));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe la cita solicitada.'));
            die;
        }

        echo json_encode($fnd->cast());
    }

    public function getAppointmentList()
    {
        $this->hasAuthOrDie();

        $user_id = $this->auth->getUserId();
        $user = new \Data\UserModel();
        $fnd = $user->load(array('id = :id', ':id' => $user_id));

        if ($fnd == false) {
            \http_response_code(400);
            echo json_encode(array("message" => "El usuario no es valido para consultar citas."));
            die;
        }

        $appt_list = $this->f3->get('DB')->exec("select * from vw_appointments where id_user = :id", array(':id' => $user_id));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe la cita solicitada.'));
            die;
        }

        echo json_encode($appt_list);
    }

    public function cancelAppointment()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_CANCELAPPT_PARAMS, $params);

        $id = $params['id'];

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id = :id', ':id' => $id));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe la cita especificada. No se puede cancelar.'));
            die;
        }

        $id_procedure = $fnd['id_procedure'];
        $id_up = $fnd['id_up'];

        $u = $this->getUserData($this->auth->getUserId());
        if (!\in_array($u['usertype'], [2,3,4])) {
            \http_response_code(400);
            echo json_encode(array('message' => 'Usted no tiene permisos para cancelar citas.'));
            die;
        }
        $proc = $this->getProcModelInstance($id_procedure);
        $fnd = $proc->load(array('id = :id', 
                                 ':id' => $id_up)
                                //  , 
                                //  ':idu' => $this->auth->getUserId(), 
                                //  ':idop' => $this->auth->getUserId())
                                );

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        $vmd = $this->f3->get('DB')->exec(
            'select appt.id id_appt, p.id,  p.usuario_email, p.usuario_nombre, p.tramite ' .
            'from appointments appt join vw_user_procs p on p.id = appt.id_up and p.id_procedure = appt.id_procedure '.
            'where appt.id = :id',
            array(':id' => $id)
        );
        if (count($vmd) > 0) {
            $vmd = $vmd[0];
        } else {
            $vmd = false;
        }

        $proc->id_procedure_status = 4;
        $proc->save();

        $appt->erase();

        if (!empty($vmd['usuario_email'])) {
            \Util\Mail::sendApptCancelEmail($vmd['usuario_email'], $vmd['usuario_nombre'], $vmd['tramite'], $params['comments']);
        }

        $user = new \Data\UserModel();
        $user->load(array('id = :id', ':id' => $proc['id_user']));

        if (!empty($user[ 'device_id' ])) {
            $procedure = new \Data\VUserProcsModel();
            $procedure->load(array( 'id = :id and id_procedure= :idp', ':id' => $id_up, ':idp' => $id_procedure ));

            \Util\Notify::sendFirebaseMessage("Cita cancelada: " . $procedure['tramite'], $user[ 'device_id' ]);
        }

        echo json_encode(array('message' => 'Cita cancelada. Puede volver a agendarla.'));
    }

    public function cancelProc()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_CANCELAPPT_PARAMS, $params);

        $id = $params['id'];

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id = :id', ':id' => $id));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe la cita especificada. No se puede cancelar.'));
            die;
        }

        $id_procedure = $fnd['id_procedure'];
        $id_up = $fnd['id_up'];

        $u = $this->getUserData($this->auth->getUserId());
        if (!\in_array($u['usertype'], [2,3,4])) {
            \http_response_code(400);
            echo json_encode(array('message' => 'Usted no tiene permisos para cancelar citas.'));
            die;
        }
        $proc = $this->getProcModelInstance($id_procedure);
        $fnd = $proc->load(array('id = :id', 
                                 ':id' => $id_up)
                                //  , 
                                //  ':idu' => $this->auth->getUserId(), 
                                //  ':idop' => $this->auth->getUserId())
                                );

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        $vmd = $this->f3->get('DB')->exec(
            'select appt.id id_appt, p.id,  p.usuario_email, p.usuario_nombre, p.tramite ' .
            'from appointments appt join vw_user_procs p on p.id = appt.id_up and p.id_procedure = appt.id_procedure '.
            'where appt.id = :id',
            array(':id' => $id)
        );
        if (count($vmd) > 0) {
            $vmd = $vmd[0];
        } else {
            $vmd = false;
        }

        $appt->erase();

        $proc->id_procedure_status = 100;
        $proc->last_update_dt = date('Y-m-d H:i:s');
        $proc->comments .= ' Nota de cancelación: ' . $params['comments'];
        $proc->save();

        $this->genProcPDF($id_procedure, $id_up, true);

        if (!empty($vmd['usuario_email'])) {
            \Util\Mail::sendProcCancelEmail($vmd['usuario_email'], $vmd['usuario_nombre'], $vmd['tramite'], $params['comments']);
        }

        $user = new \Data\UserModel();
        $user->load(array('id = :id', ':id' => $proc['id_user']));

        if (!empty($user[ 'device_id' ])) {
            $procedure = new \Data\VUserProcsModel();
            $procedure->load(array( 'id = :id and id_procedure= :idp', ':id' => $id_up, ':idp' => $id_procedure ));

            \Util\Notify::sendFirebaseMessage("Trámite cancelado: " . $procedure['tramite'], $user[ 'device_id' ]);
        }

        echo json_encode(array('message' => 'Trámite cancelado. Usuario debera iniciar proceso de nuevo.'));
    }

    public function finishAppointment()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_FINISHAPPT_PARAMS, $params);

        $id = $params['id'];

        $appt = new \Data\AppointmentsModel();
        $fnd = $appt->load(array('id = :id', ':id' => $id));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'No existe la cita especificada. No se puede finalizar tramite.'));
            die;
        }

        $id_procedure = $fnd['id_procedure'];
        $id_up = $fnd['id_up'];

        $proc = $this->getProcModelInstance($id_procedure);
        $fnd = $proc->load(array('id = :id and (id_user = :idu  or id_user_operator = :idop)' , ':id' => $id_up, ':idu' => $this->auth->getUserId(), ':idop' => $this->auth->getUserId()));

        if ($fnd === false) {
            \http_response_code(400);
            echo json_encode(array('message' => 'El usuario no tiene ese procedimiento'));
            die;
        }

        $vmd = $this->f3->get('DB')->exec(
            'select appt.id id_appt, p.id,  p.usuario_email, p.usuario_nombre, p.tramite ' .
            'from appointments appt join vw_user_procs p on p.id = appt.id_up and p.id_procedure = appt.id_procedure '.
            'where appt.id = :id',
            array(':id' => $id)
        );
        if (count($vmd) > 0) {
            $vmd = $vmd[0];
        } else {
            $vmd = false;
        }

        $proc->id_procedure_status = 6;
        $proc->tag = $params['tag'];
        $proc->comments = $params['comments'];
        $proc->finish_dt = date('Y-m-d H:i:s');

        if ($proc->id_procedure == 1 && isset($params['conv_anualidad']) && isset($params['conv_saldo'])) {
            if (is_numeric($params['conv_anualidad'])) {
                $proc->conv_anualidad = $params['conv_anualidad'];
            }
            if (is_numeric($params['conv_saldo'])) {
                $proc->conv_anualidad = $params['conv_saldo'];
            }
        }

        $proc->save();


        $this->genProcPDF($id_procedure, $id_up);

        $this->setUserSentriData($this->auth->getUserId(), $proc->sentri, $proc->sentri_vencimiento);

        $appt->comments = $params['comments'];
        $appt->save();

        $procedure = new \Data\VUserProcsModel();
        $procedure->load(array( 'id = :id and id_procedure= :idp', ':id' => $proc[ 'id' ], ':idp' => $proc[ 'id_procedure' ] ));

        if (!empty($vmd['usuario_email'])) {
            \Util\Mail::sendProcFinishEmail($vmd['usuario_email'], $vmd['usuario_nombre'], $vmd['tramite'], $params['comments']);
        }

        $user = new \Data\UserModel();
        $fnd = $user->load(array('id = :id', ':id' => $proc['id_user']));
        $device_id = $user['device_id'];

        if (!empty($device_id)) {
            \Util\Notify::sendFirebaseMessage("Trámite finalizado: " . $procedure['tramite'], $device_id);
        }

        echo json_encode(array('message' => 'Tramite finalizado.'));
    }
}
