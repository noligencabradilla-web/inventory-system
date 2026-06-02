<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SPOffices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;


class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::select('users.*', 's_p_offices.office as office_name','s_p_offices.id as s_p_office_id')
            ->join('s_p_offices', 'users.s_p_office_id', '=', 's_p_offices.id')
            ->whereRole('client')
            ->orderBy('users.created_at', 'desc')
            ->get();
        $spOffices = SPOffices::select('id',"office")->whereIsActive(true)->orderBy('created_at','desc')->get();
        return view('admin.users.index', compact('users', 'spOffices'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
            'role' => 'required|in:admin,client',
            's_p_office_id' => 'nullable|exists:s_p_offices,id',
        ]);
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            's_p_office_id' => $validated['s_p_office_id'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'New account created successfully.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'subaccount') {
            abort(403, 'Unauthorized');
        }
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'subaccount') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'required|in:admin,client',
            's_p_office_id' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'subaccount') {
            abort(403, 'Unauthorized');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
