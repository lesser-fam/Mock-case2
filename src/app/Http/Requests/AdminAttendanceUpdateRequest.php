<?php

namespace App\Http\Requests;

use App\Support\Validation\AttendanceTimeValidator;
use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'work_start_at' => 'required|date_format:H:i',
            'work_end_at'   => 'required|date_format:H:i',
            'memo'          => 'required|max:255',

            'breaks'               => 'nullable|array',
            'breaks.*.start'       => 'nullable|date_format:H:i|required_with:breaks.*.end',
            'breaks.*.end'         => 'nullable|date_format:H:i|required_with:breaks.*.start',
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
            $bag = $v->errors();

            /** @var AttendanceTimeValidator $logic */
            $logic = app(AttendanceTimeValidator::class);
            $logic->validate($this->all(), $bag);
        });
    }
}
