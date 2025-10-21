<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserCollecion;

class UserController extends Controller
{
    public function get(Request $request)
    {
        if ($request->user()->can('view own users')) {
            return new UserResource($request->user());
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function search(Request $request): UserCollecion
    {
        if ($request->user()->can('view users')) {
            $page = $request->query('page', 1);
            $size = $request->query('size', 10);
            $search = $request->query('search', '');

            $users = User::with('roles') // Eager load roles
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->paginate($size, ['*'], 'page', $page);

            return new UserCollecion($users);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(Request $request): UserResource
    {
        if ($request->user()->can('edit users')) {
            $user = $request->user();
            $user->update($request->only('name', 'email', 'password'));

            return new UserResource($user);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
