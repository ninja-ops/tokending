<?php

/*

curl -s -H "token: footoken" https://domain.tld/tokending/
curl -s -X POST -F "token=footoken" "https://domain.tld/tokending/"
curl -s -X POST -F "token=${{ secrets.TOKEN }}" "https://domain.tld/tokending/"

--

config.ini.php

[main]
allowed_request_methods="GET,POST"
token_request_header="token"
token_request_var="token"

[footoken]
what="touch-file"
where="/tmp/footoken.txt"

*/

function _200($msg = "") {
  http_response_code(401);
  if ($msg != "") {
    echo $msg . "\n";
  }
  exit();
}

function _401($msg = "") {
  http_response_code(401);
  if ($msg != "") {
    echo $msg . "\n";
  }
  exit();
}

function _403($msg = "") {
  http_response_code(403);
  if ($msg != "") {
    echo $msg . "\n";
  }
  exit();
}

function _503($msg = "") {
  http_response_code(503);
  if ($msg != "") {
    echo $msg . "\n";
  }
  exit();
}

$config_file = dirname(__FILE__) . "/config.ini.php";

if (!file_exists($config_file)) {
  _503("configuration not found");
}

$tokens = parse_ini_file($config_file, true);
$config = $tokens["main"];
unset($tokens["main"]);

$allowed_via_request_method = false;
foreach(explode(",", $config["allowed_request_methods"]) as $method) {
  $method = trim($method);
  if ($_SERVER["REQUEST_METHOD"] == $method) {
    $allowed_via_request_method = true;
    break;
  }
}

if ($allowed_via_request_method == false) {
  _403("request method not allowed here");
}

$token = "";
foreach(explode(",", $config["token_request_var"]) as $varname) {
  $varname = trim($varname);
  if (isset($_REQUEST[$varname])) {
    $token = $_REQUEST[$varname];
    break;
  }
}

if ($token == "") {
  $headers = getallheaders();
  foreach($headers as $key=>$value) {
    $headers[strtolower($key)] = $value;
  }
  foreach(explode(",", $config["token_request_header"]) as $name) {
    $name = trim(strtolower($name));
    if (isset($headers[$name])) {
      $token = $headers[$name];
      break;
    }
  }
}

if ($token == "") {
  _401("no token omitted");
}

if (!isset($tokens[$token])) {
  _403("token not found");
}

switch($tokens[$token]["what"]) {
  case "touch-file":
    $fp = @fopen($tokens[$token]["where"], "w");
    if (!$fp) {
      _503("unable to write file");
    }
    fwrite($fp, serialize(array("time"=>time(), "_REQUEST"=>$_REQUEST, "_SERVER"=>$_SERVER)));
    fclose($fp);
    _200("OK");
    break;
  default:
    _503("unknown 'what'");
    break;
}

_503("undefined");
