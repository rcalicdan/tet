<?php

return [
    'country_code' => '48', 
    'default' => env('SMS_API_DEFAULT_GATEWAY', 'gateway_name_basic'),
  
    'gateway_name_basic' => [
        'method' => 'GET', 
        'url' => 'BaseUrl', 
        'params' => [
            'send_to_param_name' => '',
            'msg_param_name' => '',
            'others' => [
                'param1' => '',
                'param2' => '',
                'param3' => '',
            ],
        ],
        'headers' => [
            'header1' => '',
            'header2' => '',
        ],
        'add_code' => true,
    ],

    'gateway_name_advanced' => [
        'method' => 'POST', 
        'url' => 'BaseUrl', 
        'params' => [
            'send_to_param_name' => '',
            'msg_param_name' => '',
            'others' => [
                'param1' => '',
                'param2' => '',
                'param3' => '',
            ],
        ],
        'headers' => [
            'header1' => '',
            'header2' => '',
        ],
        'json' => true, 
        'add_code' => true, 
    ],

    'smsnix' => [
        'url' => 'http://bulk.smsnix.in/vendorsms/pushsms.aspx?',
        'params' => [
            'send_to_param_name' => 'msisdn', 
            'msg_param_name' => 'msg',
            'others' => [
                'user' => '',
                'password' => '', 
                'sid' => '', 
                'fl' => '0',
                'gwid' => '2',
            ],
        ],
        'add_code' => true, 
    ],

    'msg91' => [
        'method' => 'POST',
        'url' => 'https://control.msg91.com/api/v2/sendsms?', 
        'params' => [
            'send_to_param_name' => 'to', 
            'msg_param_name' => 'message',
            'others' => [
                'authkey' => '',
                'sender' => '',
                'route' => '4',
                'country' => '91',
            ],
        ],
        'json' => true, 
        'wrapper' => 'sms', 
        'add_code' => false, 
    ],

    'smsapi_linkmobility' => [
        'method' => 'POST',
        'url' => 'https://api.smsapi.pl/sms.do',
        'params' => [
            'send_to_param_name' => 'to',
            'msg_param_name' => 'message',
            'others' => [
                'from' => env('SMS_API_FROM_NUMBER'),
                'format' => 'json',
                'encoding' => 'utf-8',
                'test' => env('APP_ENV') === 'production' ? '0' : '1', // Set to '0' for production
            ],
        ],
        'headers' => [
            'Authorization' => 'Bearer '.env('SMS_API_AUTH_TOKEN'),
            'Content-Type' => 'application/json',
        ],
        'json' => true, 
        'add_code' => false, 
    ],

    'twilio' => [
        'method' => 'POST',
        'url' => 'https://api.twilio.com/2010-04-01/Accounts/'.env('TWILIO_ACCOUNT_SID').'/Messages.json',
        'params' => [
            'send_to_param_name' => 'To',
            'msg_param_name' => 'Body', 
            'others' => [
                'From' => env('TWILIO_FROM_NUMBER'),
            ],
        ],
        'headers' => [
            'Authorization' => 'Basic '.base64_encode(env('TWILIO_ACCOUNT_SID').':'.env('TWILIO_AUTH_TOKEN')),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'json' => false, 
        'add_code' => false,
    ],

];
