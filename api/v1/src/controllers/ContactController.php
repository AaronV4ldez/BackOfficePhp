<?php

// ContactController.php
// Recibe informacion de contacto de un usuario y la envia por email al administrador
// LineaExpressApp

namespace Controllers;

class ContactController extends BaseController
{
    private const REQUIRED_PARAMETERS = ['nombre', 'apellido', 'email', 'tel', 'mensaje'];

    public function submit()
    {
        $this->checkPostParamsOrDie();
        $params = $this->getParamsFromRequestBodyOrDie();
        $this->checkRequiredParametersOrDie(self::REQUIRED_PARAMETERS, $params);

        \Util\Mail::sendContactEmail(
            $_ENV["CONTACT_EMAIL"],
            "Administrador de " . \Util\Mail::getAppName(),
            $params['nombre'],
            $params['apellido'],
            $params['email'],
            $params['tel'],
            $params['mensaje']
        );

    }
}
