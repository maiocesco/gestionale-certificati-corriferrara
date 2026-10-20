<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RetentionCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRetentionSettingRequest;
use App\Models\RetentionSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RetentionSettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RetentionSetting::class);

        $settings = RetentionSetting::all()->keyBy(fn (RetentionSetting $setting) => $setting->category->value);

        return view('admin.retention-settings.index', [
            'categories' => RetentionCategory::cases(),
            'settings' => $settings,
        ]);
    }

    public function update(UpdateRetentionSettingRequest $request, RetentionCategory $category): RedirectResponse
    {
        RetentionSetting::updateOrCreate(['category' => $category->value], $request->validated());

        return redirect()->route('admin.retention-settings.index')->with('status', 'retention-setting-updated');
    }
}
