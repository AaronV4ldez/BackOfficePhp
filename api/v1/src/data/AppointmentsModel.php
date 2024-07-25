<?php

namespace Data;

class AppointmentsModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'appointments');
    }
}
