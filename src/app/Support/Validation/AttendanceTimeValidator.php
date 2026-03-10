<?php

namespace App\Support\Validation;

use Carbon\Carbon;
use Illuminate\Support\MessageBag;

class AttendanceTimeValidator
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data, MessageBag $bag): void
    {
        $startStr = $data['work_start_at'] ?? null;
        $endStr   = $data['work_end_at'] ?? null;

        $start = null;
        $end = null;

        try {
            if (is_string($startStr) && $startStr !== '') $start = Carbon::createFromFormat('H:i', $startStr);
            if (is_string($endStr) && $endStr !== '') $end = Carbon::createFromFormat('H:i', $endStr);
        } catch (\Throwable $e) {
            // date_format で落ちるのでここでは何もしない
        }

        // 1) 出勤 < 退勤（同じもNG）
        $workOrderOk = false;
        if ($start && $end) {
            if ($start->greaterThanOrEqualTo($end)) {
                $bag->add('work_start_at', '出勤時間もしくは退勤時間が不適切な値です');
            } else {
                $workOrderOk = true;
            }
        }

        $breaks = $data['breaks'] ?? [];
        if (!is_array($breaks)) $breaks = [];

        /**
         * validBreaks: 重複チェック/合計チェック用（文字列で保持）
         * @var array<int, array{i:int,start:string,end:string}>
         */
        $validBreaks = [];

        foreach ($breaks as $i => $b) {
            if (!is_array($b)) continue;

            $bs = $b['start'] ?? null;
            $be = $b['end'] ?? null;

            if (!is_string($bs) || $bs === '' || !is_string($be) || $be === '') {
                continue; // required_with は rules() 側で処理
            }

            try {
                $bsC = Carbon::createFromFormat('H:i', $bs);
                $beC = Carbon::createFromFormat('H:i', $be);
            } catch (\Throwable $e) {
                continue; // date_format は rules() 側で処理
            }

            // 2) 休憩開始 < 休憩終了
            if ($bsC->greaterThanOrEqualTo($beC)) {
                $bag->add("breaks.$i.end", '休憩時間が不適切な値です');
                continue;
            }

            // 3) 勤務時間の外に出ていないか（勤務時間が成立している場合のみ）
            if ($workOrderOk) {
                // 出勤より前（開始 or 終了が出勤より前）
                if ($bsC->lessThan($start) || $beC->lessThan($start)) {
                    $bag->add("breaks.$i.start", '休憩時間が不適切な値です');
                    $bag->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                // 退勤より後（開始が退勤より後）
                if ($bsC->greaterThan($end)) {
                    $bag->add("breaks.$i.start", '休憩時間が不適切な値です');
                    $bag->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                // 退勤より後（終了が退勤より後）
                if ($beC->greaterThan($end)) {
                    $bag->add("breaks.$i.start", '休憩時間が不適切な値です');
                    $bag->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    continue;
                }
            }

            $validBreaks[] = [
                'i' => (int)$i,
                'start' => $bs,
                'end' => $be,
            ];
        }

        // 4) 休憩重複（開始でソートして、前の終了 > 次の開始 で重複）
        usort($validBreaks, fn($a, $b) => strcmp($a['start'], $b['start']));
        for ($k = 0; $k < count($validBreaks) - 1; $k++) {
            $cur  = $validBreaks[$k];
            $next = $validBreaks[$k + 1];

            if ($cur['end'] > $next['start']) {
                $bag->add("breaks.{$next['i']}.start", '休憩時間が重複しています');
            }
        }

        // 5) 休憩合計が勤務時間以上（>=）ならNG
        if ($workOrderOk) {
            $workTotal = $start->diffInMinutes($end);

            $breakTotal = 0;
            foreach ($validBreaks as $b) {
                $bsC = Carbon::createFromFormat('H:i', $b['start']);
                $beC = Carbon::createFromFormat('H:i', $b['end']);
                $breakTotal += $bsC->diffInMinutes($beC);
            }

            if ($breakTotal >= $workTotal) {
                $bag->add('work_end_at', '休憩時間が勤務時間を超えています');
            }
        }

        // 6) 行エラー集約（breaks.$i.start / breaks.$i.end → breaks.$i に一本化）
        $breaks2 = $data['breaks'] ?? [];
        if (!is_array($breaks2)) $breaks2 = [];

        foreach ($breaks2 as $i => $_) {
            $startKey = "breaks.$i.start";
            $endKey   = "breaks.$i.end";
            $rowKey   = "breaks.$i";

            $all = array_merge($bag->get($startKey), $bag->get($endKey));
            $all = array_values(array_unique($all));

            if (!$all) continue;

            $priority = [
                '休憩開始と休憩終了はセットで入力してください',
                '休憩開始の形式が不正です',
                '休憩終了の形式が不正です',
                '休憩時間もしくは退勤時間が不適切な値です',
                '休憩時間が不適切な値です',
                '休憩時間が重複しています',
            ];

            $picked = null;
            foreach ($priority as $p) {
                if (in_array($p, $all, true)) {
                    $picked = $p;
                    break;
                }
            }
            if ($picked === null) $picked = $all[0];

            if (!$bag->has($rowKey)) {
                $bag->add($rowKey, $picked);
            }
        }
    }
}
