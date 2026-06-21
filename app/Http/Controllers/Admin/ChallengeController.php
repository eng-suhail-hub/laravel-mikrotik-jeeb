<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Challenge::withCount(['conditions', 'rewards'])->latest()->paginate(20);

        return view('admin.challenges.index', compact('challenges'));
    }

    public function create()
    {
        return view('admin.challenges.form', ['challenge' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'max_completions' => 'integer|min:0',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|json',
            'conditions.*.logic_group' => 'integer',
            'rewards' => 'required|array|min:1',
            'rewards.*.reward_type' => 'required|string',
            'rewards.*.value' => 'required|json',
            'rewards.*.priority' => 'integer',
        ]);

        $challenge = Challenge::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'max_completions' => $data['max_completions'] ?? 0,
        ]);

        foreach ($data['conditions'] as $cond) {
            $challenge->conditions()->create([
                'condition_type' => $cond['condition_type'],
                'operator' => $cond['operator'],
                'value' => json_decode($cond['value'], true),
                'logic_group' => $cond['logic_group'] ?? 0,
            ]);
        }

        foreach ($data['rewards'] as $reward) {
            $challenge->rewards()->create([
                'reward_type' => $reward['reward_type'],
                'value' => json_decode($reward['value'], true),
                'priority' => $reward['priority'] ?? 0,
            ]);
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'تم إنشاء التحدي بنجاح.');
    }

    public function edit(Challenge $challenge)
    {
        $challenge->load(['conditions', 'rewards']);

        return view('admin.challenges.form', compact('challenge'));
    }

    public function update(Request $request, Challenge $challenge)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'max_completions' => 'integer|min:0',
            'conditions' => 'required|array|min:1',
            'conditions.*.condition_type' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required|json',
            'conditions.*.logic_group' => 'integer',
            'rewards' => 'required|array|min:1',
            'rewards.*.reward_type' => 'required|string',
            'rewards.*.value' => 'required|json',
            'rewards.*.priority' => 'integer',
        ]);

        $challenge->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $request->boolean('is_active'),
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'max_completions' => $data['max_completions'] ?? 0,
        ]);

        $challenge->conditions()->delete();
        $challenge->rewards()->delete();

        foreach ($data['conditions'] as $cond) {
            $challenge->conditions()->create([
                'condition_type' => $cond['condition_type'],
                'operator' => $cond['operator'],
                'value' => json_decode($cond['value'], true),
                'logic_group' => $cond['logic_group'] ?? 0,
            ]);
        }

        foreach ($data['rewards'] as $reward) {
            $challenge->rewards()->create([
                'reward_type' => $reward['reward_type'],
                'value' => json_decode($reward['value'], true),
                'priority' => $reward['priority'] ?? 0,
            ]);
        }

        return redirect()->route('admin.challenges.index')
            ->with('success', 'تم تحديث التحدي بنجاح.');
    }

    public function destroy(Challenge $challenge)
    {
        $challenge->delete();

        return back()->with('success', 'تم حذف التحدي.');
    }
}
