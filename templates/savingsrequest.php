<?php

$savingsrequest = '<?xml version="1.0" encoding="UTF-8"?>
<request type="'.$trans_data['method'].'">
    <authentication>
        <username>'.$trans_data['username'].'</username>
        <password>'.$trans_data['password'].'</password>
        <timestamp>'.$trans_data['timestamp'].'</timestamp>
    </authentication>
    <parameters>
        <method>'.$trans_data['method'].'</method>
        <session_id>'.$trans_data['session_id'].'</session_id>
        <phonenumber>'.$trans_data['msisdn'].'</phonenumber>
        <phoneaccount>'.$trans_data['phoneaccount'].'</phoneaccount>
        <firstname>'.$trans_data['firstname'].'</firstname>
        <lastname>'.$trans_data['lastname'].'</lastname>                
        <reason>'.$trans_data['reason'].'</reason>
        <amount>'.$trans_data['amount'].'</amount>
        <approvalvalue>'.$trans_data['approvalvalue'].'</approvalvalue>
        <appliedrequest>'.$trans_data['appliedrequest'].'</appliedrequest>
        <scode>'.$trans_data['scode'].'</scode>
        <confirmscode>'.$trans_data['confirmscode'].'</confirmscode>     
       <language>'.$trans_data['language'].'</language>                 
        <groupcode>'.$trans_data['groupcode'].'</groupcode>
        <reference>'.$trans_data['reference'].'</reference>        
    </parameters>
</request>';

