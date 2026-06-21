<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Profile;
use App\Models\RouterSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserChallenge;
use App\Services\InstantDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class V2Controller extends Controller
{
    public function __construct(private InstantDeliveryService $instantDelivery) {}

    public function verifyTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reference' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_id' => 'nullable|exists:profiles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->instantDelivery->process($validator->validated());

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function networkStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'router_connected' => RouterSetting::current()->is_connected,
            'queue_size' => DB::table('jobs')->count(),
            'maintenance_mode' => SystemSetting::getValue('maintenance_mode') === 'true',
        ]);
    }

    public function appConfig(): JsonResponse
    {
        $profiles = Profile::active()
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_hours', 'speed_limit']);

        return response()->json([
            'success' => true,
            'maintenance_mode' => SystemSetting::getValue('maintenance_mode') === 'true',
            'point_price' => (float) SystemSetting::getValue('point_price_yri', '10'),
            'jeeb_wallet_phone' => SystemSetting::getValue('jeeb_wallet_phone', ''),
            'jeeb_wallet_full_name' => SystemSetting::getValue('jeeb_wallet_full_name', ''),
            'profiles' => $profiles,
            'currency' => 'YRI',
        ]);
    }

    public function challenges(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);
        $active = Challenge::active()->with(['conditions', 'rewards'])->get();
        $userProgress = UserChallenge::where('user_id', $user->id)->get()->keyBy('challenge_id');

        $result = $active->map(function ($challenge) use ($userProgress) {
            $progress = $userProgress->get($challenge->id);

            return [
                'id' => $challenge->id,
                'name' => $challenge->name,
                'description' => $challenge->description,
                'progress' => $progress?->progress_data ?? [],
                'conditions' => $challenge->conditions,
                'rewards' => $challenge->rewards,
                'completed' => ! is_null($progress?->completed_at),
                'claimed' => ! is_null($progress?->reward_claimed_at),
            ];
        });

        return response()->json([
            'success' => true,
            'active' => $result,
        ]);
    }
}
