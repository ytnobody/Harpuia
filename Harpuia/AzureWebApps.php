<?php
namespace Harpuia;
use \ApplicationInsights\Telemetry_Client;

class AzureWebApps {
    // user definable api key
    var $apikey;

    // token expire (in seconds)
    var $token_expire = 120;

    // begin time of processing a request
    var $begin;

    // for telemetry (Azure Application Insight)
    var $telemetry_client;

    // constructor
    function __construct () {
        $telemetry_key = $this->server_value('APPINSIGHTS_INSTRUMENTATIONKEY');
        if ($telemetry_key) {
            $begin            = microtime(TRUE); // microtime as float
            $telemetry_client = new \ApplicationInsights\Telemetry_Client();
            $telemetry_client->getContext()->setInstrumentationKey($telemetry_key);
        }
    }

    // public : fetch a http request parameter safely
    public function param (string $key) {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }

    // public : fetch a server value safely
    public function server_value (string $key) {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    // private : check existance of required parameters
    private function check_required (array $keylist = array()) {
        foreach ($keylist as $key) {
            if (!isset($_REQUEST[$key])) {
                throw new ParamRequiredException("parameter '$key' is required");
            }
        }
    }

    // public : define, check and report an error(when failuer) about existance of required parameters
    public function required (...$keylist) {
        try { 
            $this->check_required($keylist);
        } catch (Exception $err) {
            $this->res_error(400, "bad request", $err->getMessage());
        }
    }

    // private : validate http parameters that is matching to specified regex rules
    private function check_validate (array $rule = array()) {
        $valid = array();
        foreach ($rule as $key => $regex) {
            if (!isset($_REQUEST[$key])) {
                $valid[$key] = null;
                continue;
            }
            if (!preg_match($regex, $this->param($key))) {
                throw new ParamIsInvalidException("parameter '$key' is invalid");
            }
            $valid[$key] = $this->param($key);
        }
        return (object) $valid;
    }

    // public : define, check and report an error(when failuer) about validate http parameters
    public function validate (array $rule = array()) {
        $valid = null;
        try { 
            $valid = $this->check_validate($rule);
        } catch (Exception $err) {
            $this->res_error(400, "bad request", $err->getMessage());
        }
        return $valid;
    }

    // public : build and send a http response that includes json content
    public function res (array $content) {
        if (! isset($content['status'])) {
            $content['status'] = 200;
        }
        http_response_code($content['status']);
        header('Content-Type: application/json');
        $response_body = json_encode($content);
        echo $response_body;

        if ($this->telemetry_client) {
            $is_success  = $content['status'] >= 400 ? FALSE : TRUE;
            $duration    = microtime() - $this->begin;
            $request_url = $this->server_value('HTTP_HOST'). $this->server_value('REQUEST_URI');

            $this->telemetry_client->trackRequest(
                'HttpRequest', 
                $request_url, 
                time(), 
                strlen($response_body), 
                $content['status'], 
                $is_success, 
                NULL, 
                $duration
            );

            $this->telemetry_client->flush();
        }
        exit;
    }

    // public : build and send a http response that includes json content when something wrone is there
    public function res_error (int $status, string $message, $error = null) {
        $res = array(
            "status"  => $status,
            "message" => $message,
        );
        if ($error) {
            $res['error'] = $error;
        }
        $this->res($res);
    }

    // public : verify "HTTP_X_TOKEN" and "HTTP_X_TOKEN_TIME" header
    public function verify_token () {
        $path   = $this->server_value('SCRIPT_NAME');
        $x_time = $this->server_value('HTTP_X_TOKEN_TIME');
        $token  = $this->server_value('HTTP_X_TOKEN');

        if (!$x_time || !$token) {
            $this->res_error(403, "Forbidden", "Header Error");
        }

        if (time() - strtotime($x_time) > $this->token_expire) {
            $this->res_error(403, "Forbidden", "Token Expire");
        }

        $str_to_sign = $this->build_str_to_sign($path, $x_time);

        $sign_src = hash_hmac("sha256", $str_to_sign, $this->apikey, true);
        $sign = base64_encode($sign_src);

        if ($sign != $token) {
            $this->res_error(403, "Forbidden", "Authorization Failure");
        }
    }

    // private : build string for signature when verify a token
    private function build_str_to_sign ($path, $xtime) {
        return base64_encode(sprintf("Path: %s\nTime: %s\n", $path, $xtime));
    }
}

class Exception extends \Exception {}
class ParamRequiredException extends Exception {}
class ParamIsInvalidException extends Exception {}
