<?php

namespace Data;

class VUserProc01Model extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'vw_user_proc01');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }
}
