@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <form method="POST" action="{{ route('attendance.detail.request', ['id' => $attendance->id]) }}">
        @csrf

        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ auth()->user()->name }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td class="date-split">
                    <span class="date-split__year">{{ $yearLabel }}</span>
                    <span class="date-split__md">{{ $mdLabel }}</span>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td class="time-range">
                    @if ($isPending)
                    <span>{{ $displayWorkStart ?? '' }}</span>
                    <span>～</span>
                    <span>{{ $displayWorkEnd ?? '' }}</span>
                    @else
                    <input type="time" name="work_start_at" value="{{ old('work_start_at', $attendance->work_start_at?->format('H:i')) }}">
                    <span>〜</span>
                    <input type="time" name="work_end_at" value="{{ old('work_end_at', $attendance->work_end_at?->format('H:i')) }}">
                    @error('work_start_at')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                    @error('work_end_at')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                    @endif
                </td>
            </tr>

            @foreach ($breakRows as $i => $row)
            <tr>
                <th>休憩{{ $i === 0 ? '' : $i + 1 }}</th>
                <td class="time-range">
                    @if ($isPending)
                    <span>{{ $row['start'] ?? '' }}</span>
                    <span>～</span>
                    <span>{{ $row['end'] ?? '' }}</span>
                    @else
                    <input type="time" name="breaks[{{ $i }}][start]" value="{{ old("breaks.$i.start", $row['start']) }}">
                    <span>〜</span>
                    <input type="time" name="breaks[{{ $i }}][end]" value="{{ old("breaks.$i.end", $row['end']) }}">
                    @error('break_start_at')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                    @error('break_end_at')
                    <p class="form__error">
                        {{ $message }}
                    </p>
                    @enderror
                    @endif
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>
                    @if ($isPending)
                    <span>{{ $displayMemo }}</span>
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
            <button type="submit" class="btn btn--small">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection