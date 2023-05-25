<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    class Sms_smsir_v2 extends CI_Driver {

        private $ci;
        private $parent;
        private $parameters = [];
        private $template_id;

        function __construct() {
            $this->ci       =	& get_instance();
            $this->parent   =	$this->ci->sms;
        }

        function clear_parameters() {
            $this->parameters = [];
            return $this;
        }

        function add_parameter($parameter_name,$value) {
            $this->parameters[]  =   array('name' => $parameter_name,'value' => "$value");
            return $this;
        }

        function set_template_id($template_id) {
            $this->template_id = $template_id;
            return $this;
        }

        function prepare($template,$text = '') {
            return $template;
        }

        function send() {

            if (count($this->parameters) == 0) {
                $result =   $this->send_simple();
                return $result;
            }

            $token  =   $this->parent->token;

            $template   =   json_encode($this->parameters);
            $fields     =   '{
  "mobile": "'.$this->parent->mobile.'",
  "templateId": '.$this->template_id.',
  "parameters":
    '.$template.'
}';

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.sms.ir/v1/send/verify',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => $fields,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Accept: text/plain',
					'x-api-key: '.$token
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);
			$response = json_decode($response);

			if ($response->status != 1)
				$this->parent->error = $response->message;

			return $response->status == 1;

        }

        function credit() {

            $res = json_decode(@file_get_contents(APPPATH.'libraries/Sms/drivers/sms_credit'));

            if ($res && time() - $res->time < 30) {
                return $res->credit;
            }

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.sms.ir/v1/credit',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'X-API-KEY: '.$this->parent->token
				),
			));

			$response = json_decode(curl_exec($curl));

			curl_close($curl);

			if (@$response->status == 1) {
				file_put_contents(APPPATH.'libraries/Sms/drivers/sms_credit',json_encode(array('time' => time(),'credit' => $response->data)));
				return $response->data;
			} else {
				return FALSE;
			}


        }

        private function get_token_sms_ir() {

            $api_key    =   $this->parent->token;
            $secret_key =   $this->parent->secret_key;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ws.sms.ir/api/Token",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n\t\"UserApiKey\":\"$api_key\",\n\t\"SecretKey\":\"$secret_key\"\n}",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);

			$response = json_decode($response);

			if(is_object($response)) {
				$resultVars = get_object_vars($response);
				if(is_array($resultVars)) {
					@$IsSuccessful = $resultVars['IsSuccessful'];
					if($IsSuccessful == true) {
						@$TokenKey = $resultVars['TokenKey'];
						$resp = $TokenKey;
					} else {
						$resp = false;
					}
				}
			}

			return $response;

        }

        private function send_simple() {

            $token  =   $this->get_token_sms_ir();

            $data                   =   [];
            $data['Messages']       =   array($this->parent->text);
            $data['MobileNumbers']  =   array($this->parent->mobile);
            $data['LineNumber']     =   $this->parent->sender;
            $data['SendDateTime']   =   "";
            $data['CanContinueInCaseOfError']   =   "";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://RestfulSms.com/api/MessageSend",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Accept: */*",
                    "Accept-Encoding: gzip, deflate",
                    "Cache-Control: no-cache",
                    "Connection: keep-alive",
                    "Content-Type: application/json",
                    "Host: restfulsms.com",
                    "cache-control: no-cache",
                    "x-sms-ir-secure-token: $token"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $response   =   json_decode($response);

            if ($response == FALSE)
                return FALSE;
            else {
                if ($response->IsSuccessful == FALSE)
                    return FALSE;
                else
                    return TRUE;
            }

        }

    }