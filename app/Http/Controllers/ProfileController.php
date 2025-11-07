<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(Auth::id());

        return view('pages.profile.index', compact('user'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = User::findOrFail(Auth::id());
        $data = $request->validated();

        if ($request->hasFile('store_logo')) {
            $this->deleteExistingLogo($user->store_logo_path);

            $path = $this->storeLogoToPublic($request->file('store_logo'));
            $data['store_logo_path'] = $path;
        }

        foreach (['operating_hours', 'store_addresses', 'map_links'] as $field) {
            if (array_key_exists($field, $data)) {
                $values = array_values(array_filter(
                    (array) ($data[$field] ?? []),
                    fn ($value) => $value !== null && $value !== ''
                ));
                $data[$field] = empty($values) ? null : $values;
            }
        }

        $user->fill($data);
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    protected function storeLogoToPublic($file): string
    {
        $directory = public_path('store-logos');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'store-logos/' . $filename;
    }

    protected function deleteExistingLogo(?string $path): void
    {
        if (! $path) {
            return;
        }

        $publicFile = public_path($path);
        if ($publicFile && File::exists($publicFile)) {
            File::delete($publicFile);
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
