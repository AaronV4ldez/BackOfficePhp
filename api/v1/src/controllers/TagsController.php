<?php

// TagsController.php
// Funciones para manejo de tags
// LineaExpressApp

namespace Controllers;

class TagsController extends BaseController
{
    public const REQ_VALID_TAG_PARAMS = ['tag'];

    public function tagIsValid()
    {
        $this->hasAuthOrDie();
        $this->checkPostParamsOrDie();

        $params = $this->getParamsFromRequestBodyOrDie();

        $this->checkRequiredParametersOrDie(self::REQ_VALID_TAG_PARAMS, $params);
        $tag = $params['tag'];

        $tag_v = $this->f3->get('DB')->
        exec(
            "select v.tag, v.saldo balance, v.marca, v.linea, v.modelo, v.placa, v.color, v.is_active, u.fullname, u.userlogin
                from vehicles v
                left outer join users u on u.id = v.id_user
                where v.tag = :tag",
            array(':tag' => $tag)
        );

        if (count($tag_v) > 0) {
            echo json_encode($tag_v[0]);
        } else {
            \http_response_code(404);
            echo \json_encode(['message' => 'Tag no encontrado.']);
        }
    }

    private function loadSentriVehicles($sentri, $email)
    {
        $userInfo = \Util\LEApi::getUserInfo($sentri, $email);
        $userInfo = \json_decode($userInfo, true);

        $res = [];
        if (\is_array($userInfo) && \count($userInfo) > 0) {
            foreach ($userInfo as $ctl_user) {
                $vs = \Util\LEApi::getUserCars($ctl_user["id"]);
                $vs = \json_decode($vs, true);

                if (\is_array($vs) && \count($vs) > 0) {
                    foreach ($vs as $v) {
                        $car = ['placa' => $v["license_plate"], 'tag' => $v["contract_tag"]];
                        $res[] = $car;
                    }
                }
            }
        } else {
        }

        return $res;
    }


    public function gfs()
    {
        $this->hasAuthOrDie();

        $params = $this->f3->get('PARAMS');
        $sentri = $params['sentri'];
        $email = $params['email'];
        $placa = $params['placa'];

        $vehicles = $this->loadSentriVehicles($sentri, $email);
        if (empty($vehicles)) {
            \http_response_code(404);
            echo \json_encode(['message' => 'No se encontraron vehículos asociados al usuario.']);
            return;
        }

        // search $vehicles array for placa
        $tag = '';
        foreach ($vehicles as $v) {
            if ($v['placa'] == $placa) {
                $tag = $v['tag'];
                break;
            }
        }

        echo \json_encode(['tag' => $tag]);

    }

    public function tagExists()
    {
        $this->hasAuthOrDie();

        $params = $this->f3->get('PARAMS');
        $tag = $params['tag'];

        // check if tag param is present
        if (empty($tag)) {
            \http_response_code(400);
            echo \json_encode(['message' => 'Debe especificar un TAG.']);
            return;
        }

        $tp = null;
        try {
		$tp = \Util\TPApi::consultaSaldo($tag);
		// echo $tp;
		$tp = json_decode($tp, true);
		if (array_key_exists('title', $tp)) {
            $st = $tp["title"];
            if (!empty($st)) {
                $tp = $st;
	    }
		}
        } catch (\Exception $e) {
            $tp = 'NR';
	} catch (\Error $t) {
	    $tp = 'NR';
	}

        echo \json_encode(['tag' => $tag, 'tp' => $tp]);
        // }
    }
}
