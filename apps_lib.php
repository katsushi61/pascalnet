<?php
function extract_app_meta(string $path, string $slugFallback): array {
    $head = file_get_contents($path, false, null, 0, 2000);
    if ($head === false || !preg_match('/<!--(.*?)-->/s', $head, $m)) {
        return ['title' => $slugFallback, 'description' => ''];
    }
    $title = $slugFallback;
    $description = '';
    $inDescription = false;
    foreach (explode("\n", $m[1]) as $line) {
        $trimmed = trim($line);
        if (preg_match('/^App:\s*(.+)$/i', $trimmed, $mm)) {
            $title = trim($mm[1]);
            $inDescription = false;
        } elseif (preg_match('/^Created:\s*(.+)$/i', $trimmed)) {
            $inDescription = false;
        } elseif (preg_match('/^Description:\s*(.*)$/i', $trimmed, $mm)) {
            $description = trim($mm[1]);
            $inDescription = true;
        } elseif ($inDescription && $trimmed !== '') {
            $description .= ($description === '' ? '' : ' ') . $trimmed;
        }
    }
    return ['title' => $title, 'description' => $description];
}

function list_all_apps(): array {
    $apps = [];
    foreach (glob(__DIR__ . '/apps/*/index.html') as $path) {
        $slug = basename(dirname($path));
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            continue;
        }
        $meta = extract_app_meta($path, $slug);
        $apps[] = ['slug' => $slug, 'title' => $meta['title'], 'description' => $meta['description']];
    }
    usort($apps, fn($a, $b) => strcmp($a['title'], $b['title']));
    return $apps;
}
