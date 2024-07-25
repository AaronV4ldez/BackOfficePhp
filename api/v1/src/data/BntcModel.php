<?php

namespace Data;

class BntcModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'bntc');
    }
}