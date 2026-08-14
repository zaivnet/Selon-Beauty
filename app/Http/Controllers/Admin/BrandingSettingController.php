<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Services\BrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BrandingSettingController extends Controller
{
    public function index(Request $request, BrandingService $brandingService): View
    {
        if (! in_array($request->user()->role, ['superadmin', 'owner'], true)) {
            abort(403, 'Akses ditolak. Pengaturan Branding hanya dapat diakses oleh Superadmin dan Owner.');
        }

        return view('admin.settings.branding', [
            'brandingData' => $brandingService->getBrandingData(),
        ]);
    }

    public function update(Request $request, BrandingService $brandingService): RedirectResponse
    {
        if (! in_array($request->user()->role, ['superadmin', 'owner'], true)) {
            abort(403, 'Akses ditolak. Pengaturan Branding hanya dapat diakses oleh Superadmin dan Owner.');
        }
        $request->validate([
            'app_name' => 'required|string|max:100',
            'app_short_name' => 'required|string|max:50',
            'company_name' => 'nullable|string|max:150',
            'app_tagline' => 'nullable|string|max:200',
            'brand_primary' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_accent' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pwa_theme_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'icon' => 'nullable|image|mimes:png|dimensions:min_width=192,min_height=192|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico|max:1024',
        ], [
            'app_name.required' => 'Nama Aplikasi wajib diisi.',
            'brand_primary.regex' => 'Format warna Primary harus berupa kode Hex 6 digit (contoh: #E11D48).',
            'brand_accent.regex' => 'Format warna Accent harus berupa kode Hex 6 digit (contoh: #F43F5E).',
            'pwa_theme_color.regex' => 'Format warna Theme PWA harus berupa kode Hex 6 digit (contoh: #E11D48).',
            'logo.mimes' => 'Logo harus berupa gambar berformat PNG, JPG, JPEG, atau WEBP.',
            'logo.max' => 'Ukuran file logo maksimal 2 MB.',
            'icon.mimes' => 'Icon aplikasi harus berupa file PNG.',
            'icon.dimensions' => 'Icon aplikasi minimal berukuran 192x192 piksel.',
            'icon.max' => 'Ukuran file icon maksimal 2 MB.',
            'favicon.mimes' => 'Favicon harus berupa file PNG atau ICO.',
        ]);

        $beforeData = $brandingService->getBrandingData();
        $media = [
            'logo' => ['setting' => 'app_logo_path', 'prefix' => 'logo'],
            'icon' => ['setting' => 'app_icon_path', 'prefix' => 'icon'],
            'favicon' => ['setting' => 'app_favicon_path', 'prefix' => 'favicon'],
        ];
        $newPaths = [];
        $oldPaths = [];

        try {
            foreach ($media as $input => $definition) {
                if (! $request->hasFile($input)) {
                    continue;
                }

                $file = $request->file($input);
                $extension = strtolower($file->getClientOriginalExtension() ?: ($input === 'favicon' ? 'ico' : 'png'));
                $filename = 'branding/'.$definition['prefix'].'-'.Str::uuid().'.'.$extension;
                $path = Storage::disk('public')->putFileAs('', $file, $filename);
                if (! $path) {
                    throw new RuntimeException("Gagal menyimpan media branding {$input}.");
                }

                $newPaths[$definition['setting']] = $path;
                $oldPaths[] = AppSetting::get($definition['setting']);
            }

            DB::transaction(function () use ($request, $brandingService, $beforeData, $newPaths): void {
                AppSetting::set('app_name', trim($request->input('app_name')), 'string', true);
                AppSetting::set('app_short_name', trim($request->input('app_short_name')), 'string', true);
                AppSetting::set('company_name', trim($request->input('company_name', '')), 'string', true);
                AppSetting::set('app_tagline', trim($request->input('app_tagline', '')), 'string', true);
                AppSetting::set('brand_primary', strtoupper(trim($request->input('brand_primary'))), 'string', true);
                AppSetting::set('brand_accent', strtoupper(trim($request->input('brand_accent'))), 'string', true);
                AppSetting::set('pwa_theme_color', strtoupper(trim($request->input('pwa_theme_color'))), 'string', true);

                foreach ($newPaths as $setting => $path) {
                    AppSetting::set($setting, $path, 'string', true);
                }

                $brandingService->clearCache();
                AuditLog::log(
                    'branding.updated',
                    null,
                    $beforeData,
                    $brandingService->getBrandingData(),
                    $request->user(),
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            $brandingService->clearCache();

            throw $exception;
        }

        foreach (array_filter($oldPaths) as $oldPath) {
            $normalized = str_replace('\\', '/', (string) $oldPath);
            if (str_starts_with($normalized, 'branding/') && ! in_array($normalized, $newPaths, true)) {
                Storage::disk('public')->delete($normalized);
            }
        }

        $brandingService->clearCache();

        return redirect()->back()->with('success', 'Profil dan identitas visual aplikasi berhasil diperbarui.');
    }
}
