<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Passenger;
use App\Models\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileAccessController extends Controller
{
    public function userView(Request $request, string $path): mixed
    {
        return $this->serveFile($request, $path, allowAdmin: false);
    }

    public function secureView(Request $request, string $path): mixed
    {
        return $this->serveFile($request, $path, allowAdmin: true);
    }


    protected function serveFile(Request $request, string $path, bool $allowAdmin): mixed
    {
        $user = $request->user();
        $path = $this->normalizePath($path);
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'الملف غير موجود');
        }

        if (!$this->isAuthorized($user, $path, $allowAdmin)) {
            abort(403, 'عذراً، لا تملك صلاحية الوصول لهذا الملف');
        }

        return Storage::disk('local')->response($path);
    }


    protected function isAuthorized($user, string $path, bool $allowAdmin): bool
    {
        if ($allowAdmin && $user->isAdmin()) {
            return true;
        }

        $segments = explode('/', $path);
        if (count($segments) < 2) return false;

        $folder = $segments[0];
        $id = $segments[1];

        switch ($folder) {
            case 'providers':
                $provider = ProviderService::find($id);
                return $provider && ($provider->user_id === $user->id || $provider->user_id === $user->user_parent);

            case 'passengers':
                $passenger = Passenger::find($id);
                return $passenger && $passenger->created_by === $user->id;

            case 'temp':
                return ctype_digit($id) && (int) $id === (int) $user->id;

            default:
                return false;
        }
    }

    protected function normalizePath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            abort(404, 'الملف غير موجود');
        }

        return $normalized;
    }
}