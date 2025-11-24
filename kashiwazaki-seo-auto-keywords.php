<?php
/*
Plugin Name: Kashiwazaki SEO Auto Keywords
Plugin URI: https://www.tsuyoshikashiwazaki.jp
Description: OpenAI GPTを使ってWordPress投稿・固定ページ・カスタム投稿・メディアからSEOキーワードを自動生成します。
Version: 1.0.1
Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
Author URI: https://www.tsuyoshikashiwazaki.jp/profile/
*/

if (!defined('ABSPATH')) exit;

class KashiwazakiSEOAutoKeywords {

    /**
     * デフォルトモデル情報を保存するプロパティ
     * @var array|null
     */
    private $default_model = null;

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_keywords', array($this, 'generate_keywords_ajax'));
        add_action('wp_ajax_check_api_settings', array($this, 'check_api_settings_ajax'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function add_meta_box() {
        // 設定から対応する投稿タイプを取得（デフォルトは post と page）
        $enabled_post_types = get_option('kashiwazaki_seo_enabled_post_types', array('post', 'page'));

        // 空の場合はデフォルトを設定
        if (empty($enabled_post_types)) {
            $enabled_post_types = array('post', 'page');
        }

        // 選択された投稿タイプにメタボックスを追加
        foreach ($enabled_post_types as $post_type) {
            add_meta_box(
                'kashiwazaki_seo_keywords',
                'Kashiwazaki SEO Auto Keywords',
                array($this, 'meta_box_html'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function meta_box_html($post) {
        wp_nonce_field('kashiwazaki_seo_keywords_nonce', 'kashiwazaki_seo_keywords_nonce');
        $keywords = get_post_meta($post->ID, '_kashiwazaki_seo_keywords', true);
        $keyword_count = get_option('kashiwazaki_seo_keyword_count', 10);
        ?>
        <div id="kashiwazaki-seo-keywords-container">
            <div style="margin-bottom: 10px; padding: 8px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 3px;">
                <span style="font-size: 12px; color: #0073aa;">
                    <strong>⚙️ キーワード抽出数:</strong> <?php echo $keyword_count; ?>個
                    <span style="color: #666;">(設定画面で変更可能)</span>
                </span>
            </div>

            <!-- キーワード抽出ボタン -->
            <button type="button" id="generate-keywords-btn" class="button button-primary" style="width: 100%; margin-bottom: 10px; height: 35px; font-size: 14px;">
                🔍 キーワード抽出
            </button>

            <!-- ローディングアニメーション -->
            <div id="keywords-loading" style="display: none; text-align: center; margin: 10px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
                <div class="spinner" style="float: none; margin: 0 auto 10px; width: 20px; height: 20px;"></div>
                <p style="margin: 0; color: #666; font-size: 13px;">AIがキーワードを分析中...</p>
            </div>

            <!-- キーワード結果表示エリア -->
            <div id="keywords-result" style="margin-top: 10px;">
                <?php if ($keywords): ?>
                    <div class="keywords-display">
                        <?php
                        $keyword_array = explode(',', $keywords);
                        // 重複削除
                        $unique_keywords = array();
                        $seen_keywords = array();
                        foreach ($keyword_array as $keyword) {
                            $keyword = trim($keyword);
                            if (!empty($keyword)) {
                                // スペースをハイフンに正規化（既存キーワードの表示用）
                                $keyword = preg_replace('/\s+/', '-', $keyword);
                                $keyword = preg_replace('/-+/', '-', $keyword);
                                $keyword = trim($keyword, '-');

                                if (!empty($keyword)) {
                                    $keyword_lower = strtolower($keyword);
                                    if (!in_array($keyword_lower, $seen_keywords)) {
                                        $seen_keywords[] = $keyword_lower;
                                        $unique_keywords[] = $keyword;
                                    }
                                }
                            }
                        }

                        foreach ($unique_keywords as $keyword) {
                            echo '<span class="keyword-tag">' . esc_html($keyword) . '</span>';
                        }

                        // 重複削除後のキーワードをテキストエリアに設定
                        $unique_keywords_string = implode(',', $unique_keywords);
                        ?>
                    </div>
                    <div style="margin-top: 10px;">
                        <button type="button" id="copy-keywords-btn" style="padding: 4px 12px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">📋 キーワードをコピー</button>
                    </div>
                    <script>
                    jQuery(document).ready(function($) {
                        $('#keywords-textarea').val('<?php echo esc_js($unique_keywords_string); ?>');
                    });
                    </script>
                <?php endif; ?>
            </div>

            <!-- デバッグログ表示エリア（デフォルト非表示） -->
            <div style="margin-top: 10px;">
                <button type="button" id="toggle-debug" style="padding: 4px 8px; font-size: 11px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px; cursor: pointer;">🔧 デバッグログを表示</button>
            </div>
            <div id="debug-log" style="margin-top: 10px; padding: 8px; background: #f5f5f9; border: 1px solid #ddd; border-radius: 3px; font-size: 10px; max-height: 120px; overflow-y: auto; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <strong style="color: #333;">デバッグログ:</strong>
                    <button type="button" id="copy-all-logs" style="padding: 2px 6px; font-size: 9px; background: #0073aa; color: white; border: none; border-radius: 2px; cursor: pointer;">全ログコピー</button>
                </div>
                <div id="debug-content" style="margin-top: 5px;"></div>
            </div>

            <textarea id="keywords-textarea" name="kashiwazaki_seo_keywords" style="display: none;"><?php echo esc_textarea($keywords); ?></textarea>
        </div>
        <style>
            .keywords-display {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-top: 10px;
            }
            .keyword-tag {
                background: #0073aa;
                color: white;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 10px;
                display: inline-block;
                line-height: 1.2;
            }
            .spinner {
                border: 2px solid #f3f3f3;
                border-top: 2px solid #0073aa;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            #debug-content div {
                margin: 1px 0;
                padding: 1px 2px;
                border-bottom: 1px solid #ddd;
                word-break: break-all;
            }
        </style>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['kashiwazaki_seo_keywords_nonce']) ||
            !wp_verify_nonce($_POST['kashiwazaki_seo_keywords_nonce'], 'kashiwazaki_seo_keywords_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['kashiwazaki_seo_keywords'])) {
            update_post_meta($post_id, '_kashiwazaki_seo_keywords', sanitize_textarea_field($_POST['kashiwazaki_seo_keywords']));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Kashiwazaki SEO Auto Keywords',
            'Kashiwazaki SEO Auto Keywords',
            'manage_options',
            'kashiwazaki-seo-keywords',
            array($this, 'admin_page'),
            'dashicons-admin-generic',
            81
        );
    }

    public function admin_page() {
        // APIキーテスト処理
        if (isset($_POST['test_api'])) {
            $test_api_key = sanitize_text_field($_POST['openai_api_key']);
            $test_result = $this->test_api_key($test_api_key);
            if ($test_result['success']) {
                echo '<div class="notice notice-success"><p>✅ APIキーのテストが成功しました！</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ APIキーのテストが失敗しました: ' . esc_html($test_result['message']) . '</p></div>';
            }
        }

        if (isset($_POST['submit'])) {
            // OpenAI APIキーの保存
            if (isset($_POST['openai_api_key'])) {
                update_option('kashiwazaki_seo_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            }

            // モデルの保存
            if (isset($_POST['model'])) {
                update_option('kashiwazaki_seo_model', sanitize_text_field($_POST['model']));
            }

            // キーワード数の保存
            if (isset($_POST['keyword_count'])) {
                update_option('kashiwazaki_seo_keyword_count', intval($_POST['keyword_count']));
            }

            // 投稿タイプの設定を保存
            $enabled_post_types = isset($_POST['enabled_post_types']) ? array_map('sanitize_text_field', $_POST['enabled_post_types']) : array();
            update_option('kashiwazaki_seo_enabled_post_types', $enabled_post_types);

            // デバッグログの設定を保存
            $debug_log_enabled = isset($_POST['debug_log_enabled']) ? true : false;
            update_option('kashiwazaki_seo_debug_log', $debug_log_enabled);

            echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
        }

        // モデル復活処理
        if (isset($_POST['restore_model'])) {
            $model_to_restore = sanitize_text_field($_POST['restore_model']);
            $this->remove_from_excluded_models($model_to_restore);
            echo '<div class="notice notice-success"><p>モデル「' . esc_html($model_to_restore) . '」を復活させました。</p></div>';
        }

        // 一括モデル復活処理
        if (isset($_POST['bulk_restore_models'])) {
            $excluded_models = $this->get_excluded_models();
            $restored_count = 0;

            foreach ($excluded_models as $model_id) {
                $this->remove_from_excluded_models($model_id);
                $restored_count++;
            }

            if ($restored_count > 0) {
                echo '<div class="notice notice-success"><p>✅ ' . $restored_count . '個のモデルを一括復活させました。</p></div>';
            } else {
                echo '<div class="notice notice-info"><p>ℹ️ 除外中のモデルがありませんでした。</p></div>';
            }
        }

        $api_provider = get_option('kashiwazaki_seo_api_provider', 'openai');
        $api_key = get_option('kashiwazaki_seo_openai_api_key', '');
        $openai_api_key = get_option('kashiwazaki_seo_openai_api_key', '');
        $model = get_option('kashiwazaki_seo_model', '');
        $keyword_count = get_option('kashiwazaki_seo_keyword_count', 10);
        $enabled_post_types = get_option('kashiwazaki_seo_enabled_post_types', array('post', 'page'));
        $debug_log_enabled = get_option('kashiwazaki_seo_debug_log', false);

        // 利用可能な投稿タイプを取得
        $available_post_types = $this->get_available_post_types();

        // モデル情報をテキストファイルから読み込み
        $available_models = $this->load_models_from_file();
        ?>
        <div class="wrap">
            <h1>Kashiwazaki SEO Auto Keywords 設定</h1>

            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h3 style="margin: 0 0 10px 0;">AIキーワード抽出</h3>
                <p style="margin: 0;">OpenAI GPTを使用して投稿・固定ページからSEOキーワードを自動抽出します。</p>
            </div>

            <form method="post">
                <table class="form-table">


                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="text" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text">
                            <p class="description">OpenAIのAPIキーを入力</p>
                            <button type="submit" name="test_api" class="button">APIキーテスト</button>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">AIモデル</th>
                        <td>
                            <select name="model" class="regular-text">
                                <option value="">デフォルト（GPT-4.1 Nano）</option>
                                <option value="gpt-4.1-nano" <?php selected($model, 'gpt-4.1-nano'); ?>>GPT-4.1 Nano - 最も経済的</option>
                                <option value="gpt-4.1-mini" <?php selected($model, 'gpt-4.1-mini'); ?>>GPT-4.1 Mini - コストパフォーマンスが良い</option>
                                <option value="gpt-4.1" <?php selected($model, 'gpt-4.1'); ?>>GPT-4.1 - 高性能</option>
                            </select>
                            <p class="description">使用するAIモデルを選択。デフォルトはGPT-4.1 Nano（最も経済的）</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">キーワード抽出数</th>
                        <td>
                            <input type="number" name="keyword_count" value="<?php echo esc_attr($keyword_count); ?>" min="1" max="100" class="small-text">
                            <p class="description">抽出するキーワードの数</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">対応する投稿タイプ</th>
                        <td>
                            <div style="margin-bottom: 10px;">
                                <button type="button" id="select-all-post-types" class="button">全選択</button>
                                <button type="button" id="deselect-all-post-types" class="button">全解除</button>
                                <button type="button" id="select-common-post-types" class="button">基本のみ</button>
                            </div>

                            <fieldset>
                                <legend class="screen-reader-text"><span>対応する投稿タイプ</span></legend>

                                <!-- 標準投稿タイプ -->
                                <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #f9f9f9;">
                                    <h4 style="margin: 0 0 8px 0;">標準投稿タイプ</h4>
                                    <?php
                                    $builtin_types = array('post', 'page', 'attachment');
                                    foreach ($builtin_types as $post_type):
                                        if (isset($available_post_types[$post_type])):
                                    ?>
                                        <label for="post_type_<?php echo esc_attr($post_type); ?>" style="display: inline-block; margin-right: 20px; margin-bottom: 5px;">
                                            <input type="checkbox"
                                                   name="enabled_post_types[]"
                                                   id="post_type_<?php echo esc_attr($post_type); ?>"
                                                   value="<?php echo esc_attr($post_type); ?>"
                                                   <?php checked(in_array($post_type, $enabled_post_types)); ?>>
                                            <?php echo esc_html($available_post_types[$post_type]); ?>
                                        </label>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>

                                <!-- カスタム投稿タイプ -->
                                <?php
                                $custom_types = array_diff_key($available_post_types, array_flip($builtin_types));
                                if (!empty($custom_types)):
                                ?>
                                <div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #f0f8ff;">
                                    <h4 style="margin: 0 0 8px 0;">カスタム投稿タイプ</h4>
                                    <?php foreach ($custom_types as $post_type => $post_type_label): ?>
                                        <label for="post_type_<?php echo esc_attr($post_type); ?>" style="display: inline-block; margin-right: 20px; margin-bottom: 5px;">
                                            <input type="checkbox"
                                                   name="enabled_post_types[]"
                                                   id="post_type_<?php echo esc_attr($post_type); ?>"
                                                   value="<?php echo esc_attr($post_type); ?>"
                                                   <?php checked(in_array($post_type, $enabled_post_types)); ?>>
                                            <?php echo esc_html($post_type_label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                            </fieldset>

                            <p class="description">編集画面でキーワードを抽出する投稿タイプを選択</p>

                            <script>
                            jQuery(document).ready(function($) {
                                // 全選択
                                $('#select-all-post-types').on('click', function() {
                                    $('input[name="enabled_post_types[]"]').prop('checked', true);
                                });

                                // 全解除
                                $('#deselect-all-post-types').on('click', function() {
                                    $('input[name="enabled_post_types[]"]').prop('checked', false);
                                });

                                // 一般的なもののみ選択
                                $('#select-common-post-types').on('click', function() {
                                    $('input[name="enabled_post_types[]"]').prop('checked', false);
                                    $('#post_type_post, #post_type_page, #post_type_attachment').prop('checked', true);
                                });
                            });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">デバッグログ</th>
                        <td>
                            <label for="debug_log_enabled">
                                <input type="checkbox"
                                       name="debug_log_enabled"
                                       id="debug_log_enabled"
                                       value="1"
                                       <?php checked($debug_log_enabled, true); ?>>
                                デバッグログを有効にする
                            </label>
                            <p class="description">
                                <strong style="color: #d32f2f;">⚠️ 注意:</strong>
                                デバッグログを有効にすると、毎回のキーワード抽出でログが出力され、debug.txtファイルが大きくなります。<br>
                                <strong>通常は無効のままにしておくことを推奨します。</strong> トラブルシューティング時のみ有効にしてください。
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <script>
            </script>

            <?php
            // 除外されたモデル管理セクション
            $all_models = $this->get_all_models_with_status();
            $excluded_models = array_filter($all_models, function($model) { return $model['is_excluded']; });

            if (!empty($excluded_models)): ?>
            <div style="margin-top: 30px;">
                <h3 style="color: #d32f2f;">⚠️ 除外中のモデル（エラーにより無効化）</h3>
                <div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <p style="margin: 0; color: #d32f2f;">
                                <strong>以下のモデルはエラーが発生したため一時的に除外されています。</strong><br>
                                復活させたい場合は「復活」ボタンをクリックしてください。
                            </p>
                        </div>
                        <div>
                            <form method="post" style="margin: 0;" onsubmit="return confirm('除外中の全てのモデル（<?php echo count($excluded_models); ?>個）を一括で復活させますか？\n\n復活後は、それらのモデルが再び選択可能になります。')">
                                <input type="hidden" name="bulk_restore_models" value="1">
                                <button type="submit" class="button button-primary"
                                        style="background: #28a745; border-color: #28a745; white-space: nowrap; font-weight: bold; padding: 8px 16px;"
                                        onmouseover="this.style.background='#218838';"
                                        onmouseout="this.style.background='#28a745';">
                                    🔄 全て復活させる（<?php echo count($excluded_models); ?>個）
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php foreach ($excluded_models as $model_id => $model_info): ?>
                    <div style="background: white; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 3px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo esc_html($this->extract_short_model_name($model_info['display_name'])); ?></strong>
                                <br>
                                <small style="color: #666;">
                                    モデルID: <?php echo esc_html($model_id); ?>
                                    <?php if ($model_info['error_info']): ?>
                                    | エラー回数: <?php echo esc_html($model_info['error_info']['error_count']); ?>回
                                    | 最終エラー: <?php echo esc_html($model_info['error_info']['error_time']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="restore_model" value="<?php echo esc_attr($model_id); ?>">
                                <button type="submit" class="button button-secondary"
                                        onclick="return confirm('モデル「<?php echo esc_js($this->extract_short_model_name($model_info['display_name'])); ?>」を復活させますか？')">
                                    ✅ 復活させる
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="background: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin-top: 15px; border-radius: 3px;">
                        <strong style="color: #ef6c00;">💡 ヒント：</strong>
                        モデルを復活させても、再度エラーが発生した場合は自動的に除外されます。
                        APIプロバイダーのサーバー状況やモデルの利用可能性によってエラーが発生する可能性があります。
                        <br><br>
                        <strong style="color: #ef6c00;">🔄 一括復活機能：</strong>
                        右上の「全て復活させる」ボタンで、除外中の全モデルを一度に復活させることができます。
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_available_post_types() {
        // 利用可能な投稿タイプを取得
        $post_types = get_post_types(array('public' => true), 'objects');
        $available_types = array();

        foreach ($post_types as $post_type) {
            // attachment（メディア）も含める
            if ($post_type->name === 'attachment') {
                $available_types[$post_type->name] = $post_type->label . ' (メディア)';
            } else {
                $available_types[$post_type->name] = $post_type->label;
            }
        }

        // カスタム投稿タイプも追加で取得（非公開のものも含める）
        $custom_post_types = get_post_types(array('_builtin' => false), 'objects');
        foreach ($custom_post_types as $post_type) {
            if (!isset($available_types[$post_type->name])) {
                $available_types[$post_type->name] = $post_type->label . ' (カスタム投稿)';
            }
        }

        return $available_types;
    }

    private function load_models_from_file() {
        $models_file = plugin_dir_path(__FILE__) . 'models.txt';
        $available_models = array();
        $default_model = 'meta-llama/llama-4-maverick:free'; // フォールバック用

        if (!file_exists($models_file)) {
            // ファイルが存在しない場合のフォールバック（重要なエラーなので常にログ出力）
            error_log('Kashiwazaki SEO: models.txt が見つかりません。フォールバックモデルを使用します。');
            return array(
                'meta-llama/llama-4-maverick:free' => '🏆 Llama 4 Maverick (🆓完全無料・400B MoE・17B有効・最高性能)',
                'qwen/qwen3-4b:free' => '⚡ Qwen3 4B (🆓完全無料・4B・高速・思考モード)'
            );
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $excluded_models = $this->get_excluded_models();

        foreach ($lines as $line) {
            $line = trim($line);

            // コメント行やデフォルトモデル設定をスキップ
            if (empty($line) || strpos($line, '#') === 0) {
                // デフォルトモデルの設定をチェック
                if (strpos($line, 'DEFAULT_MODEL=') !== false) {
                    $default_model = trim(str_replace('DEFAULT_MODEL=', '', $line));
                }
                continue;
            }

            // モデル情報をパース（形式: model_id|display_name|category|description）
            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $model_id = trim($parts[0]);
                $display_name = trim($parts[1]);

                // 除外リストに含まれていない場合のみ追加
                if (!in_array($model_id, $excluded_models)) {
                    $available_models[$model_id] = $display_name;
                }
            }
        }

        // デフォルトモデルをクラスプロパティとして保存（他のメソッドで使用）
        $this->default_model = $default_model;

        return $available_models;
    }

    private function get_default_model() {
        return 'gpt-4.1-nano';
    }

    private function get_model_selection_help() {
        $models_file = plugin_dir_path(__FILE__) . 'models.txt';

        if (!file_exists($models_file)) {
            return '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin-top: 10px; border-radius: 5px;">
                        <p style="margin: 0; color: #c62828;">⚠️ models.txt ファイルが見つかりません。</p>
                    </div>';
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $categorized_models = array(
            'flagship' => array('title' => '🏆 最高性能モデル', 'color' => '#1976d2', 'models' => array()),
            'premium' => array('title' => '🥇 高性能モデル', 'color' => '#f57c00', 'models' => array()),
            'specialized' => array('title' => '💻 特化型モデル', 'color' => '#7b1fa2', 'models' => array()),
            'lightweight' => array('title' => '⚡ 軽量・高速モデル', 'color' => '#388e3c', 'models' => array()),
            'custom' => array('title' => '🌟 特殊機能モデル', 'color' => '#795548', 'models' => array())
        );

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $model_id = trim($parts[0]);
                $display_name = trim($parts[1]);
                $category = trim($parts[2]);
                $description = trim($parts[3]);

                // モデル名を短縮形で抽出
                $short_name = $this->extract_short_model_name($display_name);

                if (isset($categorized_models[$category])) {
                    $categorized_models[$category]['models'][] = array(
                        'name' => $short_name,
                        'description' => $description
                    );
                }
            }
        }

        $html = '<div style="background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin-top: 10px; border-radius: 5px;">
                    <h4 style="margin: 0 0 10px 0; color: #2e7d32;">🆓 完全無料モデルの特徴と使い分け</h4>';

        // カテゴリを2列で表示
        $categories = array_keys($categorized_models);
        $half = ceil(count($categories) / 2);

        $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';

        for ($i = 0; $i < $half; $i++) {
            $html .= '<div>';
            if (isset($categories[$i])) {
                $category = $categories[$i];
                $cat_data = $categorized_models[$category];
                if (!empty($cat_data['models'])) {
                    $html .= '<h5 style="margin: 0 0 5px 0; color: ' . $cat_data['color'] . ';">' . $cat_data['title'] . '</h5>';
                    $html .= '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">';
                    foreach ($cat_data['models'] as $model) {
                        $html .= '<li><strong>' . esc_html($model['name']) . '</strong>: ' . esc_html($model['description']) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
            $html .= '</div>';
        }

        $html .= '<div>';
        for ($i = $half; $i < count($categories); $i++) {
            if (isset($categories[$i])) {
                $category = $categories[$i];
                $cat_data = $categorized_models[$category];
                if (!empty($cat_data['models'])) {
                    $html .= '<h5 style="margin: 0 0 5px 0; color: ' . $cat_data['color'] . ';">' . $cat_data['title'] . '</h5>';
                    $html .= '<ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">';
                    foreach ($cat_data['models'] as $model) {
                        $html .= '<li><strong>' . esc_html($model['name']) . '</strong>: ' . esc_html($model['description']) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
        }
        $html .= '</div></div>';

        $html .= '<div style="background: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin-top: 10px; border-radius: 3px;">
                     <strong style="color: #ef6c00;">💡 推奨モデル：</strong>
                     初回は <strong>Llama 4 Maverick</strong>（最高性能）がおすすめ。
                     高速処理が必要な場合は <strong>Qwen3 4B</strong> を試してください。
                  </div></div>';

        return $html;
    }

    private function extract_short_model_name($display_name) {
        // 各種記号や絵文字を安全に削除してモデル名を抽出
        $name = $display_name;

        // 絵文字を削除（より安全な方法）
        $name = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $name);
        $name = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $name);
        $name = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $name);
        $name = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $name);
        $name = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $name);

        // 括弧内の情報を除去
        $name = preg_replace('/\s*\([^)]*\).*$/', '', $name);

        // 先頭の記号やスペースを除去
        $name = preg_replace('/^[^\w\s]+\s*/', '', $name);

        return trim($name);
    }

    private function get_model_display_name($model_id) {
        if (empty($model_id)) {
            // デフォルトモデルを取得して表示名を返す
            $default_model = $this->get_default_model();
            if (empty($default_model)) {
                return 'Llama 4 Maverick'; // フォールバック
            }
            $model_id = $default_model; // 再帰を避けて直接処理
        }

        $models_file = plugin_dir_path(__FILE__) . 'models.txt';

        if (!file_exists($models_file)) {
            // ファイルがない場合はモデルIDから名前を生成
            if (strpos($model_id, 'llama-4-maverick') !== false) return 'Llama 4 Maverick';
            if (strpos($model_id, 'llama-4-scout') !== false) return 'Llama 4 Scout';
            if (strpos($model_id, 'gemini-2.5-pro') !== false) return 'Gemini 2.5 Pro';
            if (strpos($model_id, 'mistral-small') !== false) return 'Mistral Small 3.1 24B';
            if (strpos($model_id, 'qwen3-30b') !== false) return 'Qwen3 30B A3B';
            if (strpos($model_id, 'qwen3-14b') !== false) return 'Qwen3 14B';
            if (strpos($model_id, 'qwen3-4b') !== false) return 'Qwen3 4B';
            return $this->extract_short_model_name($model_id);
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $file_model_id = trim($parts[0]);
                $display_name = trim($parts[1]);

                if ($file_model_id === $model_id) {
                    // 安全な方法で表示名を処理
                    $clean_name = $display_name;
                    // 括弧内の情報を除去
                    $clean_name = preg_replace('/\s*\([^)]*\).*$/', '', $clean_name);
                    // 先頭の記号を除去（絵文字を避けて）
                    $clean_name = preg_replace('/^[^\w\s]+\s*/', '', $clean_name);
                    return trim($clean_name);
                }
            }
        }

        // 見つからない場合はモデルIDから推測
        if (strpos($model_id, 'llama-4-maverick') !== false) {
            return 'Llama 4 Maverick';
        }
        if (strpos($model_id, 'llama-4-scout') !== false) {
            return 'Llama 4 Scout';
        }
        if (strpos($model_id, 'gemini-2.5-pro') !== false) return 'Gemini 2.5 Pro';
        if (strpos($model_id, 'mistral-small') !== false) return 'Mistral Small 3.1 24B';
        if (strpos($model_id, 'qwen3-30b') !== false) return 'Qwen3 30B A3B';
        if (strpos($model_id, 'qwen3-14b') !== false) return 'Qwen3 14B';
        if (strpos($model_id, 'qwen3-4b') !== false) return 'Qwen3 4B';

        // extract_short_model_nameを使わずに直接処理
        $model_name = str_replace('meta-llama/', '', $model_id);
        $model_name = str_replace(':free', '', $model_name);
        $model_name = ucwords(str_replace('-', ' ', $model_name));

        return $model_name;
    }

    private function get_excluded_models() {
        return get_option('kashiwazaki_seo_excluded_models', array());
    }

    private function add_to_excluded_models($model_id) {
        $excluded_models = $this->get_excluded_models();
        if (!in_array($model_id, $excluded_models)) {
            $excluded_models[] = $model_id;
            update_option('kashiwazaki_seo_excluded_models', $excluded_models);

            // エラー情報も記録
            $error_info = get_option('kashiwazaki_seo_model_errors', array());
            $error_info[$model_id] = array(
                'error_time' => current_time('mysql'),
                'error_count' => isset($error_info[$model_id]['error_count']) ? $error_info[$model_id]['error_count'] + 1 : 1
            );
            update_option('kashiwazaki_seo_model_errors', $error_info);

            // モデル除外は重要な情報なので常にログ出力
            error_log("Kashiwazaki SEO: モデル '{$model_id}' を除外リストに追加しました。");
        }
    }

    private function remove_from_excluded_models($model_id) {
        $excluded_models = $this->get_excluded_models();
        $key = array_search($model_id, $excluded_models);
        if ($key !== false) {
            unset($excluded_models[$key]);
            update_option('kashiwazaki_seo_excluded_models', array_values($excluded_models));

            // モデル復活は重要な情報なので常にログ出力
            error_log("Kashiwazaki SEO: モデル '{$model_id}' を除外リストから復活させました。");
        }
    }

    private function get_all_models_with_status() {
        $models_file = plugin_dir_path(__FILE__) . 'models.txt';
        $all_models = array();
        $excluded_models = $this->get_excluded_models();
        $error_info = get_option('kashiwazaki_seo_model_errors', array());

        if (!file_exists($models_file)) {
            return array();
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $model_id = trim($parts[0]);
                $display_name = trim($parts[1]);

                $all_models[$model_id] = array(
                    'display_name' => $display_name,
                    'is_excluded' => in_array($model_id, $excluded_models),
                    'error_info' => isset($error_info[$model_id]) ? $error_info[$model_id] : null
                );
            }
        }

        return $all_models;
    }

    private function try_fallback_model($scraped_data, $api_key, $keyword_count, $failed_model) {
        // 利用可能なモデルを優先度順で取得
        $available_models = $this->get_fallback_models($failed_model);

        if (empty($available_models)) {
            return array('success' => false, 'message' => '利用可能なフォールバックモデルがありません。');
        }

        // 優先度の高いモデルから順に試行
        foreach ($available_models as $model_id => $model_name) {
            $this->debug_log("Kashiwazaki SEO: フォールバックモデル '{$model_id}' を試行中...");

            $result = $this->generate_keywords_with_ai($scraped_data, $api_key, $model_id, $keyword_count);

            if (!is_wp_error($result)) {
                // 成功した場合、そのモデルをデフォルトに設定
                update_option('kashiwazaki_seo_model', $model_id);
                // フォールバック成功は重要な情報なので常にログ出力
                error_log("Kashiwazaki SEO: フォールバックモデル '{$model_id}' で成功。デフォルトモデルを更新しました。");

                return array(
                    'success' => true,
                    'keywords' => $result,
                    'used_model' => $this->extract_short_model_name($model_name)
                );
            } else {
                // このフォールバックモデルも失敗した場合は除外（エラーなので常にログ出力）
                error_log("Kashiwazaki SEO: フォールバックモデル '{$model_id}' も失敗: " . $result->get_error_message());
                $this->add_to_excluded_models($model_id);
            }
        }

        return array('success' => false, 'message' => 'すべてのフォールバックモデルが失敗しました。');
    }

    private function try_lightweight_models($scraped_data, $api_key, $keyword_count) {
        // models.txtから軽量モデル（lightweightカテゴリ）を取得
        $lightweight_models = $this->get_models_by_category('lightweight');

        if (empty($lightweight_models)) {
            return array('success' => false, 'message' => '軽量モデルが見つかりません。');
        }

        $excluded_models = $this->get_excluded_models();

        foreach ($lightweight_models as $model_id => $model_name) {
            // 除外されていないモデルのみ試行
            if (!in_array($model_id, $excluded_models)) {
                $this->debug_log("Kashiwazaki SEO: 軽量モデル '{$model_id}' を試行中...");

                $result = $this->generate_keywords_with_ai($scraped_data, $api_key, $model_id, $keyword_count);

                if (!is_wp_error($result)) {
                    // 成功した場合
                    update_option('kashiwazaki_seo_model', $model_id);
                    error_log("Kashiwazaki SEO: 軽量モデル '{$model_id}' で成功。");

                    return array(
                        'success' => true,
                        'keywords' => $result,
                        'used_model' => $this->extract_short_model_name($model_name)
                    );
                } else {
                    // このモデルも失敗した場合は除外
                    error_log("Kashiwazaki SEO: 軽量モデル '{$model_id}' も失敗: " . $result->get_error_message());
                    $this->add_to_excluded_models($model_id);
                }
            }
        }

        return array('success' => false, 'message' => 'すべての軽量モデルでもトークン制限に引っかかりました。記事を短くするかキーワード数を減らしてください。');
    }

    private function get_models_by_category($category) {
        $models_file = plugin_dir_path(__FILE__) . 'models.txt';
        $models_by_category = array();

        if (!file_exists($models_file)) {
            return array();
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $excluded_models = $this->get_excluded_models();

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $model_id = trim($parts[0]);
                $display_name = trim($parts[1]);
                $model_category = trim($parts[2]);

                // 指定されたカテゴリで、かつ除外されていないモデルのみ追加
                if ($model_category === $category && !in_array($model_id, $excluded_models)) {
                    $models_by_category[$model_id] = $display_name;
                }
            }
        }

        return $models_by_category;
    }

    private function get_fallback_models($failed_model) {
        $available_models = $this->load_models_from_file();

        // 失敗したモデルを除外
        unset($available_models[$failed_model]);

        if (empty($available_models)) {
            return array();
        }

        // 優先度の高いモデル順にソート（flagship > premium > specialized > lightweight > custom）
        $priority_models = array();
        $models_file = plugin_dir_path(__FILE__) . 'models.txt';

        if (!file_exists($models_file)) {
            return $available_models;
        }

        $lines = file($models_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $category_priority = array(
            'flagship' => 1,
            'premium' => 2,
            'specialized' => 3,
            'lightweight' => 4,
            'custom' => 5
        );

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $model_id = trim($parts[0]);
                $display_name = trim($parts[1]);
                $category = trim($parts[2]);

                if (isset($available_models[$model_id])) {
                    $priority = isset($category_priority[$category]) ? $category_priority[$category] : 999;
                    $priority_models[] = array(
                        'model_id' => $model_id,
                        'display_name' => $display_name,
                        'priority' => $priority
                    );
                }
            }
        }

        // 優先度でソート
        usort($priority_models, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // 結果を配列に変換
        $sorted_models = array();
        foreach ($priority_models as $model) {
            $sorted_models[$model['model_id']] = $model['display_name'];
        }

        return $sorted_models;
    }

    private function test_api_key($api_key) {
        $url = 'https://api.openai.com/v1/models';

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'ネットワークエラー: ' . $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'APIキーが正常に動作しています'
            );
        } else {
            $body = wp_remote_retrieve_body($response);
            return array(
                'success' => false,
                'message' => "HTTP {$status_code}: " . $body
            );
        }
    }

    /**
     * デバッグログを出力する（設定により制御）
     * @param string $message ログメッセージ
     */
    private function debug_log($message) {
        $debug_enabled = get_option('kashiwazaki_seo_debug_log', false);
        if ($debug_enabled) {
            error_log($message);
        }
    }

    public function enqueue_admin_scripts($hook) {
        // 投稿編集画面と新規投稿画面、メディア編集画面でスクリプトを読み込む
        if (in_array($hook, array('post.php', 'post-new.php', 'upload.php', 'media.php'))) {
            $version = '1.0.0.' . time(); // キャッシュバスティング用
            wp_enqueue_script('kashiwazaki-seo-keywords', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $version, true);
            wp_localize_script('kashiwazaki-seo-keywords', 'kashiwazaki_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('kashiwazaki_seo_nonce'),
                'plugin_url' => plugin_dir_url(__FILE__)
            ));
        }
    }

    public function generate_keywords_ajax() {
        // デバッグログ開始
        $this->debug_log('Kashiwazaki SEO: AJAX処理開始');

        check_ajax_referer('kashiwazaki_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        $this->debug_log('Kashiwazaki SEO: 投稿ID = ' . $post_id);

        $post = get_post($post_id);

        if (!$post) {
            // エラー時は常にログを出力
            error_log('Kashiwazaki SEO: 投稿が見つかりません');
            wp_send_json_error('投稿が見つかりません');
        }

        // API プロバイダー別のAPIキー取得
        $api_provider = get_option('kashiwazaki_seo_api_provider', 'openai');
        $api_key = get_option('kashiwazaki_seo_openai_api_key');

        $model = get_option('kashiwazaki_seo_model', $this->get_default_model());
        $keyword_count = get_option('kashiwazaki_seo_keyword_count', 10);

        $this->debug_log('Kashiwazaki SEO: API設定 - プロバイダー: ' . $api_provider . ', モデル: ' . $model . ', キーワード数: ' . $keyword_count);
        $this->debug_log('Kashiwazaki SEO: APIキー確認 - ' . substr($api_key, 0, 10) . '...' . substr($api_key, -10));

        if (empty($api_key)) {
            // エラー時は常にログを出力
            error_log('Kashiwazaki SEO: APIキーが設定されていません');
            wp_send_json_error('OpenAI APIキーが設定されていません。管理画面で設定してください。');
        }

        $scraped_data = $this->scrape_post_content($post);
        $this->debug_log('Kashiwazaki SEO: コンテンツ抽出完了 - 長さ: ' . strlen($scraped_data));

        $keywords = $this->generate_keywords_with_ai($scraped_data, $api_key, $model, $keyword_count);

        if (is_wp_error($keywords)) {
            $error_message = $keywords->get_error_message();
            // エラー時は常にログを出力
            error_log('Kashiwazaki SEO: AI処理エラー - ' . $error_message);

            // レート制限エラー（429）の場合はフォールバックしない（モデル除外も不要）
            if (strpos($error_message, '429') !== false) {
                // レート制限の場合は全モデル共通なのでフォールバック不要
                wp_send_json_error($error_message);
                return;
            }

            // エラーが発生したモデルを除外リストに追加（レート制限以外の場合のみ）
            if (!empty($model)) {
                $this->add_to_excluded_models($model);

                // HTTP 402エラーの場合は軽量モデルを優先的に試行
                if (strpos($error_message, '402') !== false) {
                    // トークン制限エラーの場合は軽量モデルを優先
                    $fallback_result = $this->try_lightweight_models($scraped_data, $api_key, $keyword_count);
                } else {
                    // その他のエラーの場合は通常のフォールバック
                    $fallback_result = $this->try_fallback_model($scraped_data, $api_key, $keyword_count, $model);
                }

                if (isset($fallback_result) && $fallback_result['success']) {
                    // フォールバックモデルで成功した場合
                    wp_send_json_success(array(
                        'keywords' => $fallback_result['keywords'],
                        'message' => "⚠️ {$model} でエラーが発生したため、{$fallback_result['used_model']} に自動切り替えしました。",
                        'switched_model' => $fallback_result['used_model']
                    ));
                } else {
                    // フォールバックも失敗した場合
                    $error_message .= "\n\n⚠️ {$model} でエラーが発生し、他の利用可能なモデルでも処理できませんでした。\n設定画面でモデルを復活させるか、APIキーを確認してください。";
                    wp_send_json_error($error_message);
                }
            } else {
                wp_send_json_error($error_message);
            }
        }

        $this->debug_log('Kashiwazaki SEO: キーワード生成成功 - ' . $keywords);

        // 使用モデル名を取得（デフォルトモデルの場合も適切に処理）
        $actual_model = !empty($model) ? $model : $this->get_default_model();
        $model_display_name = $this->get_model_display_name($actual_model);

        // モデル情報のログ
        $this->debug_log("Kashiwazaki SEO: 使用モデル - ID: {$actual_model}, 表示名: {$model_display_name}");

        // キーワードと一緒にモデル情報を文字列として返す
        $response_data = $keywords;

        // モデル情報がある場合は、特別な形式で返す
        if (!empty($model_display_name)) {
            wp_send_json_success(array(
                'keywords' => $keywords,
                'used_model' => $model_display_name,
                'model_id' => $actual_model
            ));
        } else {
            // フォールバック：通常の文字列として返す
            wp_send_json_success($keywords);
        }
    }

    private function scrape_post_content($post) {
        $content = $post->post_title . "\n\n";

        if ($post->post_type === 'attachment') {
            // メディアファイル
            $content .= basename(get_attached_file($post->ID)) . " ";
            $content .= $post->post_content . " ";
            $alt_text = get_post_meta($post->ID, '_wp_attachment_image_alt', true);
            if ($alt_text) $content .= $alt_text . " ";
            if ($post->post_excerpt) $content .= $post->post_excerpt . " ";
        } else {
            $content .= $post->post_content;
        }

        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);

        return mb_substr($content, 0, 800);
    }

    private function generate_keywords_with_ai($scraped_data, $api_key, $model, $keyword_count) {
        $api_provider = get_option('kashiwazaki_seo_api_provider', 'openai');

        // API プロバイダー別のURL設定
        if ($api_provider === 'openai') {
            $url = 'https://api.openai.com/v1/chat/completions';
            $actual_api_key = get_option('kashiwazaki_seo_openai_api_key', '');
        } else {
            $url = 'https://api.openai.com/v1/chat/completions';
            $actual_api_key = $api_key;
        }

        $prompt = "以下から{$keyword_count}個のSEOキーワードを抽出。必ずカンマ区切りのみで回答：\n" . $scraped_data;

        $data = array(
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 300,
            'temperature' => 0.7
        );

        if (empty($model)) {
            $model = $this->get_default_model();
        }
        $data['model'] = $model;

        $headers = array(
            'Authorization' => 'Bearer ' . $actual_api_key,
            'Content-Type' => 'application/json'
        );

        // === 基本デバッグログ（最小限） ===
        $json_data = json_encode($data);

        // 一時的なデバッグ出力（ブラウザに表示）
        $debug_info = array(
            'provider' => $api_provider,
            'url' => $url,
            'api_key_preview' => substr($actual_api_key, 0, 10) . '...' . substr($actual_api_key, -10),
            'api_key_length' => strlen($actual_api_key),
            'headers' => $headers,
            'data' => $data,
            'json_data' => $json_data,
            'request_time' => date('Y-m-d H:i:s'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );

        // wp_remote_postのオプション
        $request_options = array(
            'headers' => $headers,
            'body' => $json_data,
            'timeout' => 30,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_site_url(),
            'sslverify' => true,
            'httpversion' => '1.1'
        );

        $response = wp_remote_post($url, $request_options);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return new WP_Error('api_error', "API接続エラー: {$error_message}");
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $error_msg = "APIエラー (HTTP {$status_code}): " . $body;

            // プロバイダー別のエラーメッセージ
            if ($api_provider === 'openai') {
                if ($status_code === 401) {
                    $error_msg = "❌ OpenAI認証エラー：APIキーが無効です。\n\n" .
                               "💡 対策方法：\n" .
                               "1. OpenAI APIキーが正しく入力されているか確認\n" .
                               "2. APIキーの有効期限を確認\n" .
                               "3. OpenAIアカウントに十分なクレジットがあるか確認\n\n" .
                               "詳細: " . $body;
                } elseif ($status_code === 429) {
                    $error_msg = "⏰ OpenAI レート制限エラー：リクエスト制限に達しました。\n\n" .
                               "💡 対策方法：\n" .
                               "1. 数分待ってから再度お試しください\n" .
                               "2. OpenAIの利用プランをアップグレード\n" .
                               "3. 一時的に軽量なモデルに切り替え\n\n" .
                               "詳細: " . $body;
                }
            } else {
                // 他のプロバイダー用のエラーメッセージ
                if ($status_code === 402) {
                    $error_msg = "❌ トークン制限エラー：無料プランのトークン制限に達しました。\n\n" .
                               "💡 対策方法：\n" .
                               "1. より軽量なモデル（軽量・高速モデル）を選択\n" .
                               "2. キーワード数を5個以下に減らす\n" .
                               "3. 記事の文字数を短くする\n" .
                               "4. 有料プランに変更する\n\n" .
                               "詳細: " . $body;
                } elseif ($status_code === 429) {
                    $error_msg = "⏰ レート制限エラー：1日のリクエスト制限に達しました。\n\n" .
                               "💡 対策方法：\n" .
                               "1. 明日まで待ってから再度お試しください\n" .
                               "2. 軽量なモデルに変更する\n" .
                               "3. 有料プランに変更するとより高い制限が利用可能\n\n" .
                               "📊 現在の制限状況：\n" .
                               "- 1日の制限: 50回（無料プラン）\n" .
                               "- 残り回数: 0回\n" .
                               "- リセット時間: 翌日0時（UTC）\n\n" .
                               "詳細: " . $body;
                }
            }

            return new WP_Error('api_error', $error_msg);
        }

        $json_result = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSON解析エラー: ' . json_last_error_msg());
        }

        if (isset($json_result['choices'][0]['message']['content'])) {
            $content = trim($json_result['choices'][0]['message']['content']);
            if (empty($content)) {
                return new WP_Error('empty_response', 'AIからの応答が空でした');
            }

            // AIからの生の応答をデバッグログに記録
            $this->debug_log("Kashiwazaki SEO: AI生応答: '" . $content . "'");
            $this->debug_log("Kashiwazaki SEO: AI応答長: " . strlen($content) . "文字");
            $this->debug_log("Kashiwazaki SEO: AI応答最後の10文字: '" . substr($content, -10) . "'");

            // WordPressタグ用にキーワードを正規化
            $content = $this->normalize_keywords_for_tags($content);
            return $content;
        } else {
            return new WP_Error('invalid_response', 'AIからの応答を解析できませんでした');
        }
    }

    private function normalize_keywords_for_tags($keywords_string) {
        $this->debug_log("Kashiwazaki SEO: 正規化前キーワード: " . $keywords_string);

        // まず番号などを除去して、キーワードのみを抽出
        $keywords_string = preg_replace('/\d+[\.\)]?/u', ',', $keywords_string); // 数字と番号をカンマに変換
        $keywords_string = preg_replace('/[・。、]/u', ',', $keywords_string); // 日本語句読点をカンマに変換
        $keywords_string = preg_replace('/,+/', ',', $keywords_string); // 連続するカンマを1つに

        $this->debug_log("Kashiwazaki SEO: 数字除去後: " . $keywords_string);

        // カンマで分割
        $keywords = explode(',', $keywords_string);
        $this->debug_log("Kashiwazaki SEO: 分割後キーワード数: " . count($keywords));
        $normalized_keywords = array();

                foreach ($keywords as $index => $keyword) {
            $original_keyword = $keyword;
            $keyword = trim($keyword);

            // 最後のキーワードは特別にデバッグ
            $is_last = ($index === count($keywords) - 1);
            if ($is_last) {
                $this->debug_log("Kashiwazaki SEO: 最後のキーワード処理開始: index={$index}, 元データ='{$original_keyword}'");
                $this->debug_log("Kashiwazaki SEO: 最後のキーワード文字コード: " . bin2hex($original_keyword));
            }

            if (!empty($keyword)) {
                // 不要な文字列を除去
                $keyword = preg_replace('/(最も重要|具体的な|などの|など)/u', '', $keyword);
                $keyword = preg_replace('/検索エンジン名$/', '検索エンジン', $keyword);
                $keyword = preg_replace('/結果ページ$/', 'ページ', $keyword);

                // 半角スペースを半角ハイフンに変換（WordPressタグ対応）
                $keyword = preg_replace('/\s+/', '-', $keyword);

                // 連続するハイフンを1つに正規化
                $keyword = preg_replace('/-+/', '-', $keyword);

                // 前後のハイフンを除去
                $keyword = trim($keyword, '-');

                // 前後の空白を除去
                $keyword = trim($keyword);

                if ($is_last) {
                    $this->debug_log("Kashiwazaki SEO: 最後のキーワード処理結果: '{$keyword}' (長さ: " . mb_strlen($keyword) . ")");
                }

                $this->debug_log("Kashiwazaki SEO: キーワード[{$index}] 処理: '{$original_keyword}' -> '{$keyword}'");

                // 2文字以上のキーワードのみ採用
                if (mb_strlen($keyword) >= 2 && mb_strlen($keyword) <= 20) {
                    $normalized_keywords[] = $keyword;
                    if ($is_last) {
                        $this->debug_log("Kashiwazaki SEO: 最後のキーワード採用: '{$keyword}'");
                    }
                } else {
                    if ($is_last) {
                        $this->debug_log("Kashiwazaki SEO: 最後のキーワード除外: 長さ=" . mb_strlen($keyword) . " ('{$keyword}')");
                    }
                }
            } else {
                if ($is_last) {
                    $this->debug_log("Kashiwazaki SEO: 最後のキーワードが空: '{$keyword}'");
                }
            }
        }

        $result = implode(',', $normalized_keywords);
        $this->debug_log("Kashiwazaki SEO: 正規化後キーワード: " . $result);

        return $result;
    }

    public function check_api_settings_ajax() {
        check_ajax_referer('kashiwazaki_seo_nonce', 'nonce');

        $api_key = get_option('kashiwazaki_seo_openai_api_key', '');
        $model = get_option('kashiwazaki_seo_model', '');
        $keyword_count = get_option('kashiwazaki_seo_keyword_count', 8);

        $settings = array(
            'api_key_exists' => !empty($api_key),
            'api_key_preview' => !empty($api_key) ? substr($api_key, 0, 10) . '...' . substr($api_key, -10) : '未設定',
            'model' => $model,
            'keyword_count' => $keyword_count
        );

        wp_send_json_success($settings);
    }

    /**
     * プラグイン一覧に「設定」リンクを追加
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=kashiwazaki-seo-keywords') . '">設定</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new KashiwazakiSEOAutoKeywords();
