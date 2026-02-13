@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="container--narrow request-list">
    <h1 class="request-list__title">申請一覧</h1>

    <div class="request-list__tabs">
        <a class="request-list__tab {{ $status === 'pending' ? 'is-active' : '' }}"
            href="{{ route('request.list', ['status' => 'pending']) }}">
            承認待ち
        </a>
        <a class="request-list__tab {{ $status === 'approved' ? 'is-active' : '' }}"
            href="{{ route('request.list', ['status' => 'approved']) }}">
            承認済み
        </a>
    </div>

    <div class="request-list__stack {{ $status === 'pending' ? 'is-pending' : 'is-approved' }}">
        <div class="request-list__table">
            <div class="request-list__table-row request-list__table-head">
                <div>状態</div>
                <div>名前</div>
                <div>対象日時</div>
                <div>申請理由</div>
                <div>申請日時</div>
                <div>詳細</div>
            </div>

            @foreach ($requests as $r)
            @php
            $statusLabel = $r->status === 'pending' ? '承認待ち' : '承認済み';
            $target = $r->date ? \Carbon\Carbon::parse($r->date)->format('Y/m/d') : '';
            $applied = $r->created_at ? $r->created_at->format('Y/m/d') : '';
            $name = $r->applicant?->name ?? '';
            @endphp

            <div class="request-list__table-row">
                <div>{{ $statusLabel }}</div>
                <div>{{ $name }}</div>
                <div>{{ $target }}</div>
                <div class="request-list__table-memo">{{ $r->memo ?? '' }}</div>
                <div>{{ $applied }}</div>
                <div>
                    @if (auth()->user()->role === 'admin')
                    <a class="btn btn--list-detail"
                        href="{{ route('request.approve.show', ['attendance_correction_request_id' => $r->id]) }}">
                        詳細
                    </a>
                    @else
                    <a class="btn btn--list-detail"
                        href="{{ route('attendance.detail', ['id' => $r->attendance_id]) }}">
                        詳細
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="pager">
        {{ $requests->links() }}
    </div>
</div>
@endsection