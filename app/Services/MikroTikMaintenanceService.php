<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\Log;

class MikroTikMaintenanceService
{
    public function __construct(private MikroTikService $mikroTik) {}

    public function execute(string $action, Admin $admin): MaintenanceLog
    {
        try {
            $result = $this->mikroTik->executeMaintenance($action);

            return MaintenanceLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'status' => 'success',
                'raw_output' => $result['output'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Maintenance action failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return MaintenanceLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'status' => 'failed',
                'raw_output' => $e->getMessage(),
            ]);
        }
    }
}
