<?php

class Index extends Controller {

    function __construct() {
        parent::__construct();
    }

    function Index() {
        $xml_post = file_get_contents('php://input');
        if (empty($xml_post)) {
            $this->view->render('index');
        } else {
            $standard_array = $this->model->ParseRequest($xml_post);
            $response = $this->model->determineNetwork($xml_post, $standard_array);


            header('Content-Type:UTF-8');
            header('"Expires", "-1"');
            header('Expires: -1');
            header('Cache-Control: max-age=0');
            header('FreeFlow: FC');

            echo $response;
        }
    }

    function RunTest() {

        $params = array(
            'requesttype' => 'pull',
            'sessionId' => '1707245974780',
            'transactionId' => '0222715372687569132',
            'msisdn' => '250788920481',
            'newRequest' => '0',
            'flowState' => 'FE',
            'subscriberInput' => '1221',
            'vendor' => 'mtn_rw',
        );


        $res = $this->model->MenuOptionHandler($params, 1);
        print_r($res);
    }

}

?>
