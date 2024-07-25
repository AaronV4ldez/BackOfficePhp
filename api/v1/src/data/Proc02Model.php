<?php

namespace Data;

class Proc02Model extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'proc02');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }
}
