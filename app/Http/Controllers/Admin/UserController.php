<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\ErrorHandler\Debug;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Users');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nik' => 'numeric|unique:users,nik',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'string|max:15',
            'address' => 'string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,svg',
            'role' => 'required',
            'role.*' => 'exists:roles,name',
        ], [
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()->first(),
                'status' => 'error',
            ]);
        }

        Debugbar::info($request->all());

        $photo = $request->file('photo');
        $photoPath = $photo->store('public/assets/users/photo');
        $photoPath = str_replace('public/', '', $photoPath);

        $user = User::create([
            'nik' => $request->nik,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'photo' => 'storage/' . $photoPath,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan',
            'status' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validation = Validator::make($request->all(), [
            'nik' => 'numeric|unique:users,nik,' . $user->id,
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'string|max:15',
            'address' => 'string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,svg',
            'role' => 'required',
            'role.*' => 'exists:roles,name',
        ], [
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()->first(),
                'status' => 'error',
            ]);
        }

        $photo = $request->file('photo');
        if ($photo) {
            $photoPath = $photo->store('public/assets/users/photo');
            $photoPath = 'storage/' . str_replace('public/', '', $photoPath);
        } else {
            $photoPath = $user->photo;
        }

        $user->update([
            'nik' => $request->nik,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'photo' => $photoPath,
        ]);

        $user->syncRoles($request->role);

        return response()->json([
            'message' => 'Pengguna berhasil diubah',
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus',
            'status' => 'success',
        ]);
    }

    /**
     * Get all users data.
     */
    public function userData(Request $request)
    {
        $query = User::query()->with('roles');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Handle sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        }

        // Handle pagination
        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($users->currentPage() - 1) * $users->perPage() + 1;
        $to = min($from + $users->count() - 1, $users->total());

        $transformedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nik' => $user->nik,
                'address' => $user->address,
                'photo' => $user->photo,
                'roles' => $user->roles->pluck('name')->implode(', '),
            ];
        });

        return response()->json([
            'data' => $transformedUsers,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
