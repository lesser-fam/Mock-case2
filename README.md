# coachtech勤怠管理アプリ(模擬案件2)
- 
- 会員登録後はメール認証完了後にログイン可能な仕様です。
- メール認証には MailHog を使用しています。


## 環境構築
**初回起動手順**
1. DockerDesktopを立ち上げる
2. `git clone git@github.com:lesser-fam/Mock-case2.git`
3. `cd Mock-case2`
4. `make bootstrap`

※ 権限エラーが出た場合のみ実行
```bash
docker-compose exec php bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```


# テストユーザー
※ 本アプリでは、動作確認用として、以下の初期データをシーダーで作成しています。
- 管理者ユーザー：2人
- 一般ユーザー：6人
- 勤怠記録情報：(件)

**管理者ユーザー**
| id | name  | email             | password |
|----|-------|-------------------|----------|
| 1  | 太郎  | test1@example.com | password |
| 2  | 次郎  | test2@example.com | password |

**一般ユーザー**
| id | name  | email             | password |
|----|-------|-------------------|----------|
| 3  | 三郎  | test3@example.com | password |
| 4  | 四郎  | test4@example.com | password |
| 5  | 五郎  | test5@example.com | password |
| 6  | 六郎  | test6@example.com | password |
| 7  | 七郎  | test7@example.com | password |
| 8  | 八郎  | test8@example.com | password |

※ パスワードは全ユーザー共通で「password」です




# テスト実行

※ テスト実行前に、MySQLのrootユーザーでテスト用データベースを作成してください。
```bash
docker-compose exec mysql mysql -u root -p
```
```sql
CREATE DATABASE demo_test;
```
※ テスト実行時は'.env.testing'を使用し、すべて Feature Test として実装しています。

```bash
php artisan test
```

**実装済みテスト一覧**
```text
01 認証機能(一般ユーザー)
    - Test

02 ログイン認証機能(一般ユーザー)
    - Test

03 ログイン認証機能(管理者)
    - Test

04 日時取得機能
    - Test

05 ステータス確認機能
    - Test

06 出勤機能
    - Test

07 休憩機能
    - Test

08 退勤機能
    - Test

09 勤怠一覧情報取得機能(一般ユーザー)
    - Test

10 勤怠詳細情報取得機能(一般ユーザー)
    - Test

11 勤怠一覧情報修正機能(一般ユーザー)
    - Test

12 勤怠一覧情報取得機能(管理者)
    - Test

13 勤怠詳細情報取得・修正機能(管理者)
    - Test

14 ユーザー情報取得機能(管理者)
    - Test

15 勤怠情報修正機能(管理者)
    - Test

16 メール認証機能
    - AuthEmailVerificationTest
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
