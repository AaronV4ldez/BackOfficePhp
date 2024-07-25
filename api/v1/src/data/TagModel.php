<?php

namespace Data;

class TagModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'tags');
    }
}
