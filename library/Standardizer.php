<?php

class Standardizer {

    function __construct() {
           $this->log = new Logs();  
           $this->db = new Database();     
    }

    public $_match_up_array = array(
        'type' => 'requesttype',
        'username' => 'username',
        'password' => 'password',
        'timestamp' => 'timestamp',
        'msisdn' => 'msisdn',
        'sessionId' => 'sessionId',
        'amount' => 'amount',
        'language' => 'language',
        'newRequest' => 'newRequest',
        'subscriberInput' => 'subscriberInput',
        'transactionId' => 'transactionId',
        'code'=>'resultcode',
        'response_code' => 'responsecode',
        'status' => 'requeststatus',
        'max_amount_allowed'=>'borrowingmax',
        'loan_balance'=>'outstandingloan',
        'account_balance'=>'accountbalance',
        'number_of_shares'=>'numberofshares',
        'available_balance'=>'availablebalance',
        'account_status'=>'accountstatus',
        'amount_to_borow'=>'borrowingamount',
        'interest_rate'=>'interestrate',
        'amount_to_withdraw'=>'withdrawingamount',
        'member_name'=>'membername',
        'applied_socialfund'=>'appliedsocialfund',
        'transactions'=>'transactions',
        'applied_loans'=>'appliedloans',
        'unpaid_fees'=>'unpaidfees',
        'requests_applied'=>'appliedrequest',  
        'group_balance'=>'group_balance',
        'flowState'=>'flowState',
        'freeflowState' => 'freeFlow',
        'loanamount'=>'amount',
        'loan_duration'=>'duration',
        'interest_option'=>'interest',
        'loan_req_pin'=>'pin',
        'groupcode'=>'group_code',
        'loan_approval_value'=>'request_id', 
        'loan_app_decision'=>'approval_value', 
        'loan_app_pin'=>'pin',
        'social_approval_value'=>'request_id', 
        'socal_app_decision'=>'approval_value',
        'social_app_pin'=>'pin',
        'fineamount'=>'amount',
        'finevalue'=>'fine_value',
        'user_pin'=>'pin',
        'loan_request_id'=>'request_id',
        'admin_pin'=>'pin',
        'fine_id'=>'request_id',
        'applicationResponse' => 'applicationResponse'
    );
    

    function ParseXMLFromURL($url) {
        $xmlp = simplexml_load_file($url);
        $p_array = $this->ObjectToArray($xmlp);
        return $p_array;
    }

   // function ParseXMLRequest($xml_post, $level = false, $source = false, $serv_id = false, $array = false) {
    function ParseXMLRequest($xml_post, $level = false) {   
        if ($level) {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($xml_post);
            libxml_clear_errors();
            $xmln = $doc->saveXML($doc->documentElement);
        } else {
            $xmln = $xml_post;
        }
        $xmlp = simplexml_load_string($xmln);
        $p_array = $this->ObjectToArray($xmlp); 
        $request_array = $this->ArrayFlattener($p_array);
        $standard_array = $this->Standardize($request_array);
        return $standard_array;
    }

    function Standardize($data_array) {
        //Convert to Single
        $result_array = array();
        foreach ($data_array as $key => $value) {
            $standard_key = $this->_match_up_array[$key];
            if (!empty($standard_key)) {
                $result_array[$standard_key] = $value;
            }
        }
        return $result_array;
    }
    
    function MatchOne($key){
        return $this->_match_up_array[$key];
    }

    function ObjectToArray($obj) {
        if (!is_array($obj) && !is_object($obj))
            return $obj;
        if (is_object($obj))
            $obj = get_object_vars($obj);
        return array_map(__METHOD__, $obj);
    }

    function ArrayFlattener($array) {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->ArrayFlattener($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    

    

}
