# LureX データベース設計

最終更新: 2026-11-02  
ステータス: 確定

---

## 用語解説

| 用語 | 意味 |
|---|---|
| PK（Primary Key） | テーブルの各行を一意に識別するID。重複不可 |
| FK（Foreign Key） | 他のテーブルのPKを参照して「関係」を持つカラム |
| UNIQUE | 値の重複を禁止する制約 |
| enum | あらかじめ決めた値の中からしか入れられない型 |
| nullable | 空（NULL）を許可するカラム |
| ソフトデリート | 物理削除せず `deleted_at` に日時を入れて「削除済み」とする仕組み。復元が可能 |
| インデックス | 検索を速くするための索引。本の目次に相当 |

---

## テーブル一覧

| テーブル名 | 役割 |
|---|---|
| users | ユーザー（出品者・購入者兼用） |
| shipping_addresses | 配送先住所（複数登録可） |
| bank_accounts | 振込先口座 |
| brands | ブランドマスター（選択式ブランドの一覧） |
| colors | カラーマスター（選択式カラーの一覧） |
| lures | ルアー商品 |
| lure_images | ルアー画像（複数枚） |
| lure_videos | ルアー動画（任意） |
| catch_records | 釣果情報（任意） |
| lure_comments | 商品コメント（値下げ交渉・質問） |
| favorites | お気に入り |
| transactions | 取引（購入〜完了までの管理） |
| payments | 決済情報（PAY.JP連携） |
| messages | 取引内メッセージ（購入者↔出品者） |
| reviews | レビュー・評価 |
| withdrawal_requests | 売上振込申請 |
| reports | 違反報告 |

---

## 各テーブルの詳細

### users（ユーザー）

```sql
id                   BIGINT UNSIGNED  PK, AUTO_INCREMENT
nickname             VARCHAR(50)      表示名（ユニーク）
email                VARCHAR(255)     UNIQUE, ログイン用
email_verified_at    TIMESTAMP        メール確認日時（NULL=未確認）
password             VARCHAR(255)     ハッシュ化済みパスワード（平文不可）
profile_image_path   VARCHAR(255)     NULL可
bio                  TEXT             自己紹介文、NULL可
rating_avg           DECIMAL(3,2)     評価平均（0.00〜5.00）、NULL可
rating_count         INT UNSIGNED     評価件数（デフォルト0）
sales_count          INT UNSIGNED     販売件数（デフォルト0）
balance              INT UNSIGNED     売上残高（円）、デフォルト0
kyc_status           ENUM             'unverified'(未確認) / 'pending'(審査中) / 'verified'(確認済み)
identity_verified_at TIMESTAMP        本人確認完了日時、NULL可 ※将来のeKYC連携用
is_banned            BOOLEAN          BANフラグ（デフォルトfalse）
remember_token       VARCHAR(100)     ログイン維持トークン
created_at           TIMESTAMP
updated_at           TIMESTAMP
deleted_at           TIMESTAMP        NULL可（ソフトデリート用：退会日時）
```

**インデックス**: `email`（UNIQUE）、`nickname`（UNIQUE）

---

### shipping_addresses（配送先住所）

```sql
id             BIGINT UNSIGNED  PK
user_id        BIGINT UNSIGNED  FK → users.id
name           VARCHAR(50)      受取人氏名
postal_code    VARCHAR(8)       郵便番号（例: 123-4567）
prefecture     VARCHAR(10)      都道府県
city           VARCHAR(50)      市区町村
address_line1  VARCHAR(100)     番地
address_line2  VARCHAR(100)     建物名・部屋番号、NULL可
phone_number   VARCHAR(15)      電話番号
is_default     BOOLEAN          デフォルト住所フラグ
created_at     TIMESTAMP
updated_at     TIMESTAMP
```

**インデックス**: `user_id`

---

### bank_accounts（振込先口座）

