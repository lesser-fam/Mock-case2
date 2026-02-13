@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/forms/form.css') }}">
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <form method="POST" action="{{ route('attendance.detail.request', ['id' => $attendance->id]) }}">
        @csrf

        @php
        $name = auth()->user()->name ?? '';
        $parts = preg_split('/\s+/u', trim($name));
        $sei = $parts[0] ?? $name;
        $mei = $parts[1] ?? '';
        @endphp

        <table class="attendance-detail__table">
            <tr class="attendance-detail__table-row is-4col">
                <th class="attendance-detail__table-key">名前</th>
                <td class="attendance-detail__cell">
                    <span class="name-split cell-box">
                        <span class="name-split__sei">{{ $sei }}</span>
                        <span class="name-split__mei">{{ $mei }}</span>
                    </span>
                </td>
                <td class="attendance-detail__cell is-empty"></td>
                <td class="attendance-detail__cell is-empty"></td>
            </tr>

            <tr class="attendance-detail__table-row is-4col">
                <th class="attendance-detail__table-key">日付</th>
                <td class="attendance-detail__cell">
                    <span class="cell-box">{{ $yearLabel }}</span>
                </td>
                <td class="attendance-detail__cell is-empty"></td>
                <td class="attendance-detail__cell">
                    <span class="cell-box">{{ $mdLabel }}</span>
                </td>
            </tr>

            @php
            $workHasError = !$isPending && ($errors->has('work_start_at') || $errors->has('work_end_at'));
            @endphp

            <tr class="attendance-detail__table-row is-4col {{ $workHasError ? 'has-error' : '' }}">
                <th class="attendance-detail__table-key">出勤・退勤</th>
                <td class="attendance-detail__cell">
                    @if ($isPending)
                    <span class="time-display">{{ $displayWorkStart ?? '' }}</span>
                    @else
                    <input class="time-input" type="time" name="work_start_at" value="{{ old('work_start_at', $attendance->work_start_at?->format('H:i')) }}">
                    @endif
                </td>
                <td class="attendance-detail__cell sep">〜</td>
                <td class="attendance-detail__cell">
                    @if ($isPending)
                    <span class="time-display">{{ $displayWorkEnd ?? '' }}</span>
                    @else
                    <input class="time-input" type="time" name="work_end_at" value="{{ old('work_end_at', $attendance->work_end_at?->format('H:i')) }}">
                    @endif
                </td>
            </tr>

            @if ($workHasError)
            @php
            $msgs = array_merge($errors->get('work_start_at'), $errors->get('work_end_at'));
            $msgs = array_values(array_unique($msgs));
            @endphp
            <tr class="attendance-detail__table-row is-4col is-error-row">
                <th class="attendance-detail__table-key"></th>
                <td class="attendance-detail__cell row-error-wide" colspan="3">
                    @foreach ($msgs as $msg)
                    <p class="form__error">
                        {{ $msg }}
                    </p>
                    @endforeach
                </td>
            </tr>
            @endif

            @foreach ($breakRows as $i => $row)
            @php
            $breakHasError = !$isPending && ($errors->has("breaks.$i.start") || $errors->has("breaks.$i.end"));
            @endphp
            <tr class="attendance-detail__table-row is-4col {{ $breakHasError ? 'has-error' : '' }}">
                <th class="attendance-detail__table-key">休憩{{ $i === 0 ? '' : $i + 1 }}</th>
                <td class="attendance-detail__cell">
                    @if ($isPending)
                    <span class="time-display">{{ $row['start'] ?? '' }}</span>
                    @else
                    <input class="time-input" type="time" name="breaks[{{ $i }}][start]" value="{{ old("breaks.$i.start", $row['start']) }}">
                    @endif
                </td>
                <td class="attendance-detail__cell sep">～</td>
                <td class="attendance-detail__cell">
                    @if ($isPending)
                    <span class="time-display">{{ $row['end'] ?? '' }}</span>
                    @else
                    <input class="time-input" type="time" name="breaks[{{ $i }}][end]" value="{{ old("breaks.$i.end", $row['end']) }}">
                    @endif
                </td>
            </tr>

            @php
            $breakHasError = !$isPending && ($errors->has("breaks.$i.start") || $errors->has("breaks.$i.end") || $errors->has("breaks.$i"));
            @endphp

            @if ($breakHasError)
            @php
            $msg = $errors->first("breaks.$i");
            @endphp
            <tr class="attendance-detail__table-row is-4col is-error-row">
                <th class="attendance-detail__table-key"></th>
                <td class="attendance-detail__cell row-error-wide" colspan="3">
                    @if ($msg)
                    <p class="form__error">
                        {{ $msg }}
                    </p>
                    @endif
                </td>
            </tr>
            @endif
            @endforeach

            <tr class="attendance-detail__table-row is-4col">
                <th class="attendance-detail__table-key">備考</th>
                <td class="attendance-detail__cell memo-cell" colspan="3">
                    @if ($isPending)
                    <span class="memo-display">{{ $displayMemo }}</span>
                    @else
                    <textarea name="memo" rows="4">{{ old('memo', $displayMemo) }}</textarea>
                    @error('memo')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                    @endif
                </td>
            </tr>
        </table>

        <div class="detail-actions">
            @if($isPending)
            <p class="notice">*承認待ちのため修正はできません</p>
            @else
            <button type="submit" class="btn btn--approve btn--black">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection