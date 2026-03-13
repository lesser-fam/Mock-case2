@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="container--narrow request-list">
    <h1 class="request-list__title">申請一覧</h1>

    <div class="request-list__tabs">
        <a class="request-list__tab {{ $status === 'pending' ? 'is-active' : '' }}"
            href="{{ route('request.index', ['status' => 'pending']) }}">
            承認待ち
        </a>
        <a class="request-list__tab {{ $status === 'approved' ? 'is-active' : '' }}"
            href="{{ route('request.index', ['status' => 'approved']) }}">
            承認済み
        </a>
    </div>

    <div class="request-list__stack {{ $status === 'pending' ? 'is-pending' : 'is-approved' }}">
        <table class="request-list__table">
            <thead>
                <tr class="request-list__table-row request-list__table-head">
                    <th scope="col">状態</th>
                    <th scope="col">名前</th>
                    <th scope="col">対象日時</th>
                    <th scope="col">申請理由</th>
                    <th scope="col">申請日時</th>
                    <th scope="col">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $r)
                @php
                $statusLabel = $r->status === 'pending' ? '承認待ち' : '承認済み';
                $target = $r->date ? \Carbon\Carbon::parse($r->date)->format('Y/m/d') : '';
                $applied = $r->created_at ? $r->created_at->format('Y/m/d') : '';
                $name = $r->applicant?->name ?? '';
                @endphp

                <tr class="request-list__table-row">
                    <td>{{ $statusLabel }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $target }}</td>
                    <td class="request-list__table-memo" title="{{ $r->memo ?? '' }}">{{ $r->memo ?? '' }}</td>
                    <td>{{ $applied }}</td>
                    <td>
                        @if ($isAdmin)
                        <a class="btn btn--list-detail"
                            href="{{ route('request.approve.show', ['attendance_correction_request_id' => $r->id]) }}">詳細</a>
                        @else
                        @if ($r->status === 'approved')
                        <a class="btn btn--list-detail" href="{{ route('request.user.show', ['attendance_correction_request_id' => $r->id]) }}">詳細</a>
                        @else
                        <a class="btn btn--list-detail" href="{{ route('attendance.detail.show', ['id' =>$r->attendance_id]) }}">詳細</a>
                        @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="request-list__pager">
        @if ($requests->onFirstPage())
        <span class="pager-btn is-disabled">← 前</span>
        @else
        <a class="pager-btn" href="{{ $requests->previousPageUrl() }}">← 前</a>
        @endif

        @if ($requests->hasMorePages())
        <a class="pager-btn" href="{{ $requests->nextPageUrl() }}">次 →</a>
        @else
        <span class="pager-btn is-disabled">次 →</span>
        @endif
    </div>
</div>
@endsection