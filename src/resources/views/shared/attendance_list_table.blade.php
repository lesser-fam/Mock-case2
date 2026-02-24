@php
$listRouteName = $listRouteName ?? 'attendance.month.index';
$detailRouteName = $detailRouteName ?? 'attendance.detail.show';
$listRouteParams = $listRouteParams ?? [];
$detailRouteParams = $detailRouteParams ?? [];
@endphp

<div class="attendance-list__pager">
    <a href="{{ route($listRouteName, array_merge($listRouteParams ?? [], ['month' => $prevMonth])) }}">←前月</a>

    <span class="attendance-list__pager-center">
        <form class="pager-picker" action="{{ route($listRouteName, $listRouteParams ?? []) }}" method="GET">
            <input
                id="month-picker"
                class="pager-picker__input"
                type="month"
                name="month"
                value="{{ $baseMonth->format('Y-m') }}"
                aria-label="月を選択"
                onchange="this.form.submit()">
            <button
                class="pager-picker__btn"
                type="button"
                aria-label="月を選択"
                onclick="document.getElementById('month-picker').showPicker?.();
                        document.getElementById('month-picker').focus();
                        document.getElementById('month-picker').click();">
                📆
            </button>
        </form>
        <span class="attendance-list__pager-label">{{ $baseMonth->format('Y/m') }}</span>
    </span>

    <a href="{{ route($listRouteName, array_merge($listRouteParams ?? [], ['month' => $nextMonth])) }}">翌月→</a>
</div>

<div class="attendance-list__table">
    <div class="attendance-list__table-row attendance-list__table-head">
        <div>日付</div>
        <div>出勤</div>
        <div>退勤</div>
        <div>休憩</div>
        <div>合計</div>
        <div>詳細</div>
    </div>

    @foreach ($days as $row)
    @php
    $a = $row['attendance'];
    @endphp

    <div class="attendance-list__table-row">
        <div>{{ $row['dateLabel'] }}</div>
        <div>{{ $row['start'] }}</div>
        <div>{{ $row['end'] }}</div>
        <div>{{ $row['breakLabel'] }}</div>
        <div>{{ $row['workLabel'] }}</div>
        <div class="attendance-list__detail">
            @if($a)
            <a class="btn btn--list-detail" href="{{ route($detailRouteName, array_merge($detailRouteParams, ['id' => $a->id])) }}">詳細</a>
            @else
            <span class="btn btn--list-detail is-disable">詳細</span>
            @endif
        </div>
    </div>
    @endforeach
</div>