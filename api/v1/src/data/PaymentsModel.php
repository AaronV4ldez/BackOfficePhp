<?php

namespace Data;

class PaymentsModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'payments');
    }

    
}
