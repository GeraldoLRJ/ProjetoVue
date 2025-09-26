<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // Listar usuários (master: todos; admin: do próprio tenant)
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $q = User::query()->with('company');
        if ($user->role !== User::ROLE_MASTER) {
            $q->where('tenant_id', $user->tenant_id)
              ->where('role', '!=', User::ROLE_MASTER);
        }
        return $q->orderBy('id','desc')->paginate(20);
    }

    // Criar usuário
    public function store(Request $request)
    {
        $authUser = auth('api')->user();

        $rules = [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
            'password' => ['required','string','min:6'],
            'role' => ['nullable', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
            'tenant_id' => ['nullable','integer','exists:companies,id'],
        ];
        $data = $request->validate($rules);

        // Definir tenant de destino
        if ($authUser->role === User::ROLE_MASTER) {
            if (empty($data['tenant_id'])) {
                return response()->json(['message' => 'tenant_id é obrigatório para master'], 422);
            }
        } else {
            $data['tenant_id'] = $authUser->tenant_id;
            if (($data['role'] ?? User::ROLE_USER) === User::ROLE_MASTER) {
                return response()->json(['message' => 'admin não pode criar usuário master'], 403);
            }
        }

        $exists = User::where('tenant_id', $data['tenant_id'])
            ->where('email', $data['email'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'E-mail já utilizado neste tenant'], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? User::ROLE_USER,
            'tenant_id' => $data['tenant_id'],
        ]);

        return response()->json($user, 201);
    }

    // Mostrar um usuário (escopo por tenant)
    public function show(User $user)
    {
        $authUser = auth('api')->user();
        if ($authUser->role !== User::ROLE_MASTER) {
            if ($user->role === User::ROLE_MASTER || $user->tenant_id !== $authUser->tenant_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }
        $user->load('company');
        return $user;
    }

    // Atualizar usuário
    public function update(Request $request, User $user)
    {
        $authUser = auth('api')->user();
        if ($authUser->role !== User::ROLE_MASTER) {
            if ($user->role === User::ROLE_MASTER || $user->tenant_id !== $authUser->tenant_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $rules = [
            'name' => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','required','email','max:255'],
            'password' => ['sometimes','nullable','string','min:6'],
            'role' => ['sometimes', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ];
        $data = $request->validate($rules);

        if ($authUser->role !== User::ROLE_MASTER && ($data['role'] ?? null) === User::ROLE_MASTER) {
            return response()->json(['message' => 'admin não pode promover a master ou alterar o usuário master'], 401);
        }

        if (isset($data['email'])) {
            $exists = User::where('tenant_id', $user->tenant_id)
                ->where('email', $data['email'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'E-mail já utilizado neste tenant'], 422);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function destroy(User $user)
    {
        $authUser = auth('api')->user();
        if ($authUser->role !== User::ROLE_MASTER && $user->tenant_id !== $authUser->tenant_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($user->role === User::ROLE_MASTER) {
            return response()->json(['message' => 'Não é permitido excluir usuário master'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
