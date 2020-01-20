<?php

class AirtelRW extends Controller {

    function __construct() {
        parent::__construct();
    }

    function Index() {
   
        $xml_post = file_get_contents('php://input');
        if (empty($xml_post)) {
            $this->view->render('index');
        } else {
            $standard_array = $this->model->ParseRequest($xml_post);
            $standard_array['vendor'] = 'airtel_rw';
            if ($standard_array['requesttype'] == 'pull') {
            // if($standard_array['msisdn']=='250739196555'){           
                $response_xml = $this->model->Handler($xml_post, $standard_array);
                header('Content-Type: application/xml; charset=UTF-8');
                echo $response_xml;  
          /*  }else{
            
           $resp='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<response><msisdn>250739196555</msisdn><sessionid>14975328666155274</sessionid><transactionid>200420170615152106537420</transactionid><freeflow><freeflowState>FB</freeflowState></freeflow><applicationResponse>System is under Upgrade, Check other time</applicationResponse></response>';
          header('Content-Type: application/xml; charset=UTF-8');
                echo $resp;
            }  */               
            }
            if ($standard_array['requesttype'] == 'cleanup') {
                $this->model->SessionCleanUp($standard_array);
            }
        }
    }
}
