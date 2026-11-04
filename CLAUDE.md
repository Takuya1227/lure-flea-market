# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## プロジェクト概要

**サービス名**: ルアマケ（LureMake）  
**内容**: 釣りルアー専用フリマサービス（バス・トラウト・ソルト対応）  
**対応デバイス**: PCブラウザ・スマホブラウザ（将来的にiOS/Androidアプリ拡張予定）  
**デプロイ先**: さくらのレンタルサーバ（スタンダードプラン以上 ※SSHアクセス必要）

---

## 技術スタック

| レイヤー | 技術 | 備考 |
|---|---|---|
| バックエンド | Laravel 11 (PHP 8.2+) | REST API + サーバーサイドレンダリング |
| フロントエンド | Blade + Tailwind CSS + Alpine.js | スマホ対応レスポンシブデザイン |
| データベース | MySQL 8.0 | さくらのレンタルサーバ標準サポート |
| 画像ストレージ | ローカルストレージ → 将来的にS3互換へ移行 | |
| 決済 | PAY.JP | 日本国内決済、トークン方式でカード情報サーバー保存なし |
| バージョン管理 | Git + GitHub（privateリポジトリ） | |
| 認証 | Laravel Breeze（標準認証スキャフォールド） | |
| メール送信 | さくらのSMTP / Amazon SES | パスワードリセット・通知 |
| **ローカル開発環境** | **Laravel Sail（Docker）** | **Mac本体を汚さずDocker上に開発環境を構築** |

---

## 開発コマンド

> ローカル開発はLaravel Sail（Docker）を使用。`php artisan`の代わりに`./vendor/bin/sail artisan`を使う。
> エイリアス設定後は`sail artisan`と短縮可能（初回セットアップ時に設定）。

```bash
# Sailの起動・停止
./vendor/bin/sail up -d    # バックグラウンドで起動（-d = detached mode）
./vendor/bin/sail down     # 停止

# フロントエンド（Viteビルド）※Sailの外（Mac本体）で実行
npm run dev

# データベース
./vendor/bin/sail artisan migrate                  # マイグレーション実行
./vendor/bin/sail artisan migrate:fresh --seed     # DB初期化 + テストデータ投入
./vendor/bin/sail artisan db:seed                  # テストデータのみ投入

# データベース
php artisan migrate                  # マイグレーション実行（テーブル作成）
php artisan migrate:fresh --seed     # DB初期化 + テストデータ投入
php artisan db:seed                  # テストデータのみ投入

# テスト実行
./vendor/bin/sail artisan test                     # 全テスト実行
./vendor/bin/sail artisan test --filter=UserTest   # 特定のテストのみ実行
./vendor/bin/sail artisan test --coverage          # カバレッジ付きで実行

# キャッシュクリア（動作がおかしい時）
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# 本番デプロイ後に必要なコマンド（さくらのサーバ上でSSH経由で実行）
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

---

## ディレクトリ構成（予定）

```
lure_flea_market/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # 各機能のコントローラー（処理の中枢）
│   │   ├── Middleware/      # 認証チェック等の前処理
│   │   └── Requests/        # 入力バリデーション（不正データの排除）
│   ├── Models/              # DBテーブルに対応するモデル
│   └── Services/            # ビジネスロジック（決済・取引処理等）
├── database/
│   ├── migrations/          # テーブル定義（変更履歴として管理）
│   └── seeders/             # テストデータ
├── resources/
│   ├── views/               # Bladeテンプレート（画面HTML）
│   └── css/ js/             # フロントエンドアセット
├── routes/
│   ├── web.php              # Webルーティング（URL→処理の対応表）
│   └── api.php              # APIルーティング（将来のアプリ向け）
├── storage/
│   └── app/public/          # アップロード画像の保存先
└── tests/
    ├── Feature/             # 統合テスト（実際のHTTPリクエストを模擬）
    └── Unit/                # 単体テスト（個別関数のテスト）
