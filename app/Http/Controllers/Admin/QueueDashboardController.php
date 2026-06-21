<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class QueueDashboardController extends Controller
{
    /** Show dashboard with pending/failed counts */
    public function index()
    {
        $pending = DB::table('jobs')->where('queue', 'cards')->count();
        $failed = DB::table('failed_jobs')->where('queue', 'cards')->count();
        return view('admin.queue_dashboard.index', compact('pending', 'failed'));
    }

    /** Clear pending cards jobs */
    public function clearPending(Request $request)
    {
        DB::table('jobs')->where('queue', 'cards')->delete();
        Log::info('Admin cleared pending cards jobs', ['admin_id' => $request->user('admin')->id ?? null]);
        return back()->with('status', 'All pending cards jobs have been cleared.');
    }

    /** Clear failed cards jobs */
    public function clearFailed(Request $request)
    {
        DB::table('failed_jobs')->where('queue', 'cards')->delete();
        Log::info('Admin cleared failed cards jobs', ['admin_id' => $request->user('admin')->id ?? null]);
        return back()->with('status', 'All failed cards jobs have been cleared.');
    }

    /** Start the cards queue worker in background (Windows) */
    public function startWorker(Request $request)
    {
        $cmd = 'php artisan queue:work --queue=cards --tries=3 --timeout=60 --sleep=3';
        // Detach process on Windows
        $full = "start \"\" /B $cmd";
        exec($full, $output, $returnVar);
        Log::info('Admin started cards queue worker', [
            'admin_id' => $request->user('admin')->id ?? null,
            'command' => $cmd,
            'return' => $returnVar,
        ]);
        return back()->with('status', 'Cards queue worker started in background.');
    }
}
?>
