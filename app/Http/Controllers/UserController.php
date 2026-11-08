<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserAction;
use App\Actions\DeleteUserAction;
use App\Actions\UpdateUserAction;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Queries\UserDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحات إدارة المستخدمين (Inertia). organization_id و role يُضبطان
 * صريحاً في Controller من سياق المسؤول الحالي — لا يُقبلان من العميل
 * مباشرة (منع Privilege Escalation، انظر User::$fillable و StoreUserRequest).
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserDirectoryQuery $users,
        private readonly CreateUserAction $createUser,
        private readonly UpdateUserAction $updateUser,
        private readonly DeleteUserAction $deleteUser,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => $this->users->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('role')->toString() ?: null,
            ),
            'filters' => $request->only('search', 'role'),
            'availableRoles' => UserDirectoryQuery::ROLES,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->createUser->execute($request->validated(), $request->user());

        return back()->with('success', __('messages.users.created'));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->updateUser->execute($user, $request->validated());

        return back()->with('success', __('messages.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->deleteUser->execute($user);

        return back()->with('success', __('messages.users.deleted'));
    }
}