```sql
id                  BIGINT UNSIGNED  PK
user_id             BIGINT UNSIGNED  FK → users.id
bank_name           VARCHAR(50)      銀行名
branch_name         VARCHAR(50)      支店名
account_type        ENUM             'ordinary'(普通) / 'checking'(当座)
account_number      VARCHAR(10)      口座番号
account_holder_name VARCHAR(50)      口座名義（カナ）
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**注意**: 口座番号は個人情報のため、ログへの出力・画面表示は一部マスキング必須

**インデックス**: `user_id`

---

### brands（ブランドマスター）

```sql
id         BIGINT UNSIGNED  PK
name       VARCHAR(100)     ブランド名（例: メガバス、シマノ）
name_kana  VARCHAR(100)     カナ表記（検索・ソート用）
is_active  BOOLEAN          有効フラグ（無効化で非表示）
created_at TIMESTAMP
updated_at TIMESTAMP
```

---

### colors（カラーマスター）

```sql
id         BIGINT UNSIGNED  PK
name       VARCHAR(100)     カラー名（例: チャート、ゴールド、ナチュラル）
name_kana  VARCHAR(100)     カナ表記
is_active  BOOLEAN
created_at TIMESTAMP
updated_at TIMESTAMP
```

---

### lures（ルアー商品）★中心テーブル

```sql
-- 基本情報
id                  BIGINT UNSIGNED   PK
seller_id           BIGINT UNSIGNED   FK → users.id（出品者）
brand_id            BIGINT UNSIGNED   FK → brands.id、NULL可（自由入力時はNULL）
brand_name_custom   VARCHAR(100)      ブランド名自由入力、NULL可
model_name          VARCHAR(100)      モデル名（例: I-Waver、Blitz MR）
category            ENUM              'bass' / 'trout' / 'salt'

-- ルアー種類（カテゴリ別に入力値が変わるためVARCHAR）
lure_type           VARCHAR(50)       例: 'crankbait','minnow','metal_jig' 等

-- コンディション
condition           ENUM              'new_unopened'(新品未開封) /
                                      'new_opened'(新品開封済み) /
                                      'like_new'(未使用に近い) /
                                      'good'(目立った傷なし) /
                                      'fair'(やや傷あり) /
                                      'poor'(傷・汚れあり)
is_handmade         BOOLEAN           ハンドメイドフラグ
is_discontinued     BOOLEAN           廃盤フラグ
is_limited          BOOLEAN           限定品フラグ

-- 外観・スペック
color_id            BIGINT UNSIGNED   FK → colors.id、NULL可
color_name_custom   VARCHAR(100)      カラー自由入力、NULL可
size_mm             SMALLINT UNSIGNED サイズ（mm）、NULL可
weight_g            DECIMAL(6,1)      重量（g, 小数点1桁）、NULL可
material            ENUM              'plastic' / 'wood' / 'metal' / 'rubber' / 'other'、NULL可

-- アクション・特性
action              ENUM              'wobbling' / 'rolling' / 'wobble_and_roll' /
                                      'slide' / 'i_shaped' / 'no_action' / 'other'、NULL可
buoyancy            ENUM              'floating' / 'suspending' / 'sinking'、NULL可
range               ENUM              'surface'(表層) / 'shallow'(シャロー) /
                                      'middle'(中層) / 'deep'(ディープ) / 'bottom'(ボトム)、NULL可

-- フック・付属品
hook_size           VARCHAR(10)       例: '#6', '1/0'、NULL可
hook_condition      ENUM              'new'(新品) / 'replaced'(交換済み) /
                                      'used'(使用済み) / 'rusty'(サビあり)、NULL可
accessories         VARCHAR(255)      付属品（例: 元箱あり・ケースあり）、NULL可

-- カテゴリ別詳細属性（JSON形式で格納）
-- バス: {"target_species": "largemouth", "diving_range": "0-1m"}
-- トラウト: {"field_type": "managed_pond"}
-- ソルト: {"target_species": ["seabass","flounder"], "field_type": "shore"}
category_attributes JSON              NULL可

