<?php

class BaseController
{
    private $valid_commands = ["CREATE", "UPDATE", "DELETE", "GETSINGLE", "GETALL"];

    private string $command;
    private array $params;
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setValidCommands(array $vc)
    {
        $this->valid_commands = $vc;
    }

    public function getValidCommands(): array
    {
        return $this->valid_commands;
    }

    public function checkParameters()
    {
        // make sure to only process POST requests
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            header("Allow: POST");
            die;
        }

        // make sure we receive a "command" parameter
        $this->params = (array) json_decode(file_get_contents("php://input"));
        if (!array_key_exists("command", $this->params)) {
            http_response_code(400);
            echo json_encode(["message" => "Comando no especificado. (debe ser uno de los sig: " . implode(" ", $this->valid_commands) . ")."]);
            die;
        }

        $this->command = trim(strtoupper($this->params["command"]));
        unset($this->params["command"]);

        // make sure we received a valid command
        if (!in_array($this->command, $this->valid_commands)) {
            http_response_code(400);
            echo json_encode(["message" => "Comando invalido (debe ser uno de los sig: " . implode(" ", $this->valid_commands) . ")."]);
            die;
        }
    }

    public function processRequest(): void
    {
        switch ($this->command) {
            case "CREATE":
                $this->doCreate();
                break;
            case "UPDATE":
                $this->doUpdate();
                break;
            case "DELETE":
                $this->doDelete();
                break;
            case "GETSINGLE":
                $this->doGetSingle();
                break;
            case "GETALL":
                $this->doGetAll();
                break;
        }
    }

    public function checkFormParamOrDie(string $paramName): void
    {
        if (!array_key_exists($paramName, $this->getParams())) {
            http_response_code(400);
            echo json_encode(["message" => "Parametro ID no recibido."]);
            die;
        }
    }

    public function doCreate()
    {
        $this->respondNotImplemented();
    }

    public function doUpdate()
    {
        $this->respondNotImplemented();
    }

    public function doDelete()
    {
        $this->respondNotImplemented();
    }

    public function doGetSingle()
    {
        $this->respondNotImplemented();
    }

    public function doGetAll()
    {
        $this->respondNotImplemented();
    }

    protected function getValidationErrors(array $data, bool $is_new = true): array
    {
        return [];
    }

    protected function respondNotImplemented(): void
    {
        http_response_code(501);
        echo json_encode(["message" => "Comando no implementado"]);
        die;
    }

    protected function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
        die;
    }

    protected function responseMethodNotAllowed(string $allowed_methods): void
    {
        http_response_code(405);
        header($allowed_methods);
        die;
    }

    protected function respondNotFound(string $id): void
    {
        http_response_code(404);
        echo json_encode(["message" => "Usuario con ID $id no encontrado."]);
        die;
    }

    protected function respondCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode(["message" => "Usuario creado", "id" => $id]);
        die;
    }
}
