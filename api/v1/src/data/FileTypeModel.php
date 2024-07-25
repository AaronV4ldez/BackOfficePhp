<?php

namespace Data;

class FileTypeModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'filetypes');
    }

    public function loadByID(int $id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }

    public function loadIDsToArray()
    {
        $this->load();
        $ids = [];
        foreach ($this->query as $item) {
            $ids[] = $item->id;
        }
        return $ids;
    }

    public function loadAll()
    {
        $this->load();
        $res=[];
        foreach ($this->query as $item) {
            $res[] = $item->cast();
        }
        return $res;
    }
}
