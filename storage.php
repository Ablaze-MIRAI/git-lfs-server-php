<?php
define("OBJECT_DIR", __DIR__ . "/objects/");

if (!preg_match('#/objects/([a-f0-9]{64})$#', $_SERVER["REQUEST_URI"], $match)) {
  http_response_code(400);
  echo "Invalid OID";
  exit;
}

$oid = $match[1];
$file_path = OBJECT_DIR . $oid;

switch ($_SERVER["REQUEST_METHOD"]) {
  case "PUT":
    $input_stream = fopen("php://input", "rb");
    $output_stream = fopen($file_path, "wb");
    if (!$input_stream || !$output_stream) {
      http_response_code(500);
      exit;
    }

    stream_copy_to_stream($input_stream, $output_stream);

    fclose($input_stream);
    fclose($output_stream);

    if (hash_file("sha256", $file_path) !== $oid) {
      unlink($file_path);
      http_response_code(422);
      exit;
    }

    http_response_code(200);

    break;
  case "GET":
    if (file_exists($file_path)) {
      header("Content-Type: application/octet-stream");
      header("Content-Length: " . filesize($file_path));
      header('Content-Disposition: attachment; filename="' . $oid . '"');
      readfile($file_path);
    } else {
      http_response_code(404);
      echo "Object not found";
    }
    break;
  default:
    http_response_code(405);
    break;
}
