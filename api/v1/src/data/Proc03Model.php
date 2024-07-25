<?php

namespace Data;

class Proc03Model extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'proc03');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }
}
