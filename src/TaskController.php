<?php

class TaskController
{
    public function __construct(
        private TaskGateway $gateway,
        private int $user_id)
    {
    }

    public function processRequest(string $method, ?string $id): void
    {
        if ($id === null) {
            if ($method == "GET") {
                // index
                echo json_encode($this->gateway->getAllForUser($this->user_id));
            } elseif ($method == "POST") {
                // create
                $data = (array) json_decode(file_get_contents("php://input"));
                $errors = $this->getValidationErrors($data);
                if (!empty($errors)) {
                    $this->respondUnprocessableEntity($errors);
                    return;
                }
                $id = $this->gateway->createForUser($data, $this->user_id);
                $this->respondCreated($id);
            } else {
                $this->responseMethodNotAllowed("Allow: GET, POST");
            }
        } else {
            $task = $this->gateway->getForUser($id, $this->user_id);
            if ($task === false) {
                $this->respondNotFound($id);
                return;
            }

            switch ($method) {
                case "GET":
                    // show
                    echo json_encode($task);
                    break;

                case "PATCH":
                    //update
                    $data = (array) json_decode(file_get_contents("php://input"));
                    $errors = $this->getValidationErrors($data, false);
                    if (!empty($errors)) {
                        $this->respondUnprocessableEntity($errors);
                        return;
                    }
                    $rows = $this->gateway->updateForUser($id, $data, $this->user_id);
                    echo json_encode(["message" => "Task updated", "rows" => "$rows"]);
                    break;

                case "DELETE":
                    // delete 
                    $rows = $this->gateway->deleteForUSer($id, $this->user_id);
                    echo json_encode(["message" => "Task deleted", "rows" => $rows]);
                    break;

                default:
                    $this->responseMethodNotAllowed("Allow: GET, PATCH, DELETE");
                    break;
            }
        }
    }

    private function responseMethodNotAllowed(string $allowed_methods): void
    {
        http_response_code(405);
        header($allowed_methods);
    }

    private function respondNotFound(string $id): void
    {
        http_response_code(404);
        echo json_encode(["message" => "Task with ID $id was not found."]);
    }

    private function respondCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode(["message" => "Task created", "id" => $id]);
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["name"])) {
            $errors[] = "Name is required";
        }
        if (!empty($data["priority"])) {
            if (filter_var($data["priority"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "Priority must be an integer";
            }
        }
        return $errors;
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
    }
}
