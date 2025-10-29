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
    /** Admin per-departemen. */
    private const DEPT_ADMIN_ROLES     = ['admin_qc_flange', 'admin_qc_fitting', 'admin_hr', 'admin_k3'];

    /**
     * Lihat daftar PTK.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Lihat detail/preview PTK (dilonggarkan).
     * - PIC (pembuat) boleh lihat
     * - Selain itu: butuh izin ptk.view dan harus lolos sameDepartmentOrElevated()
     */
    public function view(User $user, PTK $ptk): bool
    {
        // pembuatnya/PIC boleh lihat
        if ((int) $user->id === (int) $ptk->pic_user_id) {
            return true;
        }

        // punya izin ptk.view dan cocok departemen (atau level atas)
        if ($user->can('ptk.view')) {
            return $this->sameDepartmentOrElevated($user, $ptk);
        }

        return false;
    }

    /**
     * Export / preview (PDF/berkas).
     * - PIC (pembuat) + punya ptk.export => boleh
     * - Selain itu: butuh ptk.export dan sameDepartmentOrElevated()
     */
    public function export(User $user, PTK $ptk): bool
    {
        if ((int) $user->id === (int) $ptk->pic_user_id && $user->can('ptk.export')) {
            return true;
        }

        return $user->can('ptk.export') && $this->sameDepartmentOrElevated($user, $ptk);
    }

    /**
     * Download mengikuti aturan export (supaya konsisten).
     */
    public function download(User $user, PTK $ptk): bool
    {
        return $this->export($user, $ptk);
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
            return (int) $ptk->created_by === (int) $user->id
                || (int) $ptk->department_id === (int) $user->department_id;
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
        if ($user->hasRole(['director', 'kabag_qc', 'manager_hr'])) {
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
        if ($user->hasRole(['director', 'kabag_qc', 'manager_hr'])) {
            return $ptk->status === 'Completed';
        }
        return false;
    }

    /**
     * Helper: cek apakah user boleh akses lintas departemen atau elevated.
     *
     * - Level atas: director, auditor, kabag_qc, manager_hr => bebas
     * - Admin departemen: sesuai permintaan, boleh lintas departemen (return true)
     *   Jika ingin membatasi ke departemen yang sama, ganti return true
     *   dengan: return (int) $user->department_id === (int) $ptk->department_id;
     */
    protected function sameDepartmentOrElevated(User $user, PTK $ptk): bool
    {
        if ($user->hasAnyRole(['director', 'auditor', 'kabag_qc', 'manager_hr'])) {
            return true;
        }

        if ($user->hasAnyRole(self::DEPT_ADMIN_ROLES)) {
            return true; // bebas lintas departemen (sesuai request)
        }

        // Default: tidak elevated dan bukan admin => tidak lolos
        return false;
    }
}
