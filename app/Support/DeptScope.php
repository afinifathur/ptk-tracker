<?php
namespace App\Support;

use App\Models\User;

class DeptScope {
  public static function allowedDeptIds(User $user): array {
    // director/auditor: semua
    if ($user->hasAnyRole(['director','auditor'])) {
      return []; // empty => tanpa filter
    }
    $ids = [];
    if ($user->department_id) $ids[] = $user->department_id;
    // izin tambahan via permission view-dept-{id}
    foreach ($user->getAllPermissions()->pluck('name') as $p) {
      if (str_starts_with($p,'view-dept-')) {
        $ids[] = (int)str_replace('view-dept-','',$p);
      }
    }
    return array_values(array_unique($ids));
  }
}