-- 商品説明・価格
description         TEXT              商品説明文
price               INT UNSIGNED      販売価格（円）

-- 配送情報
shipping_cost_type  ENUM              'included'(送料込み) / 'cash_on_delivery'(着払い)
shipping_method     VARCHAR(100)      配送方法（例: ゆうパック、ネコポス）、NULL可
shipping_days       ENUM              '1_2_days' / '3_7_days' / 'over_7_days'
shipping_prefecture VARCHAR(10)       発送元都道府県

-- ステータス
status              ENUM              'draft'(下書き) / 'active'(出品中) /
                                      'suspended'(一時停止) / 'sold'(売却済み) / 'deleted'(削除済み)

-- カウンター（表示用キャッシュ）
views_count         INT UNSIGNED      閲覧数（デフォルト0）
favorites_count     INT UNSIGNED      お気に入り数（デフォルト0）
comments_count      INT UNSIGNED      コメント数（デフォルト0）

-- 日時
sold_at             TIMESTAMP         売れた日時、NULL可
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**インデックス**:
- `seller_id`
- `status`
- `category`
- `price`
- `(category, status, price)` — 複合インデックス（絞り込み検索の高速化）
- `(status, created_at)` — 新着順表示の高速化

---

### lure_types（ルアー種類の選択肢）参考

| category | lure_type値 | 表示名 |
|---|---|---|
| bass | crankbait | クランクベイト |
| bass | vibration | バイブレーション |
| bass | spinnerbait | スピナーベイト |
| bass | buzzbait | バズベイト |
| bass | topwater | トップウォーター/ポッパー |
| bass | pencilbait | ペンシルベイト |
| bass | minnow | ミノー |
| bass | jerkbait | ジャークベイト |
| bass | swimbait | スイムベイト |
| bass | jig | ラバージグ |
| bass | worm | ワーム/ソフトルアー |
| bass | other | その他 |
| trout | spoon | スプーン |
| trout | spinner | スピナー |
| trout | minnow | ミノー |
| trout | crankbait | クランクベイト |
| trout | other | その他 |
| salt | minnow | ミノー |
| salt | vibration | バイブレーション |
| salt | metal_jig | メタルジグ |
| salt | popper | ポッパー |
| salt | pencilbait | シンキングペンシル/ペンシルベイト |
| salt | soft_lure | ワーム/ソフトルアー |
| salt | tai_rubber | タイラバ |
| salt | other | その他 |

---

### lure_images（ルアー画像）

```sql
id            BIGINT UNSIGNED  PK
lure_id       BIGINT UNSIGNED  FK → lures.id
image_path    VARCHAR(255)     ストレージ上のパス
display_order TINYINT UNSIGNED 表示順（0始まり）
created_at    TIMESTAMP
updated_at    TIMESTAMP
```

**制約**: 1ルアーにつき最大10枚をアプリ側で制御  
**インデックス**: `(lure_id, display_order)`

---

### lure_videos（ルアー動画 ※任意）

```sql
id          BIGINT UNSIGNED  PK
lure_id     BIGINT UNSIGNED  FK → lures.id（UNIQUE: 1ルアー1動画）
video_path  VARCHAR(255)     ストレージ上のパス
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

### catch_records（釣果情報 ※任意）

```sql
id           BIGINT UNSIGNED  PK
lure_id      BIGINT UNSIGNED  FK → lures.id（UNIQUE: 1ルアー1件）
fish_species VARCHAR(100)     釣れた魚種、NULL可
location     VARCHAR(100)     釣れた場所、NULL可
note         TEXT             メモ、NULL可
caught_at    DATE             釣れた日付、NULL可
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

---

### lure_comments（商品コメント）

