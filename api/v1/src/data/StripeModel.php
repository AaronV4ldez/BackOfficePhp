<?php

namespace Data;

class StripeModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'stripe_responses');
    }
}
