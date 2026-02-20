<div class="container--narrow attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    <form method="POST" action="{{ $formAction }}">
        @csrf

        <table class="attendance-detail__table">
            <tr class="attendance-detail__table-row is-4col">
                <th class="attendance-detail__table-key">名前</th>
                <td class="attendance-detail__cell">
                    <span class="name-split cell-box">
                        <span class="name-split__sei">{{ $person?->sei ?? '' }}</span>
                        <span class="name-split__mei">{{ $person?->mei ?? '' }}</span>
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
            $workHasError = $canEdit && ($errors->has('work_start_at') || $errors->has('work_end_at'));
            @endphp

            <tr class="attendance-detail__table-row is-4col {{ $workHasError ? 'has-error' : '' }}">
                <th class="attendance-detail__table-key">出勤・退勤</th>
                <td class="attendance-detail__cell">
                    @if ($canEdit)
                    <input class="time-input" type="time" name="work_start_at"
                        value="{{ old('work_start_at', $attendance->work_start_at?->format('H:i')) }}">
                    @else
                    <span class="time-display">{{ $displayWorkStart ?? '' }}</span>
                    @endif
                </td>
                <td class="attendance-detail__cell sep">〜</td>
                <td class="attendance-detail__cell">
                    @if ($canEdit)
                    <input class="time-input" type="time" name="work_end_at"
                        value="{{ old('work_end_at', $attendance->work_end_at?->format('H:i')) }}">
                    @else
                    <span class="time-display">{{ $displayWorkEnd ?? '' }}</span>
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
                    <p class="form__error">{{ $msg }}</p>
                    @endforeach
                </td>
            </tr>
            @endif

            @foreach ($breakRows as $i => $row)
            @php
            $breakHasError = $canEdit && ($errors->has("breaks.$i.start") || $errors->has("breaks.$i.end") || $errors->has("breaks.$i"));
            @endphp

            <tr class="attendance-detail__table-row is-4col {{ $breakHasError ? 'has-error' : '' }}">
                <th class="attendance-detail__table-key">休憩{{ $i === 0 ? '' : $i + 1 }}</th>
                <td class="attendance-detail__cell">
                    @if ($canEdit)
                    <input class="time-input" type="time" name="breaks[{{ $i }}][start]"
                        value="{{ old("breaks.$i.start", $row['start']) }}">
                    @else
                    <span class="time-display">{{ $row['start'] ?? '' }}</span>
                    @endif
                </td>
                <td class="attendance-detail__cell sep">～</td>
                <td class="attendance-detail__cell">
                    @if ($canEdit)
                    <input class="time-input" type="time" name="breaks[{{ $i }}][end]"
                        value="{{ old("breaks.$i.end", $row['end']) }}">
                    @else
                    <span class="time-display">{{ $row['end'] ?? '' }}</span>
                    @endif
                </td>
            </tr>

            @if ($breakHasError)
            @php
            $msg = $errors->first("breaks.$i");
            if (!$msg) $msg = $errors->first("breaks.$i.start") ?: $errors->first("breaks.$i.end");
            @endphp
            <tr class="attendance-detail__table-row is-4col is-error-row">
                <th class="attendance-detail__table-key"></th>
                <td class="attendance-detail__cell row-error-wide" colspan="3">
                    @if ($msg)
                    <p class="form__error">{{ $msg }}</p>
                    @endif
                </td>
            </tr>
            @endif
            @endforeach

            <tr class="attendance-detail__table-row is-4col">
                <th class="attendance-detail__table-key">備考</th>
                <td class="attendance-detail__cell memo-cell" colspan="3">
                    @if ($canEdit)
                    <textarea name="memo" rows="4">{{ old('memo', $displayMemo) }}</textarea>
                    @error('memo')
                    <p class="form__error">{{ $message }}</p>
                    @enderror
                    @else
                    <span class="memo-display">{{ $displayMemo }}</span>
                    @endif
                </td>
            </tr>
        </table>

        <div class="detail-actions">
            @if($canEdit)
            <button type="submit" class="btn btn--approve btn--black">修正</button>
            @else
            <div class="detail-actions__notice">
                @if (!empty($pendingLinkUrl))
                <a class="notice-link" href="{{ $pendingLinkUrl }}">申請内容を見る</a>
                @endif
                <p class="notice">{{ $cannotEditMessage ?? '' }}</p>
            </div>
            @endif
        </div>
    </form>
</div>