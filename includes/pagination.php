<?php
/**
 * Pagination Helper
 */

function paginate(int $totalItems, int $currentPage = 1, int $perPage = 15): array {
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'        => $totalItems,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages
    ];
}

function render_pagination(array $p, string $baseUrl = ''): string {
    if ($p['total_pages'] <= 1) return '';

    $html = '<div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 sm:px-6 bg-white">';
    $html .= '<div class="text-sm text-slate-700">Showing <span class="font-medium">' . ($p['offset'] + 1) . '</span> to <span class="font-medium">' . min($p['offset'] + $p['per_page'], $p['total']) . '</span> of <span class="font-medium">' . $p['total'] . '</span> results</div>';
    $html .= '<div class="flex space-x-2">';
    
    if ($p['has_prev']) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($p['current_page'] - 1) . '" class="px-3 py-1 text-sm rounded border border-slate-300 text-slate-700 hover:bg-slate-50">Previous</a>';
    }
    for ($i = 1; $i <= $p['total_pages']; $i++) {
        if ($i == $p['current_page']) {
            $html .= '<span class="px-3 py-1 text-sm rounded bg-purple-600 text-white font-medium">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="px-3 py-1 text-sm rounded border border-slate-300 text-slate-700 hover:bg-slate-50">' . $i . '</a>';
        }
    }
    if ($p['has_next']) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($p['current_page'] + 1) . '" class="px-3 py-1 text-sm rounded border border-slate-300 text-slate-700 hover:bg-slate-50">Next</a>';
    }
    
    $html .= '</div></div>';
    return $html;
}
