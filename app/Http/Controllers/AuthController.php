<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth"
 * )
 */

class AuthController extends ApiController
{

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Registers a user with name, email, and password.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User registered successfully"),
     *     @OA\Response(response=412, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function register(Request $request)
    {
        $path_info = explode('/', $request->route()->uri());
        $user_role_type = $path_info[1];
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role_id' => Role::where('slug', $user_role_type)->first()->id
                ]);

                $user_details = [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => $user_role_type
                ];

                // AFTER REGISTER WE NEED TO DIRECT REDIRECT TO HOME PAGE THEN WE GENERATE TOKEN AND RETURN TOKEN WITH USER DETAILS
                /*$token = JWTAuth::fromUser($user);
                return $this->respondWithToken($token, $user_details, true);*/

                // ONLY REGISTER USER NO NEED TO GENERATE TOKEN
                $this->status = $this->statusCode["success"];
                $this->response["message"] = "User registered sucessfully";
                $this->response["data"]["user"] = $user_details;
                return response()->json($this->response, $this->status);

            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong";
                $this->response["error"] = $e->getMessage();
                return response()->json($this->response, $this->status);
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User login",
     *     description="Logs in a user and returns a JWT token.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];

        if ($this->apiValidator($request->all(), $rules)) {
            $credentials = request(['email', 'password']);
            try {
                if (!$token = auth("api")->attempt($credentials)) {
                    $this->status = $this->statusCode["authorization_required"];
                    $this->response["message"] = trans("api.login_fail");
                    $this->response["error"] = "Unauthorized";
                    return response()->json($this->response, $this->status);
                }
                $user = auth("api")->user();
                $user_details = [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => !empty($user->user_role) ? $user->user_role->name : null
                ];

                return $this->respondWithToken($token, $user_details);
            } catch (JWTException $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong in authentication";
                $this->response["error"] = $e->getMessage();
                return response()->json($this->response, $this->status);
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Get authenticated user",
     *     description="Returns the currently authenticated user's details.",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User details retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me()
    {
        $user = auth("api")->user();
        $user_details = [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "role" => $user->user_role->name
        ];
        $this->status = $this->statusCode["success"];
        $this->response["message"] = "Details get sucessfully";
        $this->response["data"]["user"] = $user_details;
        return response()->json($this->response, $this->status);
    }


    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Refresh JWT Token",
     *     description="Generates a new token for authenticated users.",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refreshed successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function refresh()
    {
        $token = auth("api")->refresh();
        $this->status = $this->statusCode["success"];
        $this->response["message"] = "Details received successfully";
        $this->response["data"]["token"] = "bearer " . $token;
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     description="Logs out the currently authenticated user.",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User logged out successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout()
    {
        auth('api')->logout();
        $this->status = $this->statusCode["success"];
        $this->response["message"] = "Successfully logged out";
        return response()->json($this->response, $this->status);
    }

    /**
     * Generate JWT token response.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user_details, $is_register = false)
    {
        $this->status = $this->statusCode["success"];
        $this->response["message"] = $is_register ? "User registered sucessfully" : "User login sucessfully";
        $this->response["data"]["user"] = $user_details;
        $this->response["data"]["token"] = "bearer " . $token;
        $this->response["data"]["expires_in"] = auth("api")->factory()->getTTL() * 60;
        return response()->json($this->response, $this->status);
    }
}
