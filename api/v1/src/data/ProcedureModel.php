<?php

namespace Data;

class ProcedureModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'procedures');
    }
}