<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
