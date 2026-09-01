<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Currency;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingController extends Controller
{
    protected AuditService $auditService;
    protected FileUploadService $fileUploadService;

    public function __construct(AuditService $auditService, FileUploadService $fileUploadService)
    {
        $this->auditService = $auditService;
        $this->fileUploadService = $fileUploadService;
        $this->middleware('require.admin');
    }

    private const ALLOWED_KEYS = [
        'site_title',
        'site_logo',
        'base_currency_id',
        'maintenance_mode',
        'api_maintenance_mode',
        'maintenance_title',
        'maintenance_content',
        'wallet_feature_enabled',
        'wallet_payment_enabled',
        'wallet_topup_enabled',
    ];

    private const SETTINGS_GROUP = [
        'site_title' => 'branding',
        'site_logo' => 'branding',
        'base_currency_id' => 'currency',
        'maintenance_mode' => 'maintenance',
        'api_maintenance_mode' => 'maintenance',
        'maintenance_title' => 'maintenance',
        'maintenance_content' => 'maintenance',
        'wallet_feature_enabled' => 'wallet',
        'wallet_payment_enabled' => 'wallet',
        'wallet_topup_enabled' => 'wallet',
    ];

    private const VALIDATION_RULES = [
        'site_title' => 'nullable|string|max:255',
        'maintenance_mode' => 'nullable|string|in:on,off',
        'api_maintenance_mode' => 'nullable|string|in:on,off',
        'maintenance_title' => 'nullable|string|max:255',
        'maintenance_content' => 'nullable|string|max:1000',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        'base_currency_id' => 'required|exists:currencies,id',
        'wallet_feature_enabled' => 'nullable|boolean',
        'wallet_payment_enabled' => 'nullable|boolean',
        'wallet_topup_enabled' => 'nullable|boolean',
    ];

    public function index(): View
    {
        return view('admin.settings', [
            'settings' => cache()->remember('settings-all', 300, function () {
                return Setting::pluck('value', 'key');
            }),
            'currencies' => cache()->remember('currencies-list', 3600, function () {
                return Currency::orderBy('name')->pluck('name', 'id');
            }),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(self::VALIDATION_RULES);

        try {
            DB::beginTransaction();

            $data = $this->prepareSettingsData($validated, $request);

            $this->saveSettings($data);
            $this->handleLogoUpload($request);

            DB::afterCommit(function () use ($data) {
                $this->clearSettingsCache();

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'settings',
                    null,
                    null,
                    ['changed_keys' => array_keys($data)]
                );
            });

            DB::commit();

            return back()->with('success', 'تم تحديث إعدادات النظام والعلامة التجارية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ الإعدادات. يرجى المحاولة مرة أخرى.');
        }
    }

    private function prepareSettingsData(array $validated, Request $request): array
    {
        $data = collect($validated)
                    ->only(self::ALLOWED_KEYS)
                    ->filter(fn($value) => $value !== null && $value !== '')
                    ->toArray();
        
        $data['maintenance_mode'] = $request->has('maintenance_mode') ? 'on' : 'off';
        $data['api_maintenance_mode'] = $request->has('api_maintenance_mode') ? 'on' : 'off';
        $data['wallet_feature_enabled'] = $request->boolean('wallet_feature_enabled') ? 'true' : 'false';
        $data['wallet_payment_enabled'] = $request->boolean('wallet_payment_enabled') ? 'true' : 'false';
        $data['wallet_topup_enabled'] = $request->boolean('wallet_topup_enabled') ? 'true' : 'false';


        return $data;
    }

    private function saveSettings(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'group' => self::SETTINGS_GROUP[$key] ?? 'general',
                    ]
                );
            }
        });
    }

    private function handleLogoUpload(Request $request): void
    {
        if (!$request->hasFile('logo')) {
            return;
        }

        $oldLogo = Setting::where('key', 'site_logo')->value('value');

        $result = $this->fileUploadService->uploadImage($request->file('logo'), 'branding');

        if (!$result['success']) {
            Log::error('Logo upload failed during settings update: ' . ($result['message'] ?? 'Unknown error'));
            return;
        }

        Setting::updateOrCreate(
            ['key' => 'site_logo'],
            ['value' => $result['file_path'], 'group' => 'branding']
        );

        if ($oldLogo) {
            DB::afterCommit(function () use ($oldLogo) {
                $this->fileUploadService->deleteFile($oldLogo);
            });
        }
    }

    private function clearSettingsCache(): void
    {
        Cache::forget('system_settings');
        Cache::forget('currencies_with_rates');
        Cache::forget('available_currencies');
        Cache::forget('settings-all');
        Cache::forget('currencies-list');
        Cache::forget('base_currency_id');
        Cache::forget('exchange_rate:*');
    }
}