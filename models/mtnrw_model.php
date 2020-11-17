<?php

class MTNRW_Model extends COREUSSD {

    function __construct() {
        parent::__construct();
    }

    function ParseRequest($xml_post) {
        $standard_array = $this->stan->ParseXMLRequest($xml_post);
        return $standard_array;
    }

    function Handler($xml_post, $params) {
        $params['execlogfile'] = $this->log->ExeLog($params, 'MTNRW_Model::Handler Function Call With Data Set ' . var_export($params, true), 1);
        $this->log->LogXML($params['vendor'], 'ussdrequest', $params['requesttype'], $xml_post);
        $status = $this->ManageRequestSession($params);
        $this->log->ExeLog($params, 'MTNRW_Model::Handler ManageRequestSession Returning Status ' . $status, 2);
        $response = $this->MenuOptionHandler($params, $status);

        // convert back to string response.
        $newResponse = $this->MakeStringResponse($response);
        
        $this->log->ExeLog($params, 'MTNRW_Model::Handler Returning Response ' . $response, 3);
        $this->log->LogXML($params['vendor'], 'ussdresponse', $params['requesttype'], $response);
        return $newResponse;
    }
}
