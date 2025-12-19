<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\PTK;
use App\Models\User;

class PTKPolicy
{
    /** Peran dengan hak penuh menghapus PTK apa pun. */
    private const SUPER_DELETE_ROLES   = ['director', 'auditor'];

    /** Peran atasan (boleh hapus PTK non-locked). */
    private const MANAGER_DELETE_ROLES = ['kabag_qc', 'manager_hr'];

    // =========================================================
    // VIEW
    // =========================================================

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PTK $ptk): bool
    {
        return PTK::visibleTo($user)->whereKey($ptk->id)->exists();
    }

    // =========================================================
    // EXPORT / DOWNLOAD
    // =========================================================

    public function export(User $user, PTK $ptk): bool
    {
        return $user->can('ptk.export') && $this->view($user, $ptk);
    }

    public function download(User $user, PTK $ptk): bool
    {
        return $this->export($user, $ptk);
    }

    // =========================================================
    // CREATE
    // =========================================================

    public function create(User $user): bool
    {
        return $user->can('ptk.create');
    }

    // =========================================================
    // UPDATE (🔒 DIKUNCI BERDASARKAN STATUS)
    // =========================================================

    public function update(User $user, PTK $ptk): bool
    {
        // Tidak boleh update jika PTK sudah LOCKED
        if ($ptk->isLocked()) {
            return false;
        }

        return $user->can('ptk.update') && $this->view($user, $ptk);
    }

    // =========================================================
    // DELETE (🔒 DIKUNCI BERDASARKAN STATUS)
    // =========================================================

    public function delete(User $user, PTK $ptk): bool
    {
        // PTK LOCKED tidak boleh dihapus oleh siapa pun
        if ($ptk->isLocked()) {
            return false;
        }

        // Director & Auditor boleh hapus PTK non-locked
        if ($user->hasAnyRole(self::SUPER_DELETE_ROLES)) {
            return true;
        }

        // Kabag / Manager boleh hapus PTK non-locked
        if ($user->hasAnyRole(self::MANAGER_DELETE_ROLES)) {
            return true;
        }

        return $user->can('ptk.delete') && $this->view($user, $ptk);
    }

    // =========================================================
    // APPROVAL
    // =========================================================

    /**
     * Approve PTK:
     * - Stage 1: kabag_qc / manager_hr
     * - Stage 2: director
     */
    public function approve(User $user, PTK $ptk): bool
    {
        // Stage 1 approval
        if ($ptk->awaitingStage1() && $user->hasAnyRole(['kabag_qc', 'manager_hr'])) {
            return true;
        }

        // Stage 2 approval
        if ($ptk->awaitingStage2() && $user->hasRole('director')) {
            return true;
        }

        return false;
    }

    /**
     * Reject PTK:
     * - Stage 1 / 2 sesuai role
     */
    public function reject(User $user, PTK $ptk): bool
    {
        if ($ptk->awaitingStage1() && $user->hasAnyRole(['kabag_qc', 'manager_hr'])) {
            return true;
        }

        if ($ptk->awaitingStage2() && $user->hasRole('director')) {
            return true;
        }

        return false;
    }
}
