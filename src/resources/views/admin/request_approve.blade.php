@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container--narrow attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    @include('shared.attendance_detail_table', [
    'person' => $request->applicant,
    'yearLabel' => $yearLabel,
    'mdLabel' => $mdLabel,
    'displayWorkStart' => $displayWorkStart,
    'displayWorkEnd' => $displayWorkEnd,
    'breakRows' => $breakRows,
    'displayMemo' => $displayMemo,
    ])

    <div class="detail-actions">
        @if($isPending)
        <form id="approveForm" method="POST" action="{{ route('request.approve', ['attendance_correction_request_id' => $request->id]) }}">
            @csrf
            <button type="submit" class="btn btn--approve btn--black" id="approveBtn">承認</button>
        </form>
        @else
        <p class="btn btn--approve btn--gray">承認済み</p>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('approveForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('approveBtn');
            btn.disabled = true;

            const token = form.querySelector('input[name="_token"]').value;

            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
            });

            if (res.ok) {
                btn.textContent = '承認済み';
                btn.classList.add('is-disable');
                return;
            }

            btn.disabled = false;
            form.submit();
        });
    });
</script>
@endsection