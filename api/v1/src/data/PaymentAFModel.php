<?php

namespace Data;

class PaymentAFModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'payment_af');
    }
}