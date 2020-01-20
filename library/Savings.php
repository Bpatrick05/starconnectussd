<?php

class Savings extends Model {

    function __construct() {
        parent::__construct();
        $this->saving = new CoreSavings();
        $this->log = new Logs();
    }

    function getCategories($params){

        
        if($params['subscriberInput'] == 2){
            $lang = 'en';
        } else {
            $lang = 'kin';
        }
        $this->SaveLanguage($params,$lang);
        // print_r("##################### Savings => getCategories");
        // print_r($lang);
        // die();
        $response = $this->saving->getCategories($params);
        $this->log->ExeLog($params, 'Savings::getCategories Response ' . var_export($response, true), 2);
        $final_response = array();
        if($response['status'] == 200 && $response['message'] == 'success'){
            $final_response['responsecode'] = 500;
            $xml = null;
            $postData = array();
            $i = 1;
            foreach($response['categories'] as $key => $value){
                $postData[$i]['map_id'] = $i;
                $postData[$i]['reference_id'] = $value['id'];
                $postData[$i]['reference_text'] = $value['category_name'];
                $xml .= $i . ') ' . $value['category_name'] . PHP_EOL;

                $postData[$i]['phone_number'] = $params['msisdn'];
                $i++;
            }
            $this->StoreDateReferences($params, $postData);
            $final_response['options'] = $xml;
        } else {
            $final_response['responsecode'] = 501;
        }
        return $final_response;
    }

    function getCelebrities($params) {
        $categoryMap = $this->GetRequestReference($params['msisdn'], $params['subscriberInput']);
        $sessionparams['category_id'] = $categoryMap[0]['reference_id'];
        $finalparams = array_filter(array_merge($params, $sessionparams));
        $response = $this->saving->getCelebrities($finalparams);
        $this->log->ExeLog($params, 'Savings::getCelebrities Response ' . var_export($response, true), 2);
        $final_response = array();
        if($response['status'] == 200 && $response['message'] == 'success'){
            $final_response['responsecode'] = 502;
            $xml= null;
            $postData = array();
            $i = 1;
            foreach($response['celebrities'] as $key => $value){
                $postData[$i]['map_id'] = $i;
                $postData[$i]['reference_id'] = $value['id'];
                $postData[$i]['reference_text'] = $value['names'];
                $xml .= $i . ') ' . $value['names'] . PHP_EOL;

                $postData[$i]['phone_number'] = $params['msisdn'];
                $i++;
            }
            $this->StoreDateReferences($params, $postData);
            $final_response['options'] = $xml;
        }else {
            $final_response['responsecode'] = 501;
        }
        return $final_response;
    }

    function getInfo($params) {
        $categoryMap = $this->GetRequestReference($params['msisdn'], $params['subscriberInput']);
        $sessionparams['celebrity_id'] = $categoryMap[0]['reference_id'];
        $finalparams = array_filter(array_merge($params, $sessionparams));
        $response = $this->saving->getInfo($finalparams);
        $this->log->ExeLog($params, 'Savings::getInfo Response ' . var_export($response, true), 2);
        $final_response = array();
        if($response['status'] == 200 && $response['message'] == 'success'){
            $final_response['responsecode'] = 504;
            $final_response['names'] = $response['celebrity'][0]['names'];
            $final_response['phone_number'] = $response['celebrity'][0]['phone_number'];
        }else {
            $final_response['responsecode'] = 505;
        }
        return $final_response;
    }

    function PrepareRequestParameters($params, $paramsarray) {
        foreach ($paramsarray as $value) {
            $find = $this->getInputValue($params, $value);
            $res = $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id='" . $find . "' ");
            $key = $this->stan->MatchOne($value);
            $paramvalues[$key] = $res[0]['input_value'];
        }
        return $paramvalues;
    }

