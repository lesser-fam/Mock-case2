<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('role', 'user')
            ->orderBy('id')
            ->get(['id']);

        if ($users->isEmpty()) {
            $this->command?->warn('role=user のユーザーがいないのでスキップしました。');
            return;
        }

        // 期間：前月1日〜翌月末日（3ヶ月分）
        $from = now()->startOfMonth()->subMonth()->startOfDay();
        $to   = now()->startOfMonth()->addMonth()->endOfMonth()->endOfDay();

        $userIds = $users->pluck('id')->all();

        DB::transaction(function () use ($userIds, $from, $to) {

            // 対象期間の既存データを掃除（デモ作り直し用）
            $attendanceIds = Attendance::query()
                ->whereIn('user_id', $userIds)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->pluck('id')
                ->all();

            if (!empty($attendanceIds)) {
                BreakTime::query()->whereIn('attendance_id', $attendanceIds)->delete();
                Attendance::query()->whereIn('id', $attendanceIds)->delete();
            }

            // 月ごとの「平日休み」をランダムに0〜2日作る（各ユーザーごと）
            $weekdayOffMap = $this->buildWeekdayOffMap($userIds, $from->copy(), $to->copy());

            // 日ごとに作る
            for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
                $dateStr = $d->toDateString();

                // 勤務するか判定
                foreach ($userIds as $userId) {
                    $isWeekend = in_array($d->dayOfWeekIso, [6, 7], true);
                    $isWeekdayOff = isset($weekdayOffMap[$userId][$dateStr]);

                    $works = false;

                    if (!$isWeekend) {
                        // - 平日：基本勤務、ただし平日休み指定があれば休み
                        $works = !$isWeekdayOff;
                    } else {
                        if ($d->dayOfWeekIso === 6) {
                            $works = (random_int(1, 100) <= 10);
                        } else {
                            $works = (random_int(1, 100) <= 3);
                        }
                    }

                    if (!$works) {
                        Attendance::create([
                            'user_id' => $userId,
                            'date' => $dateStr,
                            'status' => 'outside',
                            'work_start_at' => null,
                            'work_end_at' => null,
                            'memo' => null,
                        ]);
                        continue;
                    }

                    // 勤務時間（安全側）
                    $workStartAt = $this->randomTimeOnDate($d, '08:30', '10:00');
                    $workEndAt   = $this->randomTimeOnDate($d, '17:00', '19:30');

                    // 退勤が早すぎると変なので最低6時間は確保
                    if ($workEndAt->lte($workStartAt->copy()->addHours(6))) {
                        $workEndAt = $workStartAt->copy()->addHours(8)->addMinutes(random_int(0, 60));
                    }

                    // たまに残業（8%）
                    if (random_int(1, 100) <= 8) {
                        $workEndAt = $this->randomTimeOnDate($d, '20:00', '21:30');
                        if ($workEndAt->lte($workStartAt->copy()->addHours(6))) {
                            $workEndAt = $workStartAt->copy()->addHours(10);
                        }
                    }

                    $attendance = Attendance::create([
                        'user_id' => $userId,
                        'date' => $dateStr,
                        'status' => 'finished',
                        'work_start_at' => $workStartAt,
                        'work_end_at' => $workEndAt,
                        'memo' => $this->maybeMemo(),
                    ]);

                    // 休憩：0〜2本（基本は1本）
                    $this->createBreaksSafely($attendance->id, $workStartAt, $workEndAt);
                }
            }
        });

        $this->command?->info('Attendance demo data seeded (prev/current/next month).');
    }

    /**
     * 月ごとに、各ユーザーに平日休みを0〜2日割り当てる
     * @return array<int, array<string, true>> [userId => [dateStr => true]]
     */
    private function buildWeekdayOffMap(array $userIds, Carbon $from, Carbon $to): array
    {
        $map = [];
        foreach ($userIds as $uid) {
            $map[$uid] = [];
        }

        // 対象範囲に含まれる「月の一覧」
        $months = [];
        $m = $from->copy()->startOfMonth();
        $endMonth = $to->copy()->startOfMonth();
        while ($m->lte($endMonth)) {
            $months[] = $m->copy();
            $m->addMonth();
        }

        foreach ($months as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            // 平日だけ抽出
            $weekdays = [];
            for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
                if (in_array($d->dayOfWeekIso, [6, 7], true)) continue;
                $weekdays[] = $d->toDateString();
            }

            foreach ($userIds as $uid) {
                $offCount = random_int(0, 2); // 月に0〜2日
                if ($offCount === 0 || empty($weekdays)) continue;

                shuffle($weekdays);
                $picked = array_slice($weekdays, 0, $offCount);
                foreach ($picked as $dateStr) {
                    $map[$uid][$dateStr] = true;
                }
            }
        }

        return $map;
    }

    private function randomTimeOnDate(Carbon $date, string $fromHm, string $toHm): Carbon
    {
        [$fh, $fm] = array_map('intval', explode(':', $fromHm));
        [$th, $tm] = array_map('intval', explode(':', $toHm));

        $fromMin = $fh * 60 + $fm;
        $toMin   = $th * 60 + $tm;

        if ($toMin < $fromMin) {
            $toMin = $fromMin;
        }

        $pick = random_int($fromMin, $toMin);
        $h = intdiv($pick, 60);
        $m = $pick % 60;

        return $date->copy()->setTime($h, $m, 0);
    }

    private function maybeMemo(): ?string
    {
        // 15%くらいでメモを入れる
        if (random_int(1, 100) > 15) return null;

        $samples = [
            '外出あり',
            '来客対応',
            '研修',
            '体調のため早退',
            '社内作業',
        ];
        return $samples[array_rand($samples)];
    }

    private function createBreaksSafely(int $attendanceId, Carbon $workStartAt, Carbon $workEndAt): void
    {
        // 勤務時間が短いなら休憩なし
        $workSpan = $workStartAt->diffInMinutes($workEndAt);
        if ($workSpan < 6 * 60) {
            return;
        }

        // 本数：0(15%) / 1(75%) / 2(10%)
        $r = random_int(1, 100);
        $breakCount = ($r <= 15) ? 0 : (($r <= 90) ? 1 : 2);

        if ($breakCount === 0) return;

        // 休憩1：昼あたり（45〜75分）
        $b1Start = $this->clampBreakStart(
            $workStartAt,
            $workEndAt,
            $this->randomTimeOnDate($workStartAt->copy(), '12:00', '13:30')
        );
        $b1Dur = random_int(45, 75);
        $b1End = $b1Start->copy()->addMinutes($b1Dur);

        if ($b1End->gte($workEndAt)) {
            // 終業を超えるなら、終業の90分前に寄せる
            $b1End = $workEndAt->copy()->subMinutes(5);
            $b1Start = $b1End->copy()->subMinutes(min($b1Dur, 60));
            if ($b1Start->lte($workStartAt)) return;
        }

        BreakTime::create([
            'attendance_id' => $attendanceId,
            'break_start_at' => $b1Start,
            'break_end_at' => $b1End,
        ]);

        if ($breakCount === 1) return;

        // 休憩2：午後（10〜20分）
        $b2Start = $this->clampBreakStart(
            $workStartAt,
            $workEndAt,
            $this->randomTimeOnDate($workStartAt->copy(), '15:00', '16:30')
        );
        $b2Dur = random_int(10, 20);
        $b2End = $b2Start->copy()->addMinutes($b2Dur);

        // 休憩2が休憩1と被る/終業超えなら、作らない
        if ($b2Start->lt($b1End->copy()->addMinutes(5))) return;
        if ($b2End->gte($workEndAt)) return;

        BreakTime::create([
            'attendance_id' => $attendanceId,
            'break_start_at' => $b2Start,
            'break_end_at' => $b2End,
        ]);
    }

    private function clampBreakStart(Carbon $workStartAt, Carbon $workEndAt, Carbon $candidate): Carbon
    {
        // 勤務開始+30分 〜 勤務終了-30分 に収める
        $min = $workStartAt->copy()->addMinutes(30);
        $max = $workEndAt->copy()->subMinutes(30);

        if ($candidate->lt($min)) return $min;
        if ($candidate->gt($max)) return $max;
        return $candidate;
    }
}
