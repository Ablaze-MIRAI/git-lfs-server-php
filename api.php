<?php
define("OBJECT_DIR", __DIR__ . "/objects/");

function getUrl($path) {
  $proto = $_SERVER["HTTPS"] !== "off" ? "https" : "http";
  return $proto . "://" . $_SERVER["HTTP_HOST"] . $path;
}

function isValidOid($oid) {
  return preg_match('/^[a-f0-9]{64}$/', $oid);
}

header("Content-Type: application/vnd.git-lfs+json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  exit;
}

$request_body = file_get_contents("php://input");
$request_json = json_decode($request_body, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode(["message" => "Invalid JSON body"]);
  exit;
}

$response = ["objects" => []];

$operation = $request_json["operation"] ?? "download";

switch ($operation) {
  case "upload":
    foreach ($request_json["objects"] as $object) {
      $oid = $object["oid"];
      if (!isValidOid($oid)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid OID"]);
        exit;
      }
      $size = $object["size"];

      $response["objects"][] = [
        "oid" => $oid,
        "size" => $size,
        "actions" => [
          "upload" => [
            "href" => getUrl("/objects/" . $oid),
            "header" => [],
            "expires_in" => 86400,
          ]
        ]
      ];
    }
    break;
  case "download":
    foreach ($request_json["objects"] as $object) {
      $oid = $object["oid"];
      if (!isValidOid($oid)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid OID"]);
        exit;
      }
      $size = $object["size"];
      $file_path = OBJECT_DIR . $oid;

      if (file_exists($file_path) && filesize($file_path) === $size) {
        $response["objects"][] = [
          "oid" => $oid,
          "size" => $size,
          "actions" => [
            "download" => [
              "href" => getUrl("/objects/" . $oid),
              "header" => [],
              "expires_in" => 86400,
            ]
          ]
        ];
      } else {
        $response["objects"][] = [
          "oid" => $oid,
          "size" => $size,
          "error" => [
            "code" => 404,
            "message" => "Object not found"
          ]
        ];
      }
    }
    break;
  default:
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON body"]);
    exit;
}

echo json_encode($response);
