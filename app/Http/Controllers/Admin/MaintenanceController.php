<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Services\MikroTikMaintenanceService;

class MaintenanceController extends Controller
{
    public function __construct(private MikroTikMaintenanceService $maintenance) {}

    public function index()
    {
        $logs = MaintenanceLog::with('admin')->latest()->paginate(20);

        return view('admin.maintenance.index', compact('logs'));
    }

    public function execute(string $action)
    {
        $valid = ['backup_db', 'clear_logs', 'rebuild_db'];
        if (! in_array($action, $valid)) {
            return back()->withErrors(['error' => 'إجراء غير معروف.']);
        }

        $log = $this->maintenance->execute($action, auth('admin')->user());
        $msg = $log->status === 'success' ? 'تم تنفيذ الإجراء بنجاح.' : 'فشل تنفيذ الإجراء.';

        return back()->with($log->status === 'success' ? 'success' : 'error', $msg);
    }
}
