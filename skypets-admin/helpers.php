<?php
function col(array $row, string $key): string {
    $cols = COL;
    $idx  = $cols[$key] ?? -1;
    return trim($row[$idx] ?? '');
}

function tipoLabel(string $tipo): string {
    $tipo = strtoupper($tipo);
    if (str_contains($tipo, 'EUROPA'))              return 'INTERNACIONAL EUROPA';
    if (str_contains($tipo, 'LATINOAM'))            return 'INTERNACIONAL LATINOAMÉRICA';
    if (str_contains($tipo, 'INTERNAC'))            return 'INTERNACIONAL';
    if (str_contains($tipo, 'NACIONAL'))            return 'NACIONAL';
    return $tipo;
}

function esInternacional(string $tipo): bool {
    return str_contains(strtoupper($tipo), 'INTERNAC');
}

function statusLabel(string $s): string {
    return match($s) {
        'generado'   => '<span class="badge badge-ok">Generado</span>',
        'en_proceso' => '<span class="badge badge-warn">En proceso</span>',
        default      => '<span class="badge badge-pending">Pendiente</span>',
    };
}

function driveViewUrl(string $fileId): string {
    return "https://drive.google.com/file/d/$fileId/view";
}

function formatFecha(string $fecha): string {
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    if (!$ts) return $fecha;
    $meses = ['','enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('d', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

function formatFechaCorta(?string $fecha): string {
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    return $ts ? date('d-m-Y', $ts) : $fecha;
}

function especieCheck(string $especie): array {
    $e = strtoupper($especie);
    return [
        'canino' => str_contains($e, 'CANIN') ? 'X' : '',
        'felino' => str_contains($e, 'FELIN') ? 'X' : '',
    ];
}