```sql
id         BIGINT UNSIGNED  PK
lure_id    BIGINT UNSIGNED  FK → lures.id
user_id    BIGINT UNSIGNED  FK → users.id
body       TEXT             コメント本文
parent_id  BIGINT UNSIGNED  FK → lure_comments.id、NULL可（返信の場合に親IDを入れる）
is_hidden  BOOLEAN          非表示フラグ（管理者が不適切コメントを非表示に）
created_at TIMESTAMP
updated_at TIMESTAMP
deleted_at TIMESTAMP        NULL可（ソフトデリート）
```

**インデックス**: `lure_id`、`user_id`

---

### favorites（お気に入り）

```sql
id         BIGINT UNSIGNED  PK
user_id    BIGINT UNSIGNED  FK → users.id
lure_id    BIGINT UNSIGNED  FK → lures.id
created_at TIMESTAMP
```

**制約**: `UNIQUE(user_id, lure_id)` — 同じルアーを二重にお気に入りできない  
**インデックス**: `user_id`、`lure_id`

---

### transactions（取引）

```sql
id                   BIGINT UNSIGNED  PK
lure_id              BIGINT UNSIGNED  FK → lures.id
buyer_id             BIGINT UNSIGNED  FK → users.id（購入者）
seller_id            BIGINT UNSIGNED  FK → users.id（出品者）
shipping_address_id  BIGINT UNSIGNED  FK → shipping_addresses.id

-- 金額（取引時の価格を確定値として記録 ※後から変更されても履歴が残る）
price                INT UNSIGNED     取引時の商品価格
fee_rate             DECIMAL(4,3)     手数料率（例: 0.100 = 10%）
fee_amount           INT UNSIGNED     手数料額 = price × fee_rate（端数切り捨て）
seller_earnings      INT UNSIGNED     出品者受取額 = price - fee_amount

-- ステータス
status               ENUM             'pending_payment'(支払待ち) /
                                      'paid'(支払完了) /
                                      'preparing_shipment'(発送準備中) /
                                      'shipped'(発送済み) /
                                      'delivered'(配達完了) /
                                      'completed'(取引完了) /
                                      'cancelled'(キャンセル) /
                                      'disputed'(トラブル対応中)
cancelled_by         ENUM             'buyer' / 'seller' / 'admin'、NULL可
cancel_reason        TEXT             キャンセル理由、NULL可

-- 自動完了（発送後◯日で自動的に取引完了）
auto_complete_at     TIMESTAMP        NULL可

-- 各ステータスになった日時
paid_at              TIMESTAMP        NULL可
shipped_at           TIMESTAMP        NULL可
delivered_at         TIMESTAMP        NULL可
completed_at         TIMESTAMP        NULL可
cancelled_at         TIMESTAMP        NULL可
created_at           TIMESTAMP
updated_at           TIMESTAMP
```

**インデックス**: `buyer_id`、`seller_id`、`lure_id`、`status`

---

### payments（決済情報）

