<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\ApplicationBranding;
use App\Services\PrivilegedAudit;
use App\Services\RoleNavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $navigation = app(RoleNavigationService::class);

        return view('superadmin.settings', [
            'title' => 'Setting',
            'heading' => 'Setting',
            'crumbs' => 'SUPERADMIN • SETTING',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'settings' => ApplicationSetting::current(),
        ]);
    }

    public function update(Request $request, ApplicationBranding $branding): RedirectResponse
    {
        $data = $request->validate([
            'application_name' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ]);
        $settings = ApplicationSetting::current();
        $before = $settings->only(['application_name', 'logo_path']);
        $oldLogo = $settings->logo_path;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        unset($data['logo']);
        $settings->update($data);

        if (isset($data['logo_path']) && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        PrivilegedAudit::record('settings.updated', null, $before, $settings->only(['application_name', 'logo_path']), $request);
        $branding->forget();

        return redirect()->route('superadmin.settings.edit')->with('status', 'Pengaturan berhasil diperbarui.');
    }
}
