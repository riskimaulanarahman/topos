<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrinterSettingUpdateRequest;
use App\Models\Outlet;
use App\Models\PrinterSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrinterSettingController extends Controller
{
    use ResolvesOutlet;

    public function show(Request $request, Outlet $outlet): JsonResponse
    {
        $this->assertUserHasOutletAccess($request->user(), $outlet->id);

        $setting = $this->getOrCreatePrinterSetting($outlet);

        return response()->json([
            'data' => $this->transformSetting($setting),
        ]);
    }

    public function update(
        PrinterSettingUpdateRequest $request,
        Outlet $outlet
    ): JsonResponse {
        $this->assertUserHasOutletAccess($request->user(), $outlet->id);

        $setting = $this->getOrCreatePrinterSetting($outlet);
        $payload = $request->validated();

        if (array_key_exists('paper_size', $payload)) {
            $setting->paper_size = (string) $payload['paper_size'];
        }

        if (array_key_exists('title_font_size', $payload)) {
            $setting->title_font_size = (int) $payload['title_font_size'];
        }

        if (array_key_exists('show_logo', $payload)) {
            $setting->show_logo = (bool) $payload['show_logo'];
        }

        if (array_key_exists('show_footer', $payload)) {
            $setting->show_footer = (bool) $payload['show_footer'];
        }

        if (array_key_exists('footer_text', $payload)) {
            $footer = $payload['footer_text'];
            $setting->footer_text = $footer !== null ? trim((string) $footer) : null;
        }

        if (! empty($payload['remove_logo'])) {
            $this->deleteLogoIfExists($setting);
            $setting->logo_path = null;
            $setting->show_logo = false;
        }

        if ($request->hasFile('logo')) {
            $this->deleteLogoIfExists($setting);
            $path = $this->storeLogoToPublic($request->file('logo'));
            $setting->logo_path = $path;
            $setting->show_logo = true;
        }

        if (! $setting->show_footer) {
            $setting->footer_text = null;
        }

        $setting->save();

        return response()->json([
            'message' => __('Pengaturan printer berhasil diperbarui.'),
            'data' => $this->transformSetting($setting->fresh()),
        ]);
    }

    protected function getOrCreatePrinterSetting(Outlet $outlet): PrinterSetting
    {
        return $outlet->printerSetting()->firstOrCreate(
            [],
            [
                'paper_size' => '58',
                'title_font_size' => 2,
                'show_logo' => false,
                'show_footer' => false,
            ]
        );
    }

    protected function deleteLogoIfExists(PrinterSetting $setting): void
    {
        if (! $setting->logo_path) {
            return;
        }

        $publicFile = public_path($setting->logo_path);
        if ($publicFile && File::exists($publicFile)) {
            File::delete($publicFile);
            return;
        }

        if (Storage::disk('public')->exists($setting->logo_path)) {
            Storage::disk('public')->delete($setting->logo_path);
        }
    }

    protected function transformSetting(PrinterSetting $setting): array
    {
        return [
            'outlet_id' => $setting->outlet_id,
            'paper_size' => $setting->paper_size,
            'title_font_size' => $setting->title_font_size,
            'show_logo' => (bool) $setting->show_logo,
            'logo_url' => $this->resolveLogoUrl($setting->logo_path),
            'logo_path' => $setting->logo_path,
            'show_footer' => (bool) $setting->show_footer,
            'footer_text' => $setting->footer_text,
            'updated_at' => optional($setting->updated_at)->toIso8601String(),
            'created_at' => optional($setting->created_at)->toIso8601String(),
        ];
    }

    protected function storeLogoToPublic($file): string
    {
        $directory = public_path('printer-logos');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'printer-logos/' . $filename;
    }

    protected function resolveLogoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $publicFile = public_path($path);
        if ($publicFile && File::exists($publicFile)) {
            return asset($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }
}
