<?php

namespace Data;

class VehiclesModel extends \DB\SQL\Mapper
{
    public function __construct()
    {
        parent::__construct(\Base::instance()->get('DB'), 'vehicles');
    }

    public function findByUserID(int $uid)
    {
        return $this->find(array("id_user = :id", ":id" => $uid));
    }

    public function findByTag(string $tag)
    {
        return $this->load(array("tag = :tag", ":tag" => $tag));
    }

    public function findById($id)
    {
        return $this->load(array("id = :id", ":id" => $id));
    }

    public function findByVehicleIdUserID(int $vid, int $uid)
    {
        return $this->load(array("id_user = :id AND id = :vid", ":id" => $uid, ":vid" => $vid));
    }
}
