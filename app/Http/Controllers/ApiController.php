<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Library Management API",
 *     version="1.0.0",
 *     description="API documentation for the Library Management System",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Development Server"
 * )
 */
class ApiController extends Controller
{
    public $response = array('data' => null, 'message' => '', 'error' => '');
    public $status = 412;
    public $statusCode = [
        'success' => 200,
        'bad_request' => 400,
        'authorization_required' => 401,
        'payment_required' => 402,
        'forbidden' => 403,
        'not_found' => 404,
        'method_not_allowed' => 405,
        'not_acceptable' => 406,
        'proxy_authentication_required' => 407,
        'request_timeout' => 408,
        'conflict' => 409,
        'gone' => 410,
        'length_required' => 411,
        'precondition_failed' => 412,
        'request_entity_too_large' => 413,
        'request_URI_too_large' => 414,
        'unsupported_media_type' => 415,
        'request_range_not_satisfiable' => 416,
        'expectation_failed' => 417,
        'unprocessable_entity' => 422,
        'locked' => 423,
        'failed_dependency' => 424,
        'internal_server_error' => 500,
        'not_implemented' => 501,
        'bad_gateway' => 502,
        'service_unavailable' => 503,
        'gateway_timeout' => 504,
        'insufficient_storage' => 507,
        'unauthorised' => 401,
    ];

    public function ApiValidator($fields, $rules, $message = array())
    {
        $validator = Validator::make($fields, $rules, $message);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $r_message = '';
            $i = 1;
            foreach ($errors->messages() as $key => $message) {
                if ($i == 1) {
                    $r_message = $message[0];
                } else {
                    break;
                }
                $i++;
            }
            $this->response['message'] = $r_message;
            return false;
        }
        return true;
    }

    public function returnResponse()
    {
        return response()->json($this->response, $this->status);
    }
}
