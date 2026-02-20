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

    <tr class="attendance-detail__table-row is-4col">
        <th class="attendance-detail__table-key">出勤・退勤</th>
        <td class="attendance-detail__cell">
            <span class="time-display">{{ $displayWorkStart ?? '' }}</span>
        </td>
        <td class="attendance-detail__cell sep">〜</td>
        <td class="attendance-detail__cell">
            <span class="time-display">{{ $displayWorkEnd ?? '' }}</span>
        </td>
    </tr>

    @foreach ($breakRows as $i => $row)
    <tr class="attendance-detail__table-row is-4col">
        <th class="attendance-detail__table-key">休憩{{ $i === 0 ? '' : $i + 1 }}</th>
        <td class="attendance-detail__cell">
            <span class="time-display">{{ $row['start'] ?? '' }}</span>
        </td>
        <td class="attendance-detail__cell sep">～</td>
        <td class="attendance-detail__cell">
            <span class="time-display">{{ $row['end'] ?? '' }}</span>
        </td>
    </tr>
    @endforeach

    <tr class="attendance-detail__table-row is-4col">
        <th class="attendance-detail__table-key">備考</th>
        <td class="attendance-detail__cell memo-cell" colspan="3">
            <span class="memo-display">{{ $displayMemo ?? '' }}</span>
        </td>
    </tr>
</table>