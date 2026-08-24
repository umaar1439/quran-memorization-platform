<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحات إدارة المستخدمين (Inertia). organization_id و role يُضبطان
 * صريحاً في Controller من سياق المسؤول الحالي — لا يُقبلان من العميل
 * مباشرة (منع Privilege Escalation، انظر User::$fillable و StoreUserRequest).
 */
class UserController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('role')) {
            $query->role($request->string('role')->toString());
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('search', 'role'),
            'availableRoles' => ['org-admin', 'supervisor', 'teacher', 'student', 'parent'],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        $user->assignRole($data['role']);

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
