<?php

// ClimaController.php
// Funcion para retornar al cliente la informacion del clima de la ultima hora
// LineaExpressApp


namespace Controllers;

class ClimaController extends BaseController
{

    public function clima(): void
    {
        // $this->hasAuthOrDie();

        $db = $this->f3->get('DB');
        $mapper = new \DB\SQL\Mapper($db, 'weather');

        // get last two records sorted by id
        // $found = $mapper->find(array(), array('order' => 'id DESC', 'limit' => 2));
        // $found = $mapper->find([], array('order' => 'id DESC', 'limit' => 2));
    
    	$res = $this->f3->get('DB')->exec("select * from weather order by id desc limit 2");

        // if ($found === false) {
        if (\sizeof($res) === 0) {
            \http_response_code(404);
            echo json_encode(array("message" => "No hay datos de clima"));
        }

        // $res = [];
        // foreach ($found as $row) {
            // $res[] = $row->cast();
        // }
        echo json_encode($res);

    }
}
