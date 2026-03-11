<?php

namespace App\Query\User;

use App\Models\User;
use App\Support\Query\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserIndexQuery
{
    public static function build(Builder $query, Request $request): Builder
    {
        self::applySearch($query, ListQuery::searchTerm($request));
        self::applyRoleFilter($query, $request->input('role'));
        self::applySelects($query);
        self::applySorting($query, $request);

        return $query;
    }

    protected static function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($search) {
            $searchQuery->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    protected static function applyRoleFilter(Builder $query, ?string $role): void
    {
        if (! $role) {
            return;
        }

        $query->whereHas('roles', function (Builder $roleQuery) use ($role) {
            $roleQuery->where('name', $role);
        });
    }

    protected static function applySelects(Builder $query): void
    {
        $primaryRoleSub = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->selectRaw('model_has_roles.model_id, MIN(roles.name) as primary_role_name')
            ->groupBy('model_has_roles.model_id');

        $query->leftJoin('users as leaders', 'leaders.id', '=', 'users.leader_id')
            ->leftJoinSub($primaryRoleSub, 'primary_roles', 'primary_roles.model_id', '=', 'users.id')
            ->select('users.*')
            ->addSelect('leaders.name as leader_name')
            ->addSelect('primary_roles.primary_role_name');
    }

    protected static function applySorting(Builder $query, Request $request): void
    {
        $sortMap = [
            '1' => 'users.name',
            '2' => 'users.email',
            '3' => 'primary_role_name',
            '4' => 'leader_name',
            '5' => 'users.created_at',
        ];

        ListQuery::applySort($query, $request, $sortMap, function (Builder $query) {
            $query->latest('created_at');
        });
    }
}
