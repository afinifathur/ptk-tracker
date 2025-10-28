<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\PTK;
use App\Models\User;

class PTKPolicy
{
    /** Peran dengan hak penuh untuk menghapus PTK apa pun. */
    private const SUPER_DELETE_ROLES   = ['director', 'auditor'];
    /** Peran atasan dengan hak penuh menghapus. */
    private const MANAGER_DELETE_ROLES = ['kabag_qc', 'manager_hr'];
    /** Admin per-departemen: boleh hapus miliknya sendiri atau di departemennya. */
    private const DEPT_ADMIN_ROLES     = ['admin_qc_flange', 'admin_qc_fitting', 'admin_hr', 'admin_k3'];

    /**
     * Lihat daftar PTK.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Lihat detail/preview PTK.
     * - Atasan & auditor: bebas
     * - Admin departemen: hanya departemennya
     * - Default: creator atau PIC
     */
    public function view(User $user, PTK $ptk): bool
    {
        if ($user->hasRole(['director','auditor','kabag_qc','manager_hr'])) {
            return true;
        }

        if ($user->hasRole(['admin_qc_flange','admin_qc_fitting','admin_hr','admin_k3'])) {
            return (int)$ptk->department_id === (int)$user->department_id;
        }

        return (int)$ptk->created_by === (int)$user->id
            || (int)$ptk->pic_user_id === (int)$user->id;
    }

    /**
     * Download (PDF/berkas) mengikuti aturan view.
     */
    public function download(User $user, PTK $ptk): bool
    {
        return $this->view($user, $ptk);
    }

    /**
     * Membuat PTK.
     */
    public function create(User $user): bool
    {
        return $user->can('ptk.create');
    }

    /**
     * Update PTK: butuh izin dan harus lolos aturan view.
     */
    public function update(User $user, PTK $ptk): bool
    {
        return $user->can('ptk.update') && $this->view($user, $ptk);
    }

    /**
     * Hapus PTK (soft delete/permanent).
     */
    public function delete(User $user, PTK $ptk): bool
    {
        if ($user->hasRole(self::SUPER_DELETE_ROLES)) {
            return true;
        }

        if ($user->hasRole(self::MANAGER_DELETE_ROLES)) {
            return true;
        }

        if ($user->hasRole(self::DEPT_ADMIN_ROLES)) {
            return (int)$ptk->created_by === (int)$user->id
                || (int)$ptk->department_id === (int)$user->department_id;
        }

        return $user->can('ptk.delete') && $this->view($user, $ptk);
    }

    /**
     * Approve PTK:
     * - Hanya director/kabag_qc/manager_hr
     * - Hanya jika status 'Completed' & belum bernomor
     */
    public function approve(User $user, PTK $ptk): bool
    {
        if ($user->hasRole(['director','kabag_qc','manager_hr'])) {
            return $ptk->status === 'Completed' && empty($ptk->number);
        }
        return false;
    }

    /**
     * Reject PTK:
     * - Hanya director/kabag_qc/manager_hr
     * - Hanya jika status 'Completed'
     */
    public function reject(User $user, PTK $ptk): bool
    {
        if ($user->hasRole(['director','kabag_qc','manager_hr'])) {
            return $ptk->status === 'Completed';
        }
        return false;
    }
}
