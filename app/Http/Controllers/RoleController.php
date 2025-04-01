<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get list of roles",
     *     description="Retrieve a paginated list of roles with role ID, name, and slug.",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Roles retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Admin"),
     *                         @OA\Property(property="slug", type="string", example="admin")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=412, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $page = $request->page ? $request->page : 1;
            $limit = $request->limit ? $request->limit : 10;
            $roles = Role::select("*");
            $total = $roles->count();
            if (isset($page) && isset($limit) && $page > 0 && $limit >= 0) {
                $offset = ($page - 1) * $limit;
                $roles->offset($offset)->limit($limit);
            }
            $roles = $roles->get();
            $this->status = $this->statusCode["success"];
            $this->response["message"] = "Roles get sucessfully.";
            $this->response["data"]["roles"] = RoleResource::collection($roles);
            $this->response["data"]["total"] = $total;
            return response()->json($this->response, $this->status);
        } catch (Exception $exception) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong.";
            $this->response["error"] = $exception->getMessage();
            return response()->json($this->response, $this->status);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Create a new role",
     *     description="Adds a new role to the system with a unique name and generates a slug.",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Role created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="slug", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=412, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:roles,name'
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $data = Role::create([
                    'name' => $request->name,
                    'slug' => Str::slug($request->name),
                ]);
                $details = [
                    "id" => $data->id,
                    "name" => $data->name,
                    "slug" => $data->slug
                ];
                $this->status = $this->statusCode["success"];
                $this->response["data"] = $details;
                $this->response["message"] = "Role created sucessfully";
            } catch (Exception $e) {
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
     *     path="/api/roles/{id}",
     *     summary="Get details of a role",
     *     description="Fetch the details of a specific role based on its ID, including role name and slug.",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Role ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role details fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Role details fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="slug", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show(string $id)
    {
        try {
            $role = Role::where('id', $id)->first();
            if ($role) {
                $details = [
                    "id" => $role->id,
                    "name" => $role->name,
                    "slug" => $role->slug
                ];
                $this->status = $this->statusCode["success"];
                $this->response["data"] = $details;
                $this->response["message"] = "Role details fetch sucessfully";
            } else {
                $this->status = $this->statusCode["not_found"];
                $this->response["data"] = [];
                $this->response["message"] = "No any role found with this id, please try again with vaid id.";
            }
        } catch (Exception $e) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong in authentication";
            // $this->response["error"] = $e->getMessage();
        }
        return response()->json($this->response, $this->status);
    }


    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Update a role",
     *     description="Update the name and slug of a specific role based on its ID.",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Role ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Editor", description="Name of the role")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Editor"),
     *                 @OA\Property(property="slug", type="string", example="editor")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=412, description="Validation error"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'name' => 'required|unique:roles,name,' . $id
        ];
        if ($this->apiValidator($request->all(), $rules)) {
            try {
                $role = Role::where('id', $id)->first();
                if ($role) {
                    $role->name = $request->name;
                    $role->slug = Str::slug($request->name);
                    $role->save();

                    $details = [
                        "id" => $id,
                        "name" => $role->name,
                        "slug" => $role->slug
                    ];
                    $this->status = $this->statusCode["success"];
                    $this->response["data"] = $details;
                    $this->response["message"] = "Role updated sucessfully";
                } else {
                    $this->status = $this->statusCode["not_found"];
                    $this->response["data"] = [];
                    $this->response["message"] = "No any role found with this id, please try again with vaid id.";
                }
            } catch (Exception $e) {
                $this->status = $this->statusCode["internal_server_error"];
                $this->response["message"] = "Something went wrong";
                // $this->response["error"] = $e->getMessage();
            }
        }
        return response()->json($this->response, $this->status);
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete a role",
     *     description="Delete a specific role by its ID. If there are users associated with the role, deletion will be prevented.",
     *     tags={"Authentication"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Role ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Role deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found"
     *     ),
     *     @OA\Response(
     *         response=412,
     *         description="Precondition failed (Users associated with this role)"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $check = Role::where('id', $id)->first();
            if ($check) {
                $checkUserCount = User::where("role_id", $id)->count();
                if ($checkUserCount > 0) {
                    $this->status = $this->statusCode["precondition_failed"];
                    $this->response["data"] = [];
                    $this->response["message"] = "One or more users are associated with this role, Please first delete this users & come again.";
                } else {
                    $check->delete();
                    $this->status = $this->statusCode["success"];
                    $this->response["message"] = "Role deleted sucessfully";
                }
            } else {
                $this->status = $this->statusCode["not_found"];
                $this->response["data"] = [];
                $this->response["message"] = "No any role found with this id, please try again with vaid id.";
            }
        } catch (Exception $e) {
            $this->status = $this->statusCode["internal_server_error"];
            $this->response["message"] = "Something went wrong";
            // $this->response["error"] = $e->getMessage();
        }
        return response()->json($this->response, $this->status);
    }
}
