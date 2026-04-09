<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureFileUploadSafe {
    private array $allowedMimes      = ['image/jpeg','image/png','image/webp'];
    private array $blockedExtensions = ['php','php3','php4','php5','phtml','exe','sh','bat','cmd','js','html'];

    public function handle(Request $request, Closure $next) {
        foreach ($request->allFiles() as $file) {
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $f) {
                if (!in_array($f->getMimeType(), $this->allowedMimes)) abort(422, "File type not allowed.");
                if (in_array(strtolower($f->getClientOriginalExtension()), $this->blockedExtensions)) abort(422, "File extension not allowed.");
            }
        }
        return $next($request);
    }
}
