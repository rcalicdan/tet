<?php

namespace App\Libraries;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Rcalicdan\SmsApi\SmsApi as RcalicdanSmsApi;

class SmsApi extends RcalicdanSmsApi
{
    protected $config;

    protected $gateway;

    protected $response;

    protected $responseCode;

    public function sendMessage($to, $message, $extra_params = null, $extra_headers = [])
    {
        try {
            $this->initializeConfiguration();

            $mobile = $this->prepareMobileNumber($to);
            $payload = $this->buildPayload($mobile, $message, $extra_params);

            $headers = $this->prepareHeaders($extra_headers);

            $response = $this->sendHttpRequest($payload, $headers);

            $this->logRequestAndResponse($payload, $headers, $response);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }

        return $this;
    }

    /**
     * Initialize configuration and credentials.
     */
    private function initializeConfiguration()
    {
        if ($this->gateway == '') {
            $this->loadDefaultGateway();
        }
        $this->loadCredentialsFromConfig();
    }

    /**
     * Prepare the mobile number based on configuration.
     *
     * @param  string|array  $to
     * @return string|array
     */
    private function prepareMobileNumber($to)
    {
        $mobile = $this->config['add_code'] ? $this->addCountryCode($to) : $to;
        if (! (isset($this->config['json']) && $this->config['json'])) {
            if (is_array($mobile)) {
                $mobile = $this->composeBulkMobile($mobile);
            }
        } else {
            if (! is_array($mobile)) {
                $mobile = (isset($this->config['jsonToArray']) ? $this->config['jsonToArray'] : true) ? [$mobile] : $mobile;
            }
        }

        return $mobile;
    }

    /**
     * Build the payload for the request.
     *
     * @param  string|array  $mobile
     * @param  string  $message
     * @param  array|null  $extra_params
     * @return array
     */
    private function buildPayload($mobile, $message, $extra_params)
    {
        $params = $this->config['params']['others'];
        $send_to_param_name = $this->config['params']['send_to_param_name'];
        $msg_param_name = $this->config['params']['msg_param_name'];

        if (isset($this->config['wrapper'])) {
            $send_vars = [
                $send_to_param_name => $mobile,
                $msg_param_name => $message,
            ];
            if (isset($this->config['wrapperParams'])) {
                $send_vars = array_merge($send_vars, $this->config['wrapperParams']);
            }
            $payload = [$this->config['wrapper'] => [$send_vars]];
        } else {
            $params[$send_to_param_name] = $mobile;
            $params[$msg_param_name] = $message;
            $payload = $params;
        }

        if ($extra_params) {
            $payload = array_merge($payload, $extra_params);
        }

        return $payload;
    }

    /**
     * Prepare headers for the request.
     *
     * @param  array  $extra_headers
     * @return array
     */
    private function prepareHeaders($extra_headers)
    {
        $headers = isset($this->config['headers']) ? $this->config['headers'] : [];

        if (isset($this->config['auth_type']) && $this->config['auth_type'] === 'bearer') {
            $headers['Authorization'] = 'Bearer '.env('SMS_API_AUTH_TOKEN');
        } elseif (isset($this->config['auth_type']) && $this->config['auth_type'] === 'basic') {
            $headers['Authorization'] = 'Basic '.base64_encode(env('TWILIO_ACCOUNT_SID').':'.env('TWILIO_AUTH_TOKEN'));
        }

        if ($extra_headers) {
            $headers = array_merge($headers, $extra_headers);
        }

        return $headers;
    }

    /**
     * Send the HTTP request and return the response.
     *
     * @param  array  $payload
     * @param  array  $headers
     * @return mixed
     */
    private function sendHttpRequest($payload, $headers)
    {
        $request_method = isset($this->config['method']) ? $this->config['method'] : 'GET';
        $url = $this->config['url'];
        $request = new Request($request_method, $url);

        if ($request_method === 'GET') {
            $promise = $this->getClient()->sendAsync(
                $request,
                [
                    'query' => $payload,
                    'headers' => $headers,
                ]
            );
        } elseif ($request_method === 'POST') {
            $options = (isset($this->config['json']) && $this->config['json'])
                ? ['json' => $payload]
                : ['form_params' => $payload];
            $promise = $this->getClient()->sendAsync(
                $request,
                array_merge($options, ['headers' => $headers])
            );
        } else {
            throw new \InvalidArgumentException('Only GET and POST methods are allowed.');
        }

        $response = $promise->wait();
        $this->response = $response->getBody()->getContents();
        $this->responseCode = $response->getStatusCode();

        return $response;
    }

    /**
     * Log the request and response details.
     *
     * @param  array  $payload
     * @param  array  $headers
     * @param  mixed  $response
     */
    private function logRequestAndResponse($payload, $headers, $response)
    {
        Log::debug('SMS Gateway Request:', [
            'method' => isset($this->config['method']) ? $this->config['method'] : 'GET',
            'url' => $this->config['url'],
            'headers' => $headers,
            'payload' => $payload,
        ]);
        Log::debug('SMS Gateway Response:', [
            'status_code' => $this->responseCode,
            'body' => $this->response,
        ]);
    }

    /**
     * Handle exceptions during the HTTP request.
     */
    private function handleRequestException(RequestException $e)
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $this->response = $response->getBody()->getContents();
            $this->responseCode = $response->getStatusCode();
            Log::error('SMS Gateway Error:', [
                'status_code' => $this->responseCode,
                'body' => $this->response,
            ]);
        } else {
            Log::error('SMS Gateway Exception:', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
