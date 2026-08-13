<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Services\BrandingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico|max:1024',
        ], [
            'app_name.required' => 'Nama Aplikasi wajib diisi.',
            'brand_primary.regex' => 'Format warna Primary harus berupa kode Hex 6 digit (contoh: #E11D48).',
            'brand_accent.regex' => 'Format warna Accent harus berupa kode Hex 6 digit (contoh: #F43F5E).',
            'pwa_theme_color.regex' => 'Format warna Theme PWA harus berupa kode Hex 6 digit (contoh: #E11D48).',
            'logo.mimes' => 'Logo harus berupa gambar berformat PNG, JPG, JPEG, atau WEBP.',
            'logo.max' => 'Ukuran file logo maksimal 2 MB.',
            'icon.mimes' => 'Icon aplikasi harus berupa gambar berformat PNG, JPG, JPEG, atau WEBP.',
            'icon.max' => 'Ukuran file icon maksimal 2 MB.',
            'favicon.mimes' => 'Favicon harus berupa file PNG atau ICO.',
        ]);

        $beforeData = $brandingService->getBrandingData();

        // 1. Text settings
        AppSetting::set('app_name', trim($request->input('app_name')), 'string', true);
        AppSetting::set('app_short_name', trim($request->input('app_short_name')), 'string', true);
        AppSetting::set('company_name', trim($request->input('company_name', '')), 'string', true);
        AppSetting::set('app_tagline', trim($request->input('app_tagline', '')), 'string', true);
        AppSetting::set('brand_primary', strtoupper(trim($request->input('brand_primary'))), 'string', true);
        AppSetting::set('brand_accent', strtoupper(trim($request->input('brand_accent'))), 'string', true);
        AppSetting::set('pwa_theme_color', strtoupper(trim($request->input('pwa_theme_color'))), 'string', true);

        // 2. Logo Upload
        if ($request->hasFile('logo')) {
            $oldLogoPath = AppSetting::get('app_logo_path');
            $file = $request->file('logo');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'branding/logo-' . Str::uuid() . '.' . $ext;

            $path = Storage::disk('public')->putFileAs('', $file, $filename);
            AppSetting::set('app_logo_path', $path, 'string', true);

            if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }
        }

        // 3. Icon Upload
        if ($request->hasFile('icon')) {
            $oldIconPath = AppSetting::get('app_icon_path');
            $file = $request->file('icon');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = 'branding/icon-' . Str::uuid() . '.' . $ext;

            $path = Storage::disk('public')->putFileAs('', $file, $filename);
            AppSetting::set('app_icon_path', $path, 'string', true);

            if ($oldIconPath && Storage::disk('public')->exists($oldIconPath)) {
                Storage::disk('public')->delete($oldIconPath);
            }
        }

        // 4. Favicon Upload
        if ($request->hasFile('favicon')) {
            $oldFaviconPath = AppSetting::get('app_favicon_path');
            $file = $request->file('favicon');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'ico');
            $filename = 'branding/favicon-' . Str::uuid() . '.' . $ext;

            $path = Storage::disk('public')->putFileAs('', $file, $filename);
            AppSetting::set('app_favicon_path', $path, 'string', true);

            if ($oldFaviconPath && Storage::disk('public')->exists($oldFaviconPath)) {
                Storage::disk('public')->delete($oldFaviconPath);
            }
        }

        $afterData = $brandingService->getBrandingData();
        $brandingService->clearCache();

        AuditLog::log('branding.updated', null, $beforeData, $afterData, $request->user());

        return redirect()->back()->with('success', 'Profil dan identitas visual aplikasi berhasil diperbarui.');
    }
}
