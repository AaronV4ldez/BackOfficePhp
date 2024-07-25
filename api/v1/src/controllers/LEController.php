<?php

// LEController.php
// Funciones para obtener informacion de vehiculos de LineaExpress
// LineaExpressApp

namespace Controllers;

class LEController extends BaseController
{
    public function getUserInfo()
    {
        $this->hasAuthOrDie();

        $sentry = $this->f3->get('PARAMS.sentri');
        $email = $this->f3->get('PARAMS.email');
        $res = \Util\LEApi::getUserInfo($sentry, $email);

        $rd = json_decode($res, true);
        if (!is_array($rd)) {
            \http_response_code(400);
            echo \json_encode(['message' => $res]);
            return;
        }

        echo $res;
    }

    public function getUserCars()
    {
        $this->hasAuthOrDie();

        $id = $this->f3->get('PARAMS.id');
        $res = \Util\LEApi::getUserCars($id);

        $rd = json_decode($res, true);
        $res=[];
        if (is_array($rd)) {
            foreach ($rd as $r) {
                // $r['imgurl'] = "https://cdn.imagin.studio/getImage?customer=mxlineaexpres&make=" . $r["brand"]."&modelFamily=".$r["model"]."&modelYear=".$r["year"]."&width=400";
                try {
                    $r['imgurl'] = \Util\Img::getCarImg($r["brand"], $r["model"], $r["year"]);
                } catch (\Exception $e) {
                    $r['imgurl'] = "";
                }

                $res[] = $r;
            }
            $res = json_encode($res);
        }

        echo $res;
    }

    public function getCarCrossings()
    {
        $this->hasAuthOrDie();

        $idu = $this->f3->get('PARAMS.idu');
        $idv = $this->f3->get('PARAMS.idv');
        $res = \Util\LEApi::getCarLatestCrossings($idu, $idv);

        echo $res;
    }

    public function getCarCrossingsnew()
    {
        $this->hasAuthOrDie();

        $tag = $this->f3->get('PARAMS.tag');
        $res = \Util\LEApi::getCarLatestCrossingsnew($tag);

        echo $res;
    }
}
