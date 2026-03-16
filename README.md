# coachtech勤怠管理アプリ(模擬案件2)
- 一般ユーザーと管理者の2種類のユーザーを持つ勤怠管理システムです。
- 一般ユーザーは出勤・休憩・退勤の打刻を行い、自身の勤怠情報を確認できます。また、勤怠情報に誤りがある場合は修正申請を行うことができます。
- 管理者は全ユーザーの勤怠状況を確認でき、勤怠修正申請の承認を行うことができます。スタッフごとの月次勤怠情報はCSVとして出力することも可能です。
- 会員登録後はメール認証完了後にログイン可能な仕様です。
- メール認証には MailHog を使用しています。

## 主な機能

- ユーザー登録（メール認証付き）
- ログイン機能（一般ユーザー / 管理者）
- 出勤 / 休憩 / 退勤打刻
- 勤怠一覧表示（月次）
- 勤怠詳細表示
- 勤怠修正申請
- 修正申請承認（管理者）
- スタッフ別勤怠管理（管理者）
- CSV出力


## 環境構築
**初回起動手順**
1. DockerDesktopを立ち上げる
2. `git clone git@github.com:lesser-fam/Mock-case2.git`
3. `cd Mock-case2`
4. `make bootstrap`

※このコマンドで以下が自動実行されます。
- Dockerコンテナ作成
- composer install
- .env作成
- アプリケーションキー作成
- storageディレクトリのシンボリックリンク作成
- マイグレーション実行
- シーダー実行

# 主なMakeコマンド
- コンテナ起動
`make up`

- コンテナ停止
`make stop`

- コンテナ再起動
`make restart`

- データベース再作成
`make fresh`

- キャッシュクリア
`make cache`

※ 権限エラーが出た場合のみ実行
```bash
docker-compose exec php bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```


# ダミーデータ
※ 本アプリでは、動作確認用としてシーダーにより初期データを作成しています。
- 管理者ユーザー：2人
- 一般ユーザー：6人
- 勤怠記録情報：前月～翌月の3ヶ月分

- 勤怠データには出勤・休憩・退勤が登録された状態になっています。

**管理者ユーザー**
| id | name    | email              |
|----|---------|--------------------|
| 1  | 管理者A  | admin1@example.com |
| 2  | 管理者B  | admin2@example.com |


**一般ユーザー**
| id | name      | email             |
|----|-----------|-------------------|
| 3  | 山田 太郎  | user1@example.com |
| 4  | 佐藤 次郎  | user2@example.com |
| 5  | 鈴木 三郎  | user3@example.com |
| 6  | 高橋 四郎  | user4@example.com |
| 7  | 伊藤 五郎  | user5@example.com |
| 8  | 渡辺 六郎  | user6@example.com |

※ パスワードは全ユーザー共通で「password」です



# テスト実行

- 本アプリのテストは'.env.testing'を使用して実行します。テスト実行前に、テスト用環境ファイルを作成してください。
```bash
cp src/.env.testing.example src/.env.testing
```
- 次に、MySQLコンテナ内でテスト用データベースを作成し、laravel_userにdemo_testへの権限を付与してください。
```bash
docker-compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS demo_test; GRANT ALL PRIVILEGES ON demo_test.* TO 'laravel_user'@'%'; FLUSH PRIVILEGES";
```
- その後、テストを実行してください。
```bash
docker-compose exec php php artisan test
```

※ すべて Feature Test として実装しています。

**実装済みテスト一覧**
```text
01 認証機能(一般ユーザー)
    - UserRegisterTest

02 ログイン認証機能(一般ユーザー)
    - UserLoginTest

03 ログイン認証機能(管理者)
    - AdminLoginTest

04 日時取得機能
    - UserAttendanceDateTest

05 ステータス確認機能
    - UserAttendanceStatusTest

06 出勤機能
    - UserWorkStartTest

07 休憩機能
    - UserBreakTest

08 退勤機能
    - UserWorkEndTest

09 勤怠一覧情報取得機能(一般ユーザー)
    - UserAttendanceListTest

10 勤怠詳細情報取得機能(一般ユーザー)
    - UserAttendanceDetailTest

11 勤怠一覧情報修正機能(一般ユーザー)
    - UserAttendanceCorrectionRequestTest

12 勤怠一覧情報取得機能(管理者)
    - AdminAttendanceListTest

13 勤怠詳細情報取得・修正機能(管理者)
    - AdminAttendanceDetailUpdateTest

14 ユーザー情報取得機能(管理者)
    - AdminStaffTest

15 勤怠情報修正機能(管理者)
    - AdminCorrectionApprovalTest

16 メール認証機能
    - UserEmailVerificationTest

17 スタッフ別月次勤怠CSV出力
    - AdminStaffMonthCsvTest
```


## 使用技術
- PHP 8.1.34
- Laravel 8.83.29
- MySQL 8.0.26
- Laravel Fortify (認証)
- MailHog (メール認証)


## ER図
![ER図](/erd.png)


## URL
- 会員登録(一般ユーザー)：http://localhost/register
- ログイン(管理者)：http://localhost/admin/login
- phpMyAdmin：http://localhost:8080/
- MailHog：http://localhost:8025


## 補足
- 会員登録後はメール認証が完了するまでログイン不可になっています。
- メール認証はMailHogを使用して確認できます。
