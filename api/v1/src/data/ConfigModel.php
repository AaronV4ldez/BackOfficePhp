<?php

namespace Data;

class ConfigModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'config');
    }
}
