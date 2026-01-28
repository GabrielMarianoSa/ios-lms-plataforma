<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('ios_base_path')) {
    function ios_base_path(): string
    {
        // Detecta se está em localhost (Laragon usa subpasta /ios)
        $isLocalhost = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']) 
                       || strpos($_SERVER['SERVER_NAME'] ?? '', '.test') !== false;
        
        if ($isLocalhost) {
            // Localhost: retorna /ios
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
            $parts = explode('/', trim($scriptName, '/'));
            $root = $parts[0] ?? '';
            return $root !== '' ? '/' . $root : '';
        }
        
        // Produção: arquivos estão na raiz do htdocs
        return '';
    }
}

if (!function_exists('ios_partner_logo_url')) {
    /**
     * Resolve a partner logo: prefer local file in assets/images/{slug}.{png,svg,jpg}
     * otherwise return a Simple Icons CDN SVG URL (when available) or a placeholder.
     */
    function ios_partner_logo_url(string $slug): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug)));
        $possible = ['png', 'svg', 'jpg', 'jpeg'];
        foreach ($possible as $ext) {
            $localPath = __DIR__ . '/../assets/images/' . $slug . '.' . $ext;
            if (file_exists($localPath)) {
                return ios_url('/assets/images/' . $slug . '.' . $ext);
            }
        }

        // fallback to simple-icons CDN (many brands available)
        // simple-icons filenames are kebab-case; we try that.
        $siUrl = 'https://cdn.jsdelivr.net/gh/simple-icons/simple-icons@v9/icons/' . $slug . '.svg';

        // As a final fallback, use a tiny placeholder service
        $placeholder = 'https://placehold.co/200x80?text=' . rawurlencode(strtoupper($slug));

        // We can't reliably check remote existence here without network requests,
        // so prefer the simple-icons URL which will 404 if not present — browsers handle that.
        return $siUrl . ' ';
    }
}

if (!function_exists('ios_partner_logo_img')) {
    function ios_partner_logo_img(string $slug, string $alt = '', string $class = 'img-fluid'): string
    {
        $url = ios_partner_logo_url($slug);
        // If the url contains a space (our placeholder concat above), split and prefer first
        $parts = preg_split('/\s+/', trim($url));
        $url = $parts[0] ?? $url;
        $altEsc = htmlspecialchars($alt ?: $slug, ENT_QUOTES, 'UTF-8');
        $classEsc = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="' . $classEsc . '" alt="' . $altEsc . '">';
    }
}

if (!function_exists('ios_url')) {
    function ios_url(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return ios_base_path() . $path;
    }
}

if (!function_exists('ios_is_logged_in')) {
    function ios_is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('ios_is_admin')) {
    function ios_is_admin(): bool
    {
        return !empty($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
    }
}

if (!function_exists('ios_table_exists')) {
    function ios_table_exists(mysqli $conn, string $tableName): bool
    {
        $tableName = $conn->real_escape_string($tableName);
        $res = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('ios_normalize_inscricao_status')) {
    function ios_normalize_inscricao_status(?string $status): string
    {
        $s = strtolower(trim((string)$status));

        // Backward compatibility: registros antigos podem estar vazios
        if ($s === '' || $s === 'ativo') return 'aprovado';

        if (in_array($s, ['aprovado', 'aceito'], true)) return 'aprovado';
        if (in_array($s, ['pendente', 'em_analise', 'em análise', 'analise', 'análise'], true)) return 'pendente';
        if (in_array($s, ['negado', 'reprovado', 'recusado'], true)) return 'negado';

        return $s;
    }
}

if (!function_exists('ios_is_inscricao_aprovada')) {
    function ios_is_inscricao_aprovada(?string $status): bool
    {
        return ios_normalize_inscricao_status($status) === 'aprovado';
    }
}

if (!function_exists('ios_inscricao_badge')) {
    /**
     * @return array{label: string, class: string, icon: string}
     */
    function ios_inscricao_badge(?string $status): array
    {
        $s = ios_normalize_inscricao_status($status);
        if ($s === 'aprovado') {
            return ['label' => 'Aprovada', 'class' => 'text-bg-success', 'icon' => 'bi-check2-circle'];
        }
        if ($s === 'negado') {
            return ['label' => 'Negada', 'class' => 'text-bg-danger', 'icon' => 'bi-x-circle'];
        }
        return ['label' => 'Em análise', 'class' => 'text-bg-warning', 'icon' => 'bi-hourglass-split'];
    }
}
