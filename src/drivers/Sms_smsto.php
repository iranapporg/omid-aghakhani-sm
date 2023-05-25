<?php
defined('BASEPATH') OR exit('No direct script access allowed');

    class Sms_smsto extends CI_Driver {

        private $ci;
        private $parent;

        function __construct() {
            $this->ci       =   & get_instance();
            $this->parent   =   $this->ci->sms;
        }

        function send() {

            $from_number    =   $this->parent->sender;
            $token          =   $this->parent->token;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.sms.to/sms/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "message": "'.$this->parent->text.'",
                    "to": "'.$this->parent->mobile.'",
                    "bypass_optout": true,
                    "sender_id": "SMSto",
                    "callback_url": "'.base_url().'"
                }',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $token",
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            if (!json_decode($response))
                return FALSE;
            else {
                $response = json_decode($response);
                if ($response->success == TRUE)
                    return TRUE;
                else {
                    $this->parent->error = $response->message;
                    return FALSE;
                }
            }

        }

        /**
         * this method is unusable
         * @return $this
         */
        function add_parameter($key,$value) {
            return $this;
        }

        function credit() {

            $curl = curl_init();
            $token = $this->parent->token;

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://auth.sms.to/api/balance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $token",
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            if (json_decode($response))
                return json_decode($response)->balance;
            else
                return FALSE;

        }

    }