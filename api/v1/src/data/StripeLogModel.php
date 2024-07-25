<?php

namespace Data;

class StripeLogModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'stripe_responses_log');
    }
}
