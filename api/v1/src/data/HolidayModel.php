<?php

namespace Data;

class HolidayModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'holidays');
    }
}
