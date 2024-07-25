<?php

namespace Data;

class Proc04Model extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'proc04');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }
}