```

---

## ビジネスルール（確定済み）

| 項目 | 決定内容 |
|---|---|
| 出品単位 | 1出品 = 1個（数量管理なし） |
| ブランド・カラー入力 | ハイブリッド（選択式 + 自由入力） |
| 値下げ交渉 | コメント方式（専用機能なし） |
| 販売手数料 | 売上の10%（ルアマケが徴収） |
| 決済 | PAY.JP（カード情報はサーバー保存なし・トークン方式） |
| 本人確認（eKYC） | 初期リリースはなし。将来的に外部サービス連携（LIQUID eKYC等）。usersテーブルにkyc_status・identity_verified_atカラムを予め用意しておく |
| 電気通信事業 | リリース前に総務省へ届出が必要（メッセージ機能が対象） |

### 手数料の計算ロジック
```
購入者が支払う金額 = 商品価格 + 送料（送料着払いの場合は別）
出品者の売上       = 商品価格 × 90%（手数料10%を差し引き）
PAY.JP決済手数料  = ルアマケ側が負担（売上から相殺）
```

---

## 主要モデル（DB設計）

詳細は `/tasks/db_design.md` を参照。

| モデル | テーブル | 役割 |
|---|---|---|
| User | users | ユーザー（出品者・購入者兼用）。ソフトデリートあり |
| ShippingAddress | shipping_addresses | 配送先住所（ユーザーが複数登録可） |
| BankAccount | bank_accounts | 振込先口座 |
| Brand | brands | ブランドマスター（選択式） |
| Color | colors | カラーマスター（選択式） |
| Lure | lures | ルアー商品。カテゴリ別詳細属性はJSONカラム（category_attributes）で管理 |
| LureImage | lure_images | ルアー画像（1商品最大10枚） |
| LureVideo | lure_videos | ルアー動画（任意・1商品1本） |
| CatchRecord | catch_records | 釣果情報（任意・1商品1件） |
| LureComment | lure_comments | 商品コメント（値下げ交渉・質問）。parent_idで返信対応。ソフトデリートあり |
| Favorite | favorites | お気に入り。UNIQUE(user_id, lure_id) |
| Transaction | transactions | 取引管理。取引時のpriceを確定値として保持 |
| Payment | payments | PAY.JP決済情報（charge_idのみ保存、カード情報は保存しない） |
| Message | messages | 取引内メッセージ。ソフトデリートあり |
| Review | reviews | レビュー。UNIQUE(transaction_id, reviewer_id)で二重投稿防止 |
| WithdrawalRequest | withdrawal_requests | 売上振込申請 |
| Report | reports | 違反報告（ポリモーフィック: lure/user/comment/message対象） |

### 取引ステータス遷移
```
pending_payment（支払待ち）
  → paid（支払完了）
    → preparing_shipment（発送準備中）
      → shipped（発送済み）
        → delivered（配達完了）
          → completed（取引完了）← 手動 or 自動（auto_complete_at経過後）
  → cancelled（キャンセル）← 各ステータスから管理者・出品者・購入者が実行可
  → disputed（トラブル対応中）
```

---

## セキュリティ要件（最重要）

このプロジェクトではセキュリティを最優先事項として扱う。コードレビュー時に必ず確認すること。

### 必須対策
- **CSRF対策**: 全フォームに`@csrf`トークンを付与（Laravel標準）
- **XSS対策**: Bladeテンプレートでは`{{ }}`（自動エスケープ）を使用。`{!! !!}`は原則禁止
- **SQLインジェクション対策**: Eloquent ORM / クエリビルダを使用。生SQLは原則禁止
- **認証・認可**: ポリシー（Policy）とゲート（Gate）でアクセス制御
- **入力バリデーション**: 全フォームでFormRequestクラスを使用
- **ファイルアップロード**: 拡張子・MIMEタイプチェック、ランダムファイル名生成
- **HTTPS強制**: 本番環境では全通信をHTTPS化（.envで`APP_URL=https://...`）
- **秘密情報管理**: APIキー・DB接続情報は`.env`ファイルで管理（Gitにコミット禁止）
- **レートリミット**: ログイン・API呼び出しに回数制限を設ける
- **決済情報**: カード番号等はサーバーに保存しない（PAY.JPのトークンのみ保存）

### コードレビューチェックリスト（セキュリティ）
- [ ] ユーザー入力がバリデーションされているか
- [ ] 他ユーザーのリソースに不正アクセスできないか（認可チェック）
- [ ] ログに個人情報・認証情報が出力されていないか
- [ ] 依存パッケージに既知の脆弱性がないか（`composer audit`）

---

## 開発ルール

- **コメント**: 非自明な処理・セキュリティ上の判断には必ずコメントを残す
- **テスト**: 認証・決済・取引フローには必ずFeatureテストを書く
- **マイグレーション**: 一度コミットしたマイグレーションは編集しない（新規マイグレーションを追加）
- **画像アップロード**: 必ずバリデーション後にストレージへ保存（`$request->file('image')->store()`使用）

---

## 開発フェーズ（全体ロードマップ）

詳細タスクは `/tasks/roadmap.md` を参照。

| フェーズ | 内容 | 優先度 |
|---|---|---|
| 0 | 要件定義・DB設計・ワイヤーフレーム | 最高 |
| 1 | 環境構築（ローカル・本番・Git） | 最高 |
| 2 | 認証・ユーザー管理 | 高 |
| 3 | ルアー出品・管理 | 高 |
| 4 | 検索・閲覧機能 | 高 |
| 5 | 取引・決済 | 高 |
| 6 | メッセージ・通知 | 中 |
| 7 | レビュー機能 | 中 |
| 8 | 管理者機能 | 中 |
| 9 | テスト（セキュリティ含む） | 最高 |
| 10 | リリース準備・法的対応 | 最高 |
| 11 | 運用・保守 | 継続 |
