<?php
define("PASSWD_FILE_PATH", __DIR__ . "/.lfs-server-passwd");

function verify_password(string $username, string $password): bool {
  if (!file_exists(PASSWD_FILE_PATH) || !is_readable(PASSWD_FILE_PATH)) {
    return false;
  }

  $fp = fopen(PASSWD_FILE_PATH, "r");

  while ($line = fgets($fp)) {
    list($file_username, $hashed_password) = explode(":", $line, 2);

    if ($file_username === $username) {
      if (password_verify($password, $hashed_password)) {
        fclose($fp);
        return true;
      }
      break;
    }
  }

  fclose($fp);
  return false;
}