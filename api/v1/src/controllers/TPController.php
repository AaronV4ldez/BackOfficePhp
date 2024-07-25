<?php

// TPController.php
// Funciones de recarga y consulta de saldo de telepeaje
// LineaExpressApp

namespace Controllers;

class TPController extends BaseController
{
    public function consultaSaldo($tag)
    {
        $this->hasAuthOrDie();

        $tag = $this->f3->get('PARAMS.tag');
        $res = \Util\TPApi::consultaSaldo($tag);

        echo $res;
    }

    public function recargaSaldo($tag, $cant, $folio)
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(["tag", "amount", "ref"], $params);

        $tag = $params["tag"];
        $cant = $params["amount"];
        $folio = $params["ref"];

        $res = \Util\TPApi::recargaSaldo($tag, $cant, $folio);

        echo $res;
    }
}
