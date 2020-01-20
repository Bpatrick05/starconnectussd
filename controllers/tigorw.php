<?php

class TIGORW extends Controller {

    function __construct() {        
        parent::__construct();
    }









    function Index() { //connection to router
        $xml_post = file_get_contents('php://input');
        if (empty($xml_post)) {
            $this->view->render('index');
        } else {
            $standard_array = $this->model->ParseRequest($xml_post);
            $standard_array['vendor'] = 'tigo_rw';
            if ($standard_array['requesttype'] == 'pull') {  
                $response_xml = $this->model->Handler($xml_post, $standard_array);
                header('Content-Type: application/xml; charset=UTF-8');
                echo $response_xml;
       
            }
            if ($standard_array['requesttype'] == 'cleanup') {
                $this->model->SessionCleanUp($standard_array);
            }
        }
    }



    /* function Index() {//direct connection to Telco
    //     echo "Sorry check here next week";die();
    
         $mytransdata = parse_url($_SERVER['REQUEST_URI']);
        $standard_array=$this->FormatRequestParameters($mytransdata['query']);
        $standard_array['subscriberInput']=$standard_array['input'];
        $standard_array['sessionId']=$standard_array['sessionid'];

            $standard_array['vendor'] = 'tigo_rw';
        $this->model->log->ExeLog($standard_array, 'TIGORW::Handler Function Call With Data Set ' . var_export($mytransdata, true), 1);              
            if ($standard_array['clean'] == 'clean-session') {
                $this->model->SessionCleanUp($standard_array);
            }else{

                $response_xml = $this->model->Handler($mytransdata['query'], $standard_array);
                header("HTTP/1.1 200 OK");
                header("Freeflow: ".$response_xml['freeflowState']);
                header("charge: N");
                header("cpRefId: 12345");
                header("Expires: -1");                
                header("Pragma: no-cache");                
                header("Cache-Control: max-age=0");
                header("Content-Type: UTF-8");
                header("Content-Length:". strlen($response_xml['applicationResponse']));  
                header_remove("Server");  
                header_remove("X-Powered-By");                             
                echo $response_xml['applicationResponse'];
                
           
       
            }
          
        
    }
    */



function FormatRequestParameters($query){	


   $request_r=str_replace('+',' ',$query);   
   $array = explode("&", $request_r);  
          foreach ($array as $key => $value) {
           $item = explode("=", $value); 
           $reqdata[$item[0]]=$item[1];
           // print_r($reqdata);die();
            }

return $reqdata;  
} 



}

?>