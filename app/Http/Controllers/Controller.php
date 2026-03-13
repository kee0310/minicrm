<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    protected function jsonOrRedirect(
        Request $request,
        string $message,
        int $status = 200,
        string $flash = 'success',
        ?string $redirectUrl = null
    ) {
        if ($this->wantsJson($request)) {
            return response()->json(['message' => $message], $status);
        }

        if ($redirectUrl) {
            return redirect($redirectUrl)->with($flash, $message);
        }

        return redirect()->back()->with($flash, $message);
    }

    protected function redirectWithFlashToPrevious(
        Request $request,
        string $fallbackUrl,
        string $flashType,
        string $message
    ): RedirectResponse {
        $previous = url()->previous();
        $current = $request->fullUrl();
        $target = (! empty($previous) && $previous !== $current) ? $previous : $fallbackUrl;
        $separator = str_contains($target, '?') ? '&' : '?';
        $query = http_build_query([
            'flash_message' => $message,
            'flash_type' => $flashType,
        ]);

        return redirect()->to($target.$separator.$query)->with($flashType, $message);
    }
}
