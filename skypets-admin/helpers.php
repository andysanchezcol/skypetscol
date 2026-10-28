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


// ─── Catálogo de medicamentos (vacunas y desparasitantes) ───────────────────
// Lo edita la doctora desde medicamentos.php. Los valores iniciales son los que
// estaban escritos a mano en certificado.php. Los certificados ya guardados
// conservan su propio texto, así que cambiar este catálogo no los altera.

function medicamentosDefault(): array {
    $vacunas = [
            'BRONCHICINE'               => ['TOS DE PERRERAS'],
            'CANIGEN MHA2PPI/L'         => ['PENTAVALENTE'],
            'CANINE DAPP 3YR LEPTO 1YR' => ['RABIA', 'LEPTOSPIRA'],
            'CANINE VAC-R'              => ['RABIA'],
            'DAPP'                      => ['PENTAVALENTE'],
            'DENSOR 1'                  => ['RABIA'],
            'FELIGEN C.R.P.'            => ['TRIPLE FELINA'],
            'FELIGEN CRP/R'             => ['RABIA', 'TRIPLE FELINA'],
            'INMUNOVAX 5'               => ['PENTAVALENTE'],
            'INMUNOVAX RABIA'           => ['RABIA'],
            'NOBIVAC DHPPI'             => ['PENTAVALENTE'],
            'NOBIVAC INTRA TAC'         => ['TOS DE PERRERAS'],
            'NOBIVAC KC'                => ['TOS DE PERRERAS'],
            'NOBIVAC RL'                => ['LEPTOSPIRA', 'RABIA'],
            'NOVICAC RABIA'             => ['RABIA'],
            'PROCYON DA2PPVL'           => ['PENTAVALENTE'],
            'PROCYON DOG PV'            => ['PARVO'],
            'PROVIDEAN 10CV-4L'         => ['PENTAVALENTE', 'CORONAVIRUS', 'LEPTOSPIRA'],
            'PROVIDEAN 10CV/4L'         => ['PENTAVALENTE', 'CORONAVIRUS', 'LEPTOSPIRA'],
            'PROVIDEAN 6 CV'            => ['PENTAVALENTE'],
            'PROVIDEAN 9 (L4)'          => ['PENTAVALENTE', 'LEPTOSPIRA'],
            'PUREVAX DHPPI'             => ['PENTAVALENTE'],
            'PUREVAX RCP'               => ['TRIPLE FELINA'],
            'RABISIN'                   => ['RABIA'],
            'RABCAN'                    => ['RABIA'],
            'RABIGEN MONO'              => ['TRIPLE FELINA'],
            'RABIES TYPER HILLED'       => ['RABIA'],
            'RABVACL'                   => ['RABIA'],
            'RECOMBITEK C3'             => ['PENTAVALENTE'],
            'RECOMBITEK C6/CV'          => ['PENTAVALENTE'],
            'RECOMBITEK LEPTO'          => ['LEPTOSPIRA'],
            'RECOMBITEK ORAL'           => ['TOS DE PERRERAS'],
            'RONVAC'                    => ['RABIA'],
            'VANGUARD L4 / CV'          => ['PENTAVALENTE', 'LEPTOSPIRA', 'CORONAVIRUS'],
            'VANGUARD PLUS 5'           => ['PENTAVALENTE'],
            'VANGUARD PLUS 5 L4'        => ['PENTAVALENTE'],
            'VENCOMAX 8'                => ['PENTAVALENTE'],
            'VIBIX C5'                  => ['PENTAVALENTE'],
            'VIBIX C6R'                 => ['PENTAVALENTE', 'LEPTOSPIRA', 'RABIA'],
            'VIBIX F3'                  => ['TRIPLE FELINA'],
            'VIBIX F3+R'                => ['RABIA', 'TRIPLE FELINA'],
            'VIBIX PUPPY'               => ['PARVO'],
    ];
    $desint = [
            'ADVOCATE'       => ['principio' => 'MOXIDECTINA 1%',                                              'ica' => 'REG ICA NO 7336 MV'],
            'CANISAN D'      => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
            'CANNEX'         => ['principio' => 'FENBENDAZOL TOLTRAZURIL PRAZIQUANTEL',                        'ica' => 'Reg ICA No 6803-MV'],
            'CREDELIO PLUS'  => ['principio' => 'MIBEMICINA OXIMA',                                            'ica' => 'Reg ICA No 9871 MV'],
            'DOGG & CAT'     => ['principio' => 'PAMOATO DE PIRANTEL Y PRAZIQUANTEL',                          'ica' => 'Reg ICA No 8722-MV'],
            'DRONTAL'        => ['principio' => 'FEBANTEL, PAMOATO DE PIRANTEL PRAZICUANTEL',                  'ica' => 'Reg ICA No 6838 MV'],
            'ENDOGARD'       => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZIQUANTEL IVERMECTINA',       'ica' => 'Reg ICA No 6510 MV'],
            'GALGOCAL'       => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
            'GALGOCAL 600'   => ['principio' => 'ALBENDAZOL PRAZICUANTEL',                                     'ica' => 'Reg ICA No 4853 DB'],
            'NEXGARD COMBO'  => ['principio' => 'EPRINOMECTINA PRAZICUANTEL',                                  'ica' => 'Reg ICA No 11095 MV'],
            'NEXGARD SPECTRA'=> ['principio' => 'MILBEMICIN A OXIMA',                                          'ica' => 'Reg ICA No 9759-MV'],
            'PARACANIS'      => ['principio' => 'EMBONATO PIRANTEL PRAZICUENTEL',                              'ica' => 'Reg ICA No 4265-DB MV'],
            'RONDEL PUPPY'   => ['principio' => 'FEBANTEL PAMOATO DE PIRANTEL PRAZICUENTEL',                   'ica' => 'Reg ICA No 5610 MV'],
            'SIMPARICA TRIO' => ['principio' => 'MOXIDECTINA SAROLANER',                                       'ica' => 'Reg ICA No 10890 MV'],
            'TOTAL F LC'     => ['principio' => 'FENBENDAZOL PIRANTEL PRAZICUANTEL',                           'ica' => 'Reg ICA No 8365 MV'],
    ];
    $desext = [
            'ADVANTIX'       => ['principio' => 'IMIDACLOPRID Y PERMETRINA',  'ica' => 'Reg ICA No 10888 MV'],
            'ADVOCATE'       => ['principio' => 'IMIDACLOPRIDA 10%',           'ica' => 'REG ICA NO 7336 MV'],
            'BRAVECTO'       => ['principio' => 'FLURALANER',                  'ica' => 'Reg ICA 9759 MV'],
            'CREDELIO'       => ['principio' => 'LOTILANER',                   'ica' => 'Reg ICA No 9561 MV'],
            'CREDELIO PLUS'  => ['principio' => 'LOTILANER',                   'ica' => 'Reg ICA No 9871 MV'],
            'FIPROTECTION'   => ['principio' => 'FIPRONIL',                    'ica' => 'REG ICA NO 8794 MV'],
            'NEXGARD'        => ['principio' => 'AFOXOLANER',                  'ica' => 'Reg ICA No 10888 MV'],
            'NEXGARD COMBO'  => ['principio' => 'ESAFOXOLANE',                 'ica' => 'Reg ICA No 11095 MV'],
            'NEXGARD SPECTRA'=> ['principio' => 'MILBEMICIN A OXIMA',          'ica' => 'Reg ICA No 9759-MV'],
            'PULFENNDOS'     => ['principio' => 'FIPRONIL',                    'ica' => 'Reg ICA NO 10504 MV'],
            'REVOLUTION 6%'  => ['principio' => 'SELAMECTINA',                 'ica' => 'Reg ICA No 10887 MV'],
            'SIMPARICA TRIO' => ['principio' => 'MOXIDECTINA SAROLANER',       'ica' => 'Reg ICA No 10890 MV'],
    ];
    return ['vacuna' => $vacunas, 'desint' => $desint, 'desext' => $desext];
}

