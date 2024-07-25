<?php

namespace Data;

class VUserProcsModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'vw_user_procs');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }

    public function findByUser(int $id_user)
    {
        return $this->find(array("id_user = :id", ":id" => $id_user));
    }

    public function findByOperator(int $id_user_op, int $dios = 0)
    {
        return $this->find(
            array(
                "id_user_operator = :id or or 1 = :dios", 
                ":id" => $id_user_op,
                ":dios" => $dios
            )
        );
    }

    public function findAssignables()
    {
        return $this->find(
            array(
                "id_user_operator is null and id_procedure_status in (1)"
            ), 
            array("order" => "id desc")
        );
    }

    public function findWIP(int $user_id, int $dios = 0)
    {
        return $this->find(
            array(
                "(id_user_operator = :user_id or 1 = :dios ) and id_procedure_status in (2, 3, 4)", 
                ":user_id" => $user_id,
                ":dios" => $dios
            )
        );
    }

    public function findDone(int $user_id, int $dios = 0)
    {
        return $this->find(
            array(
                "(id_user_operator = :user_id or 1 = :dios) and id_procedure_status = 6 and finish_dt >= (current_timestamp() - interval 10 day)", 
                ":user_id" => $user_id,
                ":dios" => $dios
            ),
            array("order" => "finish_dt desc")
        );
    }

    public function findScheduled(int $user_id)
    {
        return $this->find(array("id_user_operator = :user_id and id_procedure_status in (5)", ":user_id" => $user_id));
    }
}
