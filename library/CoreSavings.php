<?php

class CoreSavings {

    function __construct() {
        $this->mod = new Model();
        $this->log = new Logs();
        $this->stan = new Standardizer();
    }

    function getPacks($params) {
        $req = array(
            'linked_msisdn' => $params['msisdn'],
        );
        $params['endpoint'] = 'packs';
        $params['reques'] = 'get';
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

    function getCategories($params) {
        $req = array(
            'linked_msisdn' => $params['msisdn'],
        );
        $params['endpoint'] = 'categories';
        $params['request'] = 'get';
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

    function getCelebrities($params){
        $req = array(
            'linked_msisdn' => $params['msisdn'],
        );
        $params['endpoint'] = 'celebrities/getCelebritiesByCategory/'. $params['category_id'];
        $params['request'] = 'get';
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

    function getInfo($params) {
        $req = array(
            'linked_msisdn' => $params['msisdn'],
        );
        $params['endpoint'] = 'celebrities/getCelebrityByid/'. $params['celebrity_id'];
        $params['request'] = 'get';
        $response = $this->CompleteRequest($params, $req, 1);
        return $response;
    }

    

    /*     * *************************************************************************
     * 
     * Authentication & Transaction Request & Response Processing Functions
     * 
     * ************************************************************************* */

    function CompleteRequest($params, $request = false, $resp = false) {
        $this->log->ExeLog($params, 'CoreSavings::CompleteRequest Fired For ' . $request['method'] . ' With Data ' . var_export($request, true), 2);
        $this->log->ExeLog($params, 'CoreSavings:: Test params content'. var_export($params, true), 2);
        $auth_array = $this->TimeStampPWEnc(SAVE_CONNECT_PASS);
        $request['username'] = $params['vendor'];
        $request['password'] = $auth_array['enc_pw'];
        $request['timestamp'] = $auth_array['ts'];
        //  $request_xml = $this->WriteGeneralXMLFile($params, 'savingsrequest', 'savingsrequest', $request);
        $request_xml = json_encode($request);
        $URL_CONNECT = SAVE_CONNECT . $params['endpoint'];
        
        $this->log->ExeLog($params, 'CoreSavings::CompleteRequest Preparing to send Request ' . $request_xml . ' To ' . $URL_CONNECT, 2);
        $token = $this->mod->getToken($params['msisdn']);
        $params['token'] = $token[0]['token'];
        if ($params['request'] == 'post') {
            $result = $this->SendJSONByCURL($URL_CONNECT, $params, $request_xml);
        } else {
            $result = $this->SendGetByCURL($URL_CONNECT, $params);
        }
        $this->log->ExeLog($params, 'CoreSavings::CompleteRequest SendByCURL Response XML ' . $result, 2);
        
        $response = $this->ParseRequest($params, $result);
        $this->log->ExeLog($params, 'CoreSavings::CompleteRequest Response Array ' . var_export($response, true), 2);
        return $response;
    }

    function TimeStampPWEnc($pw) {
        $ts = date('YmdGis');
        $data['ts'] = $ts;
        $data['enc_pw'] = hash_hmac('MD5', $pw, $ts);
        return $data;
    }

    function WriteGeneralXMLFile($request, $type, $temp, $trans_data) {
        $f_template = $temp;
        $template = 'templates/' . $type . '.php';
        require($template);
        $trans_xml = ${$f_template};
        $file_name = $this->log->LogXML($request['vendor'], $type, $type, $trans_xml);
        $this->log->ExeLog($request, 'CoreSavings::WriteGeneralXMLFile File For ' . $request['requesttype'] . ' Saved Under ' . $file_name, 2);
        return $trans_xml;
    }

    function SendGetByCURL($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '. $params['token']
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $content = curl_exec($ch);
        return $content;
    }

    function SendJSONByCURL($url, $params, $xml) {
        $this->log->ExeLog($params, 'CoreSavings::SendJSONByCURL Sending ' . $xml . ' To ' . $url, 2);
        $cont_len = strlen($xml);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'cache-control: no-cache', 
            'Content-Length: ' . $cont_len,
            'Authorization: Bearer '. $params['token']
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($ch);
        if (!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            $log = 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
        } else {
            $log = 'Curl error: ' . curl_error($ch);
        }
        $this->log->ExeLog($params, 'CoreSavings::SendJSONByCURL Returning ' . $log, 2);

        $this->log->ExeLog($params, 'CoreSavings::SendJSONByCURL response content ' . var_export($content, true), 2);
        return $content;
    }

    function ParseRequest($request, $xml) {
        $this->log->ExeLog($request, 'CoreSavings::ParseRequest Fired for ' . $xml, 2);
        try {
            //$standard_array = $this->stan->ParseXMLRequest($xml,$level = false, $source = false, $serv_id = false,$request);
            //  $standard_array = $this->stan->ParseXMLRequest($xml,$request);
            $array = json_decode($xml, true);
            //$standard_array = $this->stan->Standardize($array,$params);
            return $array;
        } catch (Exception $ex) {
            $this->log->ExeLog($request, 'CoreSavings::ParseRequest unable to parse XML. Throwing Exception ' . $ex, 2);
        }
    }

}
