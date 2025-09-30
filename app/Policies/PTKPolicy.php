<?php
namespace App\Policies;
use App\Models\{User, PTK};
class PTKPolicy {
    public function view(User $user, PTK $ptk){ return true; }
    public function update(User $user, PTK $ptk){ return true; }
    public function delete(User $user, PTK $ptk){ return true; }
    public function approve(User $user, PTK $ptk){ return true; }
    public function reject(User $user, PTK $ptk){ return true; }
}
