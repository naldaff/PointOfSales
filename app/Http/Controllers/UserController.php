<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\User;
use Spatie\Permission\Models\Permission;
use DB;

class UserController extends Controller
{
    public function index(){
    	$users = User::orderBy('created_at', 'ASC')->paginate(10);
    	return view('users.index', compact('users'));
    }

    public function create(){
    	$role = Role::orderBy('created_at', 'ASC')->get();
    	return view('users.create', compact('role'));
    }

    public function store(Request $request){
    	$this->validate($request, [
    		'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|string|exists:roles,name'
    	]);

    	$user = User::firstOrCreate([
    		'email' => $request->email
    	], [
    		'name' => $request->name,
    		'password' => bcrypt($request->password),
    		'status' => true
    	]);

    	$user->assignRole($request->role);
    	return redirect(route('users.index'))->with(['success' => 'User : <strong>' . $user->name . '</strong> Ditambahkan']);
    	
    	//dd($user->status);
    }

    public function edit($id){
    	$user = User::findOrFail($id);
    	return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id){
    	$this->validate($request, [
    		'name' => 'required|string|max:100',
    		'email' => 'required|email|exists:users,email',
    		'password' => 'nullable|min:6'
    	]);

    	$user = User::findOrFail($id);
    	$password = !empty($request->password) ? bcrypt($request->password):$user->password;
    	$user->update([
    		'name' => $request->name,
    		'password' => $password
    	]);
    	return redirect(route('users.index'))->with(['success' => 'User : <strong>' . $user->name . '</strong> Diperbarui']);
    }

    public function destroy($id){
    	$users = User::findOrFail($id);
    	$users->delete();
    	return redirect()->back()->with(['success' => 'User : <strong>' . $users->name . '</strong> Dihapus']);
    }

    public function RolePermission(Request $request){
    	$role = $request->get('role');

    	//set dua buah variable dengan nilai null
    	$permissions = null;
    	$hasPermission = null;

    	//mengambil data role
    	$roles = Role::all()->pluck('name');

    	//apabila parameter role terpenuhi
    	if(!empty($role)){
    		//select role berdasarkan name nya, ini sejenis dengan method find()
    		$getRole = Role::findByName($role);

    		//query untuk mengambil permission yang telah dimili oleh role terkait
    		$hasPermission = DB::table('role_has_permissions')
    			->select('permissions.name')
    			->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
    			->where('role_id', $getRole->id)->get()->pluck('name')->all();

    		//mengambil data permission 
    		$permissions = Permission::all()->pluck('name');
    	}
    	return view('users.role_permission', compact('roles', 'permissions', 'hasPermission'));
    }

    public function addPermission(Request $request){
    	$this->validate($request, [
    		'name' => 'required|string|unique:permissions'
    	]);

    	$permission = Permission::firstOrCreate([
    		'name' => $request->name
    	]);

    	return redirect()->back();
    }

    public function setRolePermission(Request $request, $role){
    	//select role berdasarkan namenya
    	$role = Role::findByName($role);

    	//fungsi syncPermission akan menghapus semua permission yg dimiliki role tersebut
    	//kemudian diassign kembali sehingga tidak terjadi duplikat data
    	$role->syncPermissions($request->permission);
    	return redirect()->back()->with(['success' => 'Permission to Role Saved!']);
    }

    public function roles(Request $request, $id)
	{
	    $user = User::findOrFail($id);
	    $roles = Role::all()->pluck('name');
	    return view('users.roles', compact('user', 'roles'));
	}

    public function setRole(Request $request, $id){
    	$this->validate($request, [
    		'role' => 'required'
    	]);

    	$user = User::findOrFail($id);
    	$user->syncRoles($request->role);
    	return redirect()->back()->with(['success' => 'Role sudah diset!']);
    }
}
