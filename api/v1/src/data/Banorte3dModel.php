<?php

namespace Data;

class Banorte3dModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'banorte3d');
    }
}