<?php

class COREUSSD extends Savings {

    function __construct() {
        parent::__construct();
    }

    function ManageRequestSession($params) {
        //Check If Session Already Exists:
        $res = $this->GetSession($params);
        if (count($res) > 0) {
            return 1;
        } else {
            //Register Session On DB.
            $postData['session_date'] = date('Y-m-d G:i:s');
            $postData['session_id'] = $params['sessionId'];
            $postData['telephone_number'] = $params['msisdn'];
            $postData['session_execution_log_file'] = $params['execlogfile'];
            $this->db->InsertData("vnd_log_session_data", $postData);
            return 0;
        }
    }

    function DoSessionCleanUp($params) {
        //Clean up session
        $this->mod->SessionCleanUp($params);
    }

    function SaveLanguage($params, $lang) {
        //Check If Session Already Exists:
        $this->log->ExeLog($params, "COREUSSD::SaveLanguage language to save " . $lang, 2);
        //Register Session On DB.
        $postLN['session_language_pref'] = $lang;
        $this->db->UpdateData('vnd_log_session_data', $postLN, "session_id = {$params['sessionId']}");
    }

    function MenuOptionHandler($params, $status) {
        if ($status == 0) {
            $nxtmenu[0]['ussd_new_state'] = 1;
            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler Initial Session Call. Returned New State ..." . $nxtmenu[0]['ussd_new_state'], 2);
        } else {
            $state = $this->GetCurrentState($params);
            $nxtmenu = $this->ValidateUserInput($state, $params);
            $this->log->ExeLog($params, "COREUSSD::MenuOptionHandler GetNextState Result: Next Menu State Is " . $nxtmenu[0]['ussd_new_state'], 2);
        }
        $response = $this->DisplayMenu($status, $params, $nxtmenu);
        return $response;
    }

    function ValidateUserInput($state, $params) {
        if (is_numeric($params['subscriberInput'])) {
            $type = 'numeric';
        // } elseif (ctype_alnum($params['subscriberInput'])) {
        //     $type = 'alphanumeric';
        } else {
            $type = 'alphabetic';
        }
        
        if ($state[0]['input_type'] == $type) {
            if ($state[0]['state_type'] == 'input') {
                $choice = $this->HandleInputMenus($params, $state);
            } else {
                $choice = $params['subscriberInput'];
            }

            $this->log->ExeLog($params, "COREUSSD::ValidateUserInput Search For Option From " . $state[0]['current_state'] . ' When User Choice Is ' . $choice, 2);
            $nxtmenu = $this->GetNextState($state[0]['current_state'], $choice);
        } else {
            $this->log->ExeLog($params, "COREUSSD::ValidateUserInput Expected Type " . $state[0]['input_type'] . ' Received Type ' . $type, 2);
            $nxtmenu[0]['ussd_new_state'] = 999;
        }

        return $nxtmenu;
    }

    function HandleInputMenus($params, $state) {
        $this->StoreInputValues($params, $state[0]);
        if ($params['subscriberInput'] == '0') {
            $choice = $params['subscriberInput'];
        } else {
            $choice = '-1';
        }
        $this->log->ExeLog($params, "COREUSSD::HandleInputMenus Subscriber Input is :  " . $params['subscriberInput'] . " Thus Choice Carried Is " . $choice, 2);
        return $choice;
    }

    function DisplayMenu($status, $params, $fxn_array) {
        $menu = $this->GetFullStateByID($fxn_array[0]['ussd_new_state']);
        $this->log->ExeLog($params, "COREUSSD::DisplayMenu GetFullStateByID Returning Array for the menu " . var_export($menu, true), 2);
        $response_array = $this->PrepareMenu($status, $params, $menu);
        $response = $this->CreateXML($response_array);
        return $response;
    }

    function PrepareMenu($status, $params, $menu) {
        if ($menu[0]['fxn_call_flag'] == 1) {
            $this->log->ExeLog($params, "COREUSSD::DisplayMenu PrepareMenu Required to make remote function call to " . $menu[0]['call_fxn_name'], 2);
            $result = $this->{trim($menu[0]['call_fxn_name'])}($params);
            $this->log->ExeLog($params, "COREUSSD::Externalfunction call " . $menu[0]['call_fxn_name'] . ": Result " . var_export($result, true), 2);
            $responses = $this->GetFullStateByRCode($result['responsecode']);
            $this->log->ExeLog($params, "COREUSSD::GetFullStateByRCode call Get Response Message Call Result " . var_export($responses, true), 2);
            $this->OperationWatch($params, $responses[0]['state_id']);
        } else {
            $this->OperationWatch($params, $menu[0]['state_id']);
            $responses = $menu;
            $this->log->ExeLog($params, "COREUSSD::DisplayMenu PrepareMenu No Remote Function Call Required", 2);
        }

        $ln = $this->GetSessionLanguage($params);
        if ($ln[0]['session_language_pref'] == '') {
            $ln_text = 'text_en';
        } else {
            $ln_text = 'text_' . $ln[0]['session_language_pref'];
        }
        $this->log->ExeLog($params, "COREUSSD::DisplayMenu PrepareMenu Session Language Is " . $ln_text, 2);

        $prepared_response = $this->ReplacePlaceHolders($params, $responses[0][$ln_text], $result);
        $resp['state'] = $responses[0]['state_indicator'];
        $resp['msg_response'] = $prepared_response;

        $response = $this->MenuArray($params, $resp);
        return $response;
    }

