<?php

namespace App\Services;

class MonteCarloExcelService
{
    public const RAND_MIN = 0;
    public const RAND_MAX = 100;
    public const SCALE    = 100;

    /**
     * Build distribusi + range 0..100 (Excel-style)
     * training = array nilai permintaan (harian/bulanan/tahunan sesuai skenario)
     */
    public function buildDistribution(array $training): array
    {
        // frekuensi masing-masing nilai
        $freq = array_count_values(array_map('intval', $training));
        ksort($freq);

        $total = array_sum($freq); // total data training (jumlah baris/periode)
        if ($total <= 0) return [];

        $rows = [];
        $cum  = 0.0;

        foreach ($freq as $nilai => $f) {
            $prob = $f / $total;
            $cum += $prob;

            $rows[] = [
                'jumlah_permintaan' => (int)$nilai,
                'frekuensi'         => (int)$f,
                'probabilitas'      => (float)$prob,
                'kumulatif'         => (float)$cum,
            ];
        }

        // buat range 0..100 inclusive
        $start = self::RAND_MIN;
        $lastEnd = self::RAND_MIN - 1;

        foreach ($rows as $i => $r) {
            $cut = (int) round(((float)$r['kumulatif']) * self::SCALE, 0);

            if ($cut < self::RAND_MIN) $cut = self::RAND_MIN;
            if ($cut > self::RAND_MAX) $cut = self::RAND_MAX;

            // pastikan tidak mundur
            if ($cut < $lastEnd) $cut = $lastEnd;
            if ($cut < $start)   $cut = $start;

            $rows[$i]['range_min'] = $start;
            $rows[$i]['range_max'] = $cut;

            $lastEnd = $cut;
            $start   = $cut + 1;
            if ($start > self::RAND_MAX) break;
        }

        // pastikan baris terakhir menutup sampai 100
        if (!empty($rows)) {
            $rows[count($rows) - 1]['range_max'] = self::RAND_MAX;
        }

        return $rows;
    }

    public function pickSimulatedDemand(int $rand, array $distRows): int
    {
        if ($rand < self::RAND_MIN) $rand = self::RAND_MIN;
        if ($rand > self::RAND_MAX) $rand = self::RAND_MAX;

        foreach ($distRows as $r) {
            if ($rand >= (int)$r['range_min'] && $rand <= (int)$r['range_max']) {
                return (int)$r['jumlah_permintaan'];
            }
        }

        return (int)($distRows[count($distRows)-1]['jumlah_permintaan'] ?? 0);
    }

    public function computeError(int $pred, int $actual): array
    {
        $ad  = abs($pred - $actual);
        $se  = ($pred - $actual) ** 2;
        $ape = ($actual > 0) ? abs(($pred - $actual) / $actual) * 100 : 0;

        return ['AD' => $ad, 'SE' => $se, 'APE' => $ape];
    }
}
