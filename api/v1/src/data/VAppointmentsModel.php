<?php

namespace Data;

use DateTime;

class VAppointmentsModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'vw_appointments');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id_appointment = :id", ":id" => $id));
    }

    public function findByUser(int $id_user)
    {
        return $this->find(array("id_user = :id", ":id" => $id_user));
    }

    public function findByOperator(int $id_user_op)
    {
        return $this->find(array("id_user_operator = :id", ":id" => $id_user_op));
    }

    public function findByOperatorAndDate(int $id_user_op, DateTime $date)
    {
        return $this->find(array("id_user_operator = :id_user_op and date(dt) = :date", ":id_user_op" => $id_user_op, ":date" => $date->format('Y-m-d')));
    }
}
