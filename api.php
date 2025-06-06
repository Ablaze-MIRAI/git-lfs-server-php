<?php
require_once "auth.php";

define("OBJECT_DIR", __DIR__ . "/objects/");

function getUrl($path) {
  $proto = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
  $host = $_SERVER["HTTP_HOST"];
  $script_dir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/");
  return "{$proto}://{$host}{$script_dir}{$path}";
}

function isValidOid($oid) {
  return preg_match('/^[a-f0-9]{64}$/', $oid);
}

header("Content-Type: application/vnd.git-lfs+json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit;
}

if (
  !isset($_SERVER["PHP_AUTH_USER"]) ||
  !isset($_SERVER["PHP_AUTH_PW"]) ||
  !verify_password($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])
) {
  http_response_code(401);
  exit;
}

$request_body = file_get_contents("php://input");
$request_json = json_decode($request_body, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(["message" => "Invalid JSON body"]);
  exit;
}

if (
  !isset($request_json["operation"]) ||
  gettype($request_json["operation"]) !== "string" ||
  !isset($request_json["objects"]) ||
  gettype($request_json["objects"]) !== "array"
) {
  http_response_code(400);
  echo json_encode(["message" => "Invalid JSON body"]);
  exit;
}

$operation = $request_json["operation"];
if ($operation !== "download" && $operation !== "upload") {
  http_response_code(400);
  echo json_encode(["message" => "Invalid JSON body"]);
  exit;
}

$response = ["objects" => []];

foreach ($request_json["objects"] as $object) {
  if (
    !isset($object["oid"]) ||
    gettype($object["oid"]) !== "string" ||
    !isset($object["size"]) ||
    gettype($object["size"]) !== "integer"
  ) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON body"]);
    exit;
  }
  $oid = $object["oid"];
  if (!isValidOid($oid)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid OID"]);
    exit;
  }
  $size = $object["size"];
  $file_path = OBJECT_DIR . $oid;

  if (
    $response === "download" &&
    (
      !file_exists($file_path) ||
      filesize($file_path) !== $size
    )
  ) {
    $response["objects"][] = [
      "oid" => $oid,
      "size" => $size,
      "error" => [
        "code" => 404,
        "message" => "Object not found"
      ]
    ];
  } else {
    $response["objects"][] = [
      "oid" => $oid,
      "size" => $size,
      "actions" => $operation === "upload" ? [
        "upload" => [
          "href" => getUrl("/objects/" . $oid),
          "header" => [],
        ]
      ] : [
        "download" => [
          "href" => getUrl("/objects/" . $oid),
          "header" => [],
        ]
      ]
    ];
  }
}

echo json_encode($response);