```sql
id               BIGINT UNSIGNED  PK
transaction_id   BIGINT UNSIGNED  FK → transactions.id（UNIQUE）
payjp_charge_id  VARCHAR(255)     PAY.JPの決済ID（例: ch_xxxxx）
amount           INT UNSIGNED     決済金額（円）
status           ENUM             'pending' / 'succeeded' / 'failed' / 'refunded'
failure_reason   VARCHAR(255)     失敗理由、NULL可
refunded_at      TIMESTAMP        返金日時、NULL可
refund_amount    INT UNSIGNED     返金額、NULL可
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

**注意**: PAY.JPのAPIキーや秘密情報はこのテーブルに保存しない。`.env`ファイルで管理

---

### messages（取引内メッセージ）

```sql
id             BIGINT UNSIGNED  PK
transaction_id BIGINT UNSIGNED  FK → transactions.id
sender_id      BIGINT UNSIGNED  FK → users.id（送信者）
body           TEXT             メッセージ本文、NULL可（画像のみ送信の場合）
image_path     VARCHAR(255)     画像添付パス、NULL可
is_read        BOOLEAN          既読フラグ（デフォルトfalse）
read_at        TIMESTAMP        既読日時、NULL可
created_at     TIMESTAMP
updated_at     TIMESTAMP
deleted_at     TIMESTAMP        NULL可（ソフトデリート）
```

**インデックス**: `transaction_id`、`sender_id`

---

### reviews（レビュー・評価）

```sql
id             BIGINT UNSIGNED  PK
transaction_id BIGINT UNSIGNED  FK → transactions.id
reviewer_id    BIGINT UNSIGNED  FK → users.id（レビューを書いた人）
reviewee_id    BIGINT UNSIGNED  FK → users.id（レビューを受けた人）
rating         TINYINT UNSIGNED 評価（1〜5）
body           TEXT             レビュー本文、NULL可
created_at     TIMESTAMP
updated_at     TIMESTAMP
```

**制約**: `UNIQUE(transaction_id, reviewer_id)` — 同じ取引に2回書けない  
**インデックス**: `reviewee_id`（ユーザープロフィールでの一覧表示用）

---

### withdrawal_requests（売上振込申請）

```sql
id              BIGINT UNSIGNED  PK
user_id         BIGINT UNSIGNED  FK → users.id
bank_account_id BIGINT UNSIGNED  FK → bank_accounts.id
amount          INT UNSIGNED     申請金額（円）
status          ENUM             'pending'(申請中) / 'processing'(処理中) /
                                 'completed'(振込完了) / 'rejected'(却下)
processed_at    TIMESTAMP        処理日時、NULL可
note            TEXT             管理者メモ、NULL可
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

### reports（違反報告）

```sql
id               BIGINT UNSIGNED  PK
reporter_id      BIGINT UNSIGNED  FK → users.id（報告者）
reportable_type  VARCHAR(50)      報告対象の種類: 'lure' / 'user' / 'lure_comment' / 'message'
reportable_id    BIGINT UNSIGNED  報告対象のID（ポリモーフィック関連）
reason           ENUM             'fake'(偽物疑い) / 'inappropriate'(不適切) /
                                  'spam' / 'prohibited_item'(出品禁止物) / 'other'
detail           TEXT             詳細、NULL可
status           ENUM             'pending'(未対応) / 'reviewed'(確認済み) /
                                  'resolved'(対応済み) / 'dismissed'(却下)
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

---

## テーブル関連図（ER図 テキスト版）

```
users ──────────────────── lures ──────── lure_images
  │  （seller_id）           │              lure_videos
  │                          │              catch_records
  │  （buyer_id）            │── lure_comments（parent_id で自己参照）
  │                          │── favorites
  │                          │
  ├── shipping_addresses      │
  │                          │
  ├── bank_accounts      transactions ──── payments
  │                       │    │           messages
  │  （buyer_id）         │    │           reviews
  │  （seller_id）        │    │
  │                       │    └── shipping_addresses
  └── withdrawal_requests │
                          │
                    brands（brand_id）
                    colors（color_id）

reports（reportable_type + reportable_id で lure/user/comment/message を参照）
```

---

## 設計上の重要な判断

### なぜ category_attributes を JSON にしたか
バス・トラウト・ソルトで属性が異なるため、カテゴリごとに別テーブルを作る方法もあるが、
初期リリースでは JSON カラムで一元管理しシンプルに保つ。
検索パフォーマンスに問題が生じた場合は別テーブルに移行する。

### なぜ transactions に price を持つか
取引成立後に出品者が価格を変更しても、取引時の価格が変わらないようにするため。
「取引時の事実」を別途記録するのは金融系システムの基本設計。

### なぜ reviews に reviewer_id と reviewee_id 両方を持つか
1回の取引で「購入者→出品者」「出品者→購入者」の2件のレビューが生まれるため。
transaction_id で紐付け、UNIQUE制約で二重投稿を防ぐ。

### ソフトデリートを使うテーブル
users / lure_comments / messages — 法的対応・トラブル解決のため削除後も内部にデータを保持する。
lures は `status = 'deleted'` で管理するためソフトデリートカラムは不要。
