<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\PTK;
use App\Models\User;

class PTKPolicy
{
    /** Peran dengan hak penuh menghapus PTK apa pun. */
    private const SUPER_DELETE_ROLES = ['director', 'auditor'];
    /** Peran atasan (boleh hapus apa pun). */
    private const MANAGER_DELETE_ROLES = ['kabag_qc', 'manager_hr'];

    /**
     * Lihat daftar PTK (list page di-filter oleh scope visibleTo di query, jadi always true).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Lihat detail/preview PTK.
     * Konsisten: hanya boleh jika PTK masuk ke scope visibleTo(user).
     */
    public function view(User $user, PTK $ptk): bool
    {
        return PTK::visibleTo($user)->whereKey($ptk->id)->exists();
    }

    /**
     * Export / Preview (PDF/berkas).
     * Harus bisa "view" + punya izin aksi.
     */
    public function export(User $user, PTK $ptk): bool
    {
        return $user->can('ptk.export') && $this->view($user, $ptk);
    }

    /**
     * Download mengikuti aturan export (konsisten).
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
     * Update PTK: butuh izin & harus bisa melihat itemnya.
     */
    public function update(User $user, PTK $ptk): bool
    {
        // 1. Basic permission & visibility
        if (!($user->can('ptk.update') && $this->view($user, $ptk))) {
            return false;
        }

        // 2. Lock if submitted (only allow Not Started or In Progress)
        // Use loose check or array check. STATUS constants are available.
        return in_array($ptk->status, [PTK::STATUS_NOT_STARTED, PTK::STATUS_IN_PROGRESS]);
    }

    /**
     * Hapus PTK (soft/permanent).
     * - Director/Auditor/Kabag_QC/Manager_HR: selalu boleh.
     * - Lainnya: butuh permission ptk.delete dan harus bisa melihat itemnya (visibleTo).
     */
    public function delete(User $user, PTK $ptk): bool
    {
        if (
            $user->hasAnyRole(self::SUPER_DELETE_ROLES)
            || $user->hasAnyRole(self::MANAGER_DELETE_ROLES)
        ) {
            return true;
        }

        // Standard users cannot delete if submitted
        if (!in_array($ptk->status, [PTK::STATUS_NOT_STARTED, PTK::STATUS_IN_PROGRESS])) {
            return false;
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
}