    function MenuArray($params, $resp) {
        $response_array = array(
            'msisdn' => $params['msisdn'],
            'sessionid' => $params['sessionId'],
            'transactionid' => $params['transactionId'],
            'freeflow' => array(
                'freeflowState' => $resp['state']
            ),
            'applicationResponse' => $resp['msg_response'],
        );
        return $response_array;
    }

    function ReplacePlaceHolders($request, $text, $array) {
        $this->log->ExeLog($request, 'COREUSSD::ReplacePlaceHolders Fired for ' . $text . ' With Data '
                . var_export($array, true), 2);
        $new_text = str_replace("[LIMIT]", $array['borrowingmax'], $text);
        $new_text = str_replace("[RATE]", $array['interestrate'], $new_text);
        $new_text = str_replace("[AMOUNT]", $array['amount'], $new_text);
        $new_text = str_replace("[DURATION]", $array['duration'], $new_text);
        $new_text = str_replace("[PERIOD]", $array['duration'], $new_text);
        $new_text = str_replace("[INTEREST]", $array['interest'], $new_text);
        $new_text = str_replace("[NAME]", $array['membername'], $new_text);
        $new_text = str_replace("[NAMES]", $array['names'], $new_text);
        $new_text = str_replace("[MEMBER_NUMBER]", $array['member_number'], $new_text);
        $new_text = str_replace("[PHONE_NUMBER]", $array['phone_number'], $new_text);
        $new_text = str_replace("[NID]", $array['nid'], $new_text);
        $new_text = str_replace("[LOAN_BALANCE]", $array['outstandingloan'], $new_text);
        $new_text = str_replace("[ACCOUNT_BALANCE]", $array['accountbalance'], $new_text);
        $new_text = str_replace("[SHARE_VALUE]", $array['numberofshares'], $new_text);
        $new_text = str_replace("[AVAILABLE_BALANCE]", $array['availablebalance'], $new_text);
        $new_text = str_replace("[ACCOUNT_STATUS]", $array['accountstatus'], $new_text);
        $new_text = str_replace("[BORROW_AMOUNT]", $array['borrowingamount'], $new_text);
        $new_text = str_replace("[SAVE_AMOUNT]", $array['subscriberInput'], $new_text);
        $new_text = str_replace("[APPLIED_REQUESTS]", $array['appliedrequest'], $new_text);
        $new_text = str_replace("[WITHDRAW_AMOUNT]", $array['withdrawingamount'], $new_text);
        $new_text = str_replace("[UNPAID_FEES]", $array['unpaidfees'], $new_text);
        $new_text = str_replace("[STATEMENT]", $array['transactions'], $new_text);
        $new_text = str_replace("[GROUP_BALANCE]", $array['group_balance'], $new_text);
        $new_text = str_replace("[OPTIONS]", $array['options'], $new_text);
        $new_text = str_replace("[SF_LIST]", $array['options'], $new_text);
        $new_text = str_replace("[TYPE]", $array['type'], $new_text);
        $new_text = str_replace("[MAX_LOAN_AMOUNT]", $array['borrowingmax'], $new_text);
        $new_text = str_replace("[MSG]", $array['error_message'], $new_text);
        $new_text = str_replace("[TYPE]", $array['type'], $new_text);
        $new_text = str_replace("[LOAN_BAL]", $array['loanbalance'], $new_text);
        $new_text = str_replace("[REASON]", $array['options'], $new_text);
        $new_text = str_replace("[MEM_FINES_LIST]", $array['options'], $new_text);
        $new_text = str_replace("[savings]", $array['balances']['savings'], $new_text);
        $new_text = str_replace("[loans]", $array['balances']['loans'], $new_text);
        $new_text = str_replace("[deposits]", $array['balances']['deposits'], $new_text);
        $new_text = str_replace("[shares]", $array['balances']['shares'], $new_text);
        $new_text = str_replace("[socialfunds]", $array['balances']['socialfunds'], $new_text);
        $new_text = str_replace("[FINE]", $array['fine_reason'], $new_text);
        return $new_text;
    }

}
