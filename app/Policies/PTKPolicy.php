<?php

namespace App\Policies;

use App\Models\{User, PTK};
use App\Support\DeptScope;

class PTKPolicy
{
    /**
     * Menentukan apakah user dapat melihat daftar PTK.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah user dapat melihat detail PTK tertentu.
     * Cek apakah department_id PTK termasuk dalam daftar departemen yang diizinkan.
     */
    public function view(User $user, PTK $ptk): bool
    {
        $allowed = DeptScope::allowedDeptIds($user);
        return empty($allowed) || in_array($ptk->department_id, $allowed, true);
    }

    /**
     * Menentukan apakah user dapat membuat PTK baru.
     */
    public function create(User $user): bool
    {
        return $user->can('ptk.create');
    }

    /**
     * Menentukan apakah user dapat mengupdate PTK tertentu.
     * Hanya jika memiliki permission dan departemen sesuai.
     */
    public function update(User $user, PTK $ptk): bool
    {
        return $user->can('ptk.update') && $this->view($user, $ptk);
    }

    /**
     * Menentukan apakah user dapat menghapus PTK tertentu.
     * Hanya jika memiliki permission dan departemen sesuai.
     */
    public function delete(User $user, PTK $ptk): bool
    {
        return $user->can('ptk.delete') && $this->view($user, $ptk);
    }
}
