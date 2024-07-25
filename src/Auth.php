<?php

class Auth
{
    private int $user_id;
    //private UserGateway $user_gateway;
    private JWTCodec $codec;

    public function __construct(
        //UserGateway $user_gateway,
        JWTCodec $c
    ) {
        // $this->user_gateway = $user_gateway;
        $this->codec = $c;
    }

    // public function authenticateApiKey(): bool
    // {
    //     if (empty($_SERVER["HTTP_X_API_KEY"])) {
    //         http_response_code(400);
    //         echo json_encode(["message" => "Missing API key"]);
    //         return false;
    //     }
    //     $api_key = $_SERVER["HTTP_X_API_KEY"];

    //     $user = $this->user_gateway->getByAPIKey($api_key);
    //     if ($user === false) {
    //         http_response_code(401);
    //         echo json_encode(["message" => "Invalid API key"]);
    //         return false;
    //     }

    //     $this->user_id = $user["id"];

    //     return true;
    // }

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
            echo json_encode(["message" => "Firma invalida, es necesario iniciar sesión de nuevo."]);
            return false;
        } catch (TokenExpiredException $e) {
            http_response_code(401);
            echo json_encode(["message" => "Autorización expirada, es necesario hacer login de nuevo."]);
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