function asegurarTablaMedicamentos(PDO $db): void {
    $existe = $db->query("SHOW TABLES LIKE 'catalogo_medicamentos'")->fetchColumn();
    if ($existe) return;

    $db->exec("CREATE TABLE catalogo_medicamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(10) NOT NULL,
        nombre VARCHAR(120) NOT NULL,
        usos VARCHAR(255) NULL,
        principio_activo VARCHAR(255) NULL,
        registro_ica VARCHAR(80) NULL,
        UNIQUE KEY uq_tipo_nombre (tipo, nombre)
    ) CHARACTER SET utf8mb4");

    $ins = $db->prepare('INSERT IGNORE INTO catalogo_medicamentos (tipo, nombre, usos, principio_activo, registro_ica) VALUES (?,?,?,?,?)');
    $def = medicamentosDefault();
    foreach ($def['vacuna'] as $nombre => $usos) {
        $ins->execute(['vacuna', $nombre, implode('|', $usos), null, null]);
    }
    foreach (['desint', 'desext'] as $tipo) {
        foreach ($def[$tipo] as $nombre => $d) {
            $ins->execute([$tipo, $nombre, null, $d['principio'], $d['ica']]);
        }
    }
}

// Devuelve ['vacuna' => [nombre => [usos]], 'desint' => [nombre => [principio, ica]], 'desext' => ...]
function catalogoMedicamentos(): array {
    try {
        $db = getDB();
        asegurarTablaMedicamentos($db);
        $out = ['vacuna' => [], 'desint' => [], 'desext' => []];
        foreach ($db->query('SELECT tipo, nombre, usos, principio_activo, registro_ica FROM catalogo_medicamentos ORDER BY nombre') as $r) {
            if ($r['tipo'] === 'vacuna') {
                $out['vacuna'][$r['nombre']] = array_values(array_filter(array_map('trim', explode('|', (string)$r['usos']))));
            } elseif (isset($out[$r['tipo']])) {
                $out[$r['tipo']][$r['nombre']] = ['principio' => (string)$r['principio_activo'], 'ica' => (string)$r['registro_ica']];
            }
        }
        return $out;
    } catch (Throwable $e) {
        error_log('catalogoMedicamentos: ' . $e->getMessage());
        return medicamentosDefault();
    }
}
