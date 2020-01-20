<?php

class Model {

    function __construct() {
        $this->log = new Logs();
        $this->db = new Database();
        $this->stan = new Standardizer();
        Session::init();
    }

    function GetSession($params) {
        return $this->db->SelectData("SELECT * FROM vnd_log_session_data WHERE session_status='active'
                AND session_id =:ssn AND telephone_number=:tn", array('ssn' => $params['sessionId'], 'tn' => $params['msisdn']));
    }

    function GetCurrentState($params) {
        return $this->db->SelectData("SELECT * FROM vnd_log_current_state c JOIN vnd_ussd_states s
                ON c.current_state=s.state_id WHERE session_id=:sid", array('sid' => $params['sessionId']));
    }

    function GetSessionLanguage($params) {
        return $this->db->SelectData("SELECT * FROM vnd_log_session_data WHERE session_id=:sid", array('sid' => $params['sessionId']));
    }

    function GetNextState($cs, $pc) {
        return $this->db->SelectData("SELECT * FROM vnd_ussd_choices WHERE ussd_state=:cs AND ussd_choice=:pc", array('cs' => $cs, 'pc' => $pc));
    }
    
    function GetFullStateByRCode($rcode) {
        return $this->db->SelectData("SELECT * FROM vnd_ussd_states s LEFT OUTER JOIN vnd_ussd_states_text t 
                ON s.state_id=t.state_id WHERE t.code=:id", array('id' => $rcode));
    }
    
    function GetFullStateByID($state) {
        return $this->db->SelectData("SELECT * FROM vnd_ussd_states s LEFT OUTER JOIN vnd_ussd_states_text t 
                ON s.state_id=t.state_id WHERE s.state_id=:id", array('id' => $state));
    }
    
    function GetInputValues($id){
        return $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id=:id", array('id'=>$id));
    }

    function OperationWatch($params, $stateid = false) {
        $this->log->ExeLog($params, "Model::OperationWatch called for state ID: ".$stateid." With Params: " . var_export($postCS, true), 2);
        //Check If this is the first Request
        $res = $this->db->SelectData("SELECT * FROM vnd_log_current_state WHERE session_id=:sid AND telephone_number=:tn", array('sid' => $params['sessionId'], 'tn' => $params['msisdn']));
        $this->SetCurrentState($res, $params, $stateid);
        $this->LogPickedOptions($res, $params);
    }

    function SetCurrentState($res, $params, $stateid = false) {
        $records = count($res);
        $postCS = array();
        if ($records == 0) {
            $postCS['session_id'] = $params['sessionId'];
            $postCS['telephone_number'] = $params['msisdn'];
            $postCS['current_state'] = 1;
            $this->log->ExeLog($params, "Model::SetCurrentState Initial State Setting With Data" . var_export($postCS, true), 2);
            $this->db->InsertData('vnd_log_current_state', $postCS);
        } else {
            $this->log->ExeLog($params, "Model::SetCurrentState ref data" . var_export($res, true), 2);
            $postCS['current_state'] = $stateid;
            $this->log->ExeLog($params, "Model::SetCurrentState Updating Session Record ".$res[0]['record_id']." State with Data " . var_export($postCS, true), 2);
            $this->db->UpdateData('vnd_log_current_state', $postCS, "record_id = {$res[0]['record_id']}");
        }
    }

    function SetLanguagePref($params) {
        if ($params['subscriberInput'] == '1') {
            $postLN['session_language_pref'] = 'en';
        } elseif ($params['subscriberInput'] == '2') {
            $postLN['session_language_pref'] = 'kin';
        }
        $this->db->UpdateData('vnd_log_session_data', $postLN, "session_id = {$params['sessionId']}");
    }

    function LogPickedOptions($res, $params) {
        $records = count($res);
        $postLog['request_time'] = date('Y-m-d G:i:s');
        $postLog['transaction_id'] = $params['transactionId'];
        $postLog['session_id'] = $params['sessionId'];
        $postLog['telephone_number'] = $params['msisdn'];

        if ($records == 0) {
            //This is the initial request.
            $postData['menu_requests'] = $params['subscriberInput'];
            $this->db->InsertData('vnd_session_activity_log', $postData);
        } else {
            $requeststring = $res[0]['menu_requests'] . ',' . $params['subscriberInput'];
            $postData['menu_requests'] = $requeststring;
            $this->db->UpdateData('vnd_session_activity_log', $postData, "record_id = {$res[0]['record_id']}");
        }
    }

    function SessionCleanUp($params) {
        $res = $this->db->SelectData("SELECT * FROM vnd_log_session_data WHERE telephone_number=:tn AND session_id=:sid", array('tn' => $params['msisdn'], 'sid' => $params['sessionId']));
        $postCS['session_status'] = 'closed';
        $postCS['session_close_date'] = date('Y-m-d G:i:s');
        $this->db->UpdateData('vnd_log_session_data', $postCS, "record_id = {$res[0]['record_id']}");
    }

    function StoreInputValues($params, $curr_state) {
        $this->log->ExeLog($params, "Model::StoreInputValues Called With Data " . var_export($params, true) . ' From Current State ' . var_export($curr_state, true), 2);
        $postData = array(
            'date' => date('Y-m-d G:i:s'),
            'session_id' => $params['sessionId'],
            'state_id' => $curr_state['current_state'],
            'telephone_number' => $params['msisdn'],
            'input_name' => $curr_state['input_field_name'],
            'input_value' => $params['subscriberInput']
        );
        $this->log->ExeLog($params, "Model::StoreInputValues preparing to post " . var_export($postData, true), 2);
        $this->db->InsertData("vnd_log_session_input_values", $postData);
    }

    function CreateXML($array) {
        // create simpleXML object
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><response></response>");
        $this->ArrayToXML($array, $xml);
        return $xml->asXML();
    }

    function ArrayToXML($array, &$xml) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    $this->ArrayToXML($value, $subnode);
                } else {
                    $this->ArrayToXML($value, $xml);
                }
            } else {
                $xml->addChild("$key", "$value");
            }
        }
    }

    function getToken($msisdn){
        $res = $this->db->SelectData('SELECT * FROM sm_access_token WHERE telephone_number=:msisdn', array('msisdn' => $msisdn));
        return $res;
    }

}

?>
