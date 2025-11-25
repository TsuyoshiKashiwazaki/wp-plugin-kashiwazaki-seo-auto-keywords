# Kashiwazaki SEO Auto Keywords

![Version](https://img.shields.io/badge/version-1.0.2-blue.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![Tested up to](https://img.shields.io/badge/Tested%20up%20to-WordPress%206.4-success.svg)
![OpenAI](https://img.shields.io/badge/OpenAI-GPT--4.1-orange.svg)

OpenAI GPT対応。WordPress投稿・固定ページ・カスタム投稿・メディアから自動でSEOキーワードを抽出・生成する高性能AIプラグインです。

## 主な機能

- **OpenAI GPT対応**
  - GPT-4.1 Nano（デフォルト）
  - GPT-4.1 Mini
  - GPT-4.1
- **対応コンテンツタイプ**
  - 投稿・固定ページ
  - カスタム投稿タイプ
  - メディア（添付ファイル）
- **自動キーワード生成**
  - コンテンツ内容を分析してSEO最適なキーワードを抽出
  - カスタマイズ可能なキーワード数設定
- **インテリジェント機能**
  - 失敗したモデルの自動除外・復活機能
  - フォールバックモデルによる自動リトライ
  - デバッグログ機能

## インストール方法

1. プラグインファイルを `/wp-content/plugins/kashiwazaki-seo-auto-keywords/` ディレクトリにアップロード
2. WordPress管理画面でプラグインを有効化
3. 管理画面の「Kashiwazaki SEO Auto Keywords」メニューでAPIキーを設定

## 使用方法

### 1. API設定

#### OpenAI GPTを使用する場合
1. [OpenAI](https://platform.openai.com/)でAPIキー取得
2. APIキー（sk-で始まるキー）を入力・保存

### 2. キーワード生成

1. 投稿・固定ページの編集画面を開く
2. サイドバーの「Kashiwazaki SEO Auto Keywords」ボックスで「キーワード抽出」ボタンをクリック
3. AIが自動でキーワードを生成・表示

## 利用可能なAIモデル

### OpenAI GPT
- GPT-4.1 Nano（デフォルト・最も経済的）
- GPT-4.1 Mini（コストパフォーマンスが良い）
- GPT-4.1（高性能）

## システム要件

- WordPress 5.0以上
- PHP 7.4以上
- インターネット接続（AI API利用のため）

## ライセンス

GPLv2 or later

## 作者

**柏崎剛 (Tsuyoshi Kashiwazaki)**
- Website: https://www.tsuyoshikashiwazaki.jp

## サポート・バグ報告

プラグインに関する問題やご質問は、作者のWebサイトまでお問い合わせください。

## 更新履歴

### [1.0.2] - 2025-11-25
- **追加**: 一括キーワード生成＆登録機能
- **追加**: キーワードをタグとして一括登録する機能
- **追加**: タグの絞り込みフィルター
- **追加**: 表示件数設定（20/50/100/全件）
- **改善**: 状態列をKW（キーワード生成）とタグ（タグ反映）に分割
- **改善**: 各記事へのページ表示リンク（↗）を追加
- **改善**: ボタンラベルの明確化（KW未生成/生成済み）

### [1.0.1] - 2025-11-24
- **修正**: APIキー設定時の「Undefined index」エラーを修正
- **改善**: プラグイン一覧から設定画面へのリンクを追加

### [1.0.0] - 2025-09-10
- 初回リリース