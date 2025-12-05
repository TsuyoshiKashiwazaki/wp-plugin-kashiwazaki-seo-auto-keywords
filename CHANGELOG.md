# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2025-12-05

### Added
- プラグイン一覧ページに「一括生成」へのリンクを追加

## [1.0.2] - 2025-11-25

### Added
- 一括キーワード生成＆登録機能
- キーワードをタグとして一括登録する機能
- タグの絞り込みフィルター
- 表示件数設定（20/50/100/全件）
- 各記事へのページ表示リンク（↗アイコン）

### Changed
- 状態列をKW（キーワード生成）とタグ（タグ反映）に分割
- ボタンラベルの明確化（KW未生成/生成済み）
- ページ見出しを「一括キーワード生成＆登録」に変更

## [1.0.1] - 2025-11-24

### Fixed
- APIキー設定時の「Undefined index」エラーを修正

### Added
- プラグイン一覧から設定画面へのリンクを追加

## [1.0.0] - 2025-09-10

### Added
- 初回リリース
- OpenAI GPT対応（GPT-4.1 Nano/Mini/標準）
- 投稿・固定ページ・カスタム投稿・メディア対応
- 自動キーワード生成機能
- 失敗モデルの自動除外・復活機能
- フォールバックモデルによる自動リトライ
- デバッグログ機能
