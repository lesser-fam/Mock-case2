<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'work_start_at' => 'required|date_format:H:i',
            'work_end_at'   => 'required|date_format:H:i',
            'memo'          => 'required|max:255',

            'breaks'            => 'nullable|array',
            'breaks.*.start'    => 'nullable|date_format:H:i|required_with:breaks.*.end',
            'breaks.*.end'      => 'nullable|date_format:H:i|required_with:breaks.*.start',
        ];
    }

    public function messages()
    {
        return [
            'work_start_at.required' => '出勤時間を入力してください',
            'work_start_at.date_format' => '出勤時間の形式が不正です',

            'work_end_at.required' => '退勤時間を入力してください',
            'work_end_at.date_format' => '退勤時間の形式が不正です',

            'memo.required' => '備考を記入してください',
            'memo.max' => '備考は255文字以内で入力してください',

            'breaks.*.start.date_format' => '休憩開始の形式が不正です',
            'breaks.*.end.date_format' => '休憩終了の形式が不正です',

            'breaks.*.start.required_with' => '休憩開始と休憩終了はセットで入力してください',
            'breaks.*.end.required_with' => '休憩開始と休憩終了はセットで入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $startStr = $this->input('work_start_at');
            $endStr   = $this->input('work_end_at');

            $start = null;
            $end = null;

            try {
                if ($startStr) $start = Carbon::createFromFormat('H:i', $startStr);
                if ($endStr) $end = Carbon::createFromFormat('H:i', $endStr);
            } catch (\Throwable $e) {
            }

            if ($start && $end && $start->greaterThanOrEqualTo($end)) {
                $v->errors()->add('work_start_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) $breaks = [];

            $validBreaks = [];

            foreach ($breaks as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;
                if (!$bs || !$be) continue;

                try {
                    $bsC = Carbon::createFromFormat('H:i', $bs);
                    $beC = Carbon::createFromFormat('H:i', $be);
                } catch (\Throwable $e) {
                    continue;
                }

                if ($bsC->greaterThanOrEqualTo($beC)) {
                    $v->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                    continue;
                }

                if ($start && $end) {
                    if ($bsC->lessThan($start) || $beC->lessThan($start)) {
                        $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                        continue;
                    }

                    if ($bsC->greaterThan($end)) {
                        $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                        continue;
                    }

                    if ($beC->greaterThan($end)) {
                        $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                        continue;
                    }
                }

                $validBreaks[] = [
                    'i' => $i,
                    'start' => $bs,
                    'end' => $be,
                ];
            }

            usort($validBreaks, fn($a, $b) => strcmp($a['start'], $b['start']));
            for ($k = 0; $k < count($validBreaks) - 1; $k++) {
                if ($validBreaks[$k]['end'] > $validBreaks[$k + 1]['start']) {
                    $v->errors()->add("breaks.{$validBreaks[$k + 1]['i']}.start", '休憩時間が重複しています');
                    break;
                }
            }

            if ($start && $end) {
                $workTotal = $start->diffInMinutes($end);

                $breakTotal = 0;
                foreach ($validBreaks as $b) {
                    $bsC = Carbon::createFromFormat('H:i', $b['start']);
                    $beC = Carbon::createFromFormat('H:i', $b['end']);
                    $breakTotal += $bsC->diffInMinutes($beC);
                }

                if ($breakTotal >= $workTotal) {
                    $v->errors()->add('work_end_at', '休憩時間が勤務時間を超えています');
                }
            }
        });
    }
}
