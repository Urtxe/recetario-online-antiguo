<?php

require_once 'config/helpers.php';

class MaintenanceService {
    public function runHousekeeping() {
        $passwordResetsPruned = prune_password_reset_storage();
        $rateLimitsPruned = rate_limit_prune_db(30);

        security_log('maintenance_housekeeping', [
            'password_resets_pruned' => (bool)$passwordResetsPruned,
            'rate_limits_pruned' => (bool)$rateLimitsPruned
        ], 'info');

        return [
            'success' => true,
            'password_resets_pruned' => (bool)$passwordResetsPruned,
            'rate_limits_pruned' => (bool)$rateLimitsPruned
        ];
    }
}