    function GetDataMapperByMsisdn($params) {
        $xml = null;
        $res = $this->db->SelectData("SELECT * FROM data_mapper WHERE 
                phone_number=:sid", array('sid' => $params['msisdn']));
        foreach ($res as $key => $value) {
            $xml .= $value['map_id'] . ') ' . $value['reference_text'] . PHP_EOL;
        }
        return $xml;
    }

    function getInputValue($params, $inputvalue) {
        $res = $this->db->SelectData("SELECT MAX(record_id) as record_id FROM vnd_log_session_input_values WHERE session_id=:sid AND input_name=:ipn", array('sid' => $params['sessionId'], 'ipn' => $inputvalue));
        return $res[0]['record_id'];
    }

    function GetRequestReference($msisdn, $map_id) {
        $res = $this->db->SelectData("SELECT * FROM data_mapper WHERE map_id='" . $map_id . "' 
                AND phone_number=:msisdn", array('msisdn' => $msisdn));
        return $res;
    }

    function ClearRequestReference($id) {
        $this->db->DeleteData('data_mapper', "id = {$id}");
    }
    

    function ProcessGetAddedDetails($params) {
        //Get All Data From The DB
        $names = $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id='" . $this->getInputValue($params, 'member_name') . "' ");
        $phone = $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id='" . $this->getInputValue($params, 'phone_number') . "' ");
        $nid = $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id='" . $this->getInputValue($params, 'national_id') . "' ");
        $final_response = array();
        $final_response['membername'] = $names[0]['input_value'];
        $final_response['phone_number'] = $phone[0]['input_value'];
        $final_response['nid'] = $nid[0]['input_value'];
        //print_r($final_response); die();
        $final_response['responsecode'] = 756;
        return $final_response;
    }

    function ProcessLanguageChangeRequest($params) {
        $lang = $this->db->SelectData("SELECT * FROM vnd_log_session_input_values WHERE record_id='" . $this->getInputValue($params, 'newlanguage') . "' ");
        $final_response = array();
        if ($lang[0]['input_value'] == 1) {
            $params['language'] = 'en';
        } else if ($lang[0]['input_value'] == 2) {
            $params['language'] = 'kin';
        } else {
            $final_response['responsecode'] = 739;
            return $final_response;
        }
        $response = $this->saving->CompleteLanguageChangeRequest($params);
        if (isset($response['status_code']) && $response['status_code'] == 200) {
            $this->SetUserLanguage($params, $response['language']);
            $final_response['responsecode'] = 784;
        } else {
            $final_response['responsecode'] = 743;
        }
        return $final_response;
    }

    

    function SaveDataReference($params, $array) {
        $xml = null;
        $postData = array();
        $i = 1;
        foreach ($array as $key => $value) {
            $postData[$i]['request_date'] = date("Y-m-d G:i:s");
            $postData[$i]['map_id'] = $i;
            if (isset($value['reason_id'])) {
                $postData[$i]['reference_id'] = $value['reason_id'];
                $postData[$i]['reference_text'] = $value['reason'];
                $xml .= $i . ') ' . $value['reason'] . PHP_EOL;
            } else {
                $postData[$i]['reference_id'] = $value['product_id'];
                $postData[$i]['reference_text'] = $value['product_name'];
                $xml .= $i . ') ' . $value['product_name'] . PHP_EOL;
            }
            $postData[$i]['phone_number'] = $params['msisdn'];
            $i++;
        }
        $this->StoreDateReferences($params, $postData);
        return $xml;
    }

    function StoreDateReferences($params, $array) {
        $sth = $this->db->prepare("DELETE FROM data_mapper where phone_number='" . $params['msisdn'] . "'");
        $sth->execute();
        foreach ($array as $key => $value) {
            $last = $this->db->InsertData("data_mapper", $value);
            $this->log->ExeLog($params, "Savings::StoreDateReferences last value " . $last, 2);
        }
    }

    function SetUserLanguage($params, $language) {
        $postLN['session_language_pref'] = $language;
        $this->db->UpdateData('vnd_log_session_data', $postLN, "session_id = {$params['sessionId']}");
    }

}

?>