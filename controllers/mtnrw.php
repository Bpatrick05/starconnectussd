<?php

class MTNRW extends Controller {

    function __construct() {
        parent::__construct();
    }

    function Index() {
        
        $xml_post = file_get_contents('php://input');
        if (empty($xml_post)) {
            echo 'welcome';
            die();
            $this->view->render('index');
        } else {
            $standard_array = $this->model->ParseRequest($xml_post);
            $standard_array['vendor'] = 'mtn_rw';
            if ($standard_array['requesttype'] == 'pull') {
                $response_xml = $this->model->Handler($xml_post, $standard_array);
                header('Content-Type: application/xml; charset=UTF-8');
                echo $response_xml;
            }
            if ($standard_array['requesttype'] == 'cleanup') {
                $this->model->DoSessionCleanUp($standard_array);
            }
        }
    }
}

?>