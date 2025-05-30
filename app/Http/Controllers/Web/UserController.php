<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->createWalletIfNotExists();
        $search = $request->input('search');
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'updated_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'name';
        }
        
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'asc';
        
        $query = User::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $query->orderBy($sortField, $sortDirection);
        
        $users = $query->paginate(10)->withQueryString();
        
        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
            'wallet' => $wallet,
        ]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'user_type' => ['required', Rule::in(['COMUM', 'LOJISTA'])],
            'wallet' => 'required|decimal:2|min:0',
        ]);
        
        User::create([
            'name' => $request->name,
            'cpf_cnpj' => $request->cpf_cnpj,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'wallet' => $request->wallet,
        ]);
        
        return redirect()->route('admin.users.index')
                         ->with('success', 'Usuário criado com sucesso!');
    }

    public function show(User $user)
    {
        $wallet = $user->createWalletIfNotExists();
        return view('users.show', [
            'user' => $user,
            'wallet' => $wallet,
        ]);
    }

    public function edit(User $user)
    {
        $wallet = $user->createWalletIfNotExists();
        return view('users.edit', [
            'user' => $user,
            'wallet' => $wallet,
        ]);
    }

   
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'user_type' => ['required', Rule::in(['COMUM', 'LOJISTA'])],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'wallet' => ['required', 'decimal:2', 'min:0'],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'user_type' => $validated['user_type'],
        ];
        
        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);
        

        return redirect()->route('admin.users.edit', $user->id)
                         ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        return redirect()->route('admin.users.index')
                         ->with('success', 'Usuário excluído com sucesso!');
    }
}
