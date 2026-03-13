<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Query\User\UserIndexQuery;
use App\Services\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,name');
        UserIndexQuery::build($query, $request);

        $users = $query->paginate(10)->withQueryString();
        $roles = Role::orderBy('name')->pluck('name');
        $leaders = User::role([RoleEnum::LEADER->value, RoleEnum::ADMIN->value])->orderBy('name')->get(['id', 'name']);

        return view('users.index', compact('users', 'roles', 'leaders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return redirect()->route('users.index')->with('success', "User {$user->name} created successfully.");
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()->back()->with('success', "User {$user->name} updated successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $error = $this->userService->deleteUser($user);
        if ($error) {
            return $this->redirectWithFlashToPrevious(
                $request,
                route('users.index'),
                'warning',
                $error
            );
        }

        return $this->redirectWithFlashToPrevious(
            $request,
            route('users.index'),
            'success',
            "User {$user->name} deleted successfully."
        );
    }
}
