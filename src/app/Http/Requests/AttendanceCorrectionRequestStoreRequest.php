<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequestStoreRequest extends FormRequest
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
            'work_start_at' => ['required', 'date_format:H:i'],
            'work_end_at'   => ['required', 'date_format:H:i'],
            'memo'          => ['required', 'string'],

            'breaks'                => ['array'],
            'breaks.*.start'        => ['nullable', 'date_format:H:i'],
            'breaks.*.end'          => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'memo.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $start = $this->input('work_start_at');
            $end   = $this->input('work_end_at');

            // 1) 出勤/退勤の前後関係
            if ($start && $end && $start >= $end) {
                $v->errors()->add('work_start_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                // 入力が片方だけなら、その行は無効
                if (($bs && !$be) || (!$bs && $be)) {

                    continue;
                }
                if (!$bs || !$be) {
                    continue;
                }

                // 休憩が出勤より前、退勤より後（開始）
                if ($start && $bs < $start) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }
                if ($end && $bs > $end) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 3) 休憩終了が退勤より後
                if ($end && $be > $end) {
                    $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    continue;
                }

                // 休憩の前後（開始 >= 終了 も不正として扱う）
                if ($bs >= $be) {
                    $v->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                }
            }
        });
    }
}
