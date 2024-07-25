<?php

// ApiAuth.php
// Validacion de token de usuarios moviles
// LineaExpressApp

class ApiAuth
{
    private int $user_id;
    private JWTCodec $codec;

    public function __construct(
        JWTCodec $c
    ) {
        $this->codec = $c;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function authenticateAccessToken(): bool
    {
        if (!preg_match("/^Bearer\s+(.*)$/", $_SERVER["HTTP_AUTHORIZATION"], $matches)) {
            http_response_code(400);
            echo json_encode(["message" => "Token de autorización incompleto o inexistente."]);
            return false;
        }
        
        try {
            $data = $this->codec->decode($matches[1]);
        } catch (InvalidSignatureException $e) {
            http_response_code(401);
            echo json_encode(["message" => "Firma invalida, es necesario iniciar una nueva sesión."]);
            return false;
        } catch (TokenExpiredException $e) {
            http_response_code(401);
            echo json_encode(["message" => "Autorización expirada, es necesario iniciar una nueva sesión."]);
            return false;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => "ERROR: " . $e->getMessage()]);
            return false;
        }
        
        $this->user_id = $data["sub"];
        return true;
    }

}
