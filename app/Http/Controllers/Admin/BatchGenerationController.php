<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMikrotikCardBatchJob;
use App\Models\BatchGeneration;
use App\Models\Profile;
use Illuminate\Http\Request;

class BatchGenerationController extends Controller
{
    public function index()
    {
        $batches = BatchGeneration::with(['admin', 'profile'])->latest()->paginate(20);
        $profiles = Profile::active()->get();

        return view('admin.batch_generations.index', compact('batches', 'profiles'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:profiles,id',
            'quantity' => 'required|integer|min:1|max:1000',
            'credential_mode' => 'nullable|in:match,separate',
            'username_length' => 'nullable|integer|min:6|max:32',
            'password_length' => 'nullable|integer|min:6|max:32',
            'username_prefix' => 'nullable|string|max:10',
        ]);

        $batch = BatchGeneration::create([
            'admin_id' => auth('admin')->id(),
            'profile_id' => $data['profile_id'],
            'quantity' => $data['quantity'],
            'generation_config' => [
                'credential_mode' => $data['credential_mode'] ?? 'match',
                'username_length' => (int) ($data['username_length'] ?? 10),
                'password_length' => (int) ($data['password_length'] ?? 10),
                'username_prefix' => $data['username_prefix'] ?? '',
            ],
        ]);

        GenerateMikrotikCardBatchJob::dispatch($batch->id)->onQueue('cards');

        return back()->with('success', "تم إرسال مهمة توليد {$data['quantity']} بطاقة إلى قائمة الانتظار.");
    }

    public function progress(int $id)
    {
        $batch = BatchGeneration::findOrFail($id);

        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'generated' => $batch->generated_count,
            'total' => $batch->quantity,
        ]);
    }
}
