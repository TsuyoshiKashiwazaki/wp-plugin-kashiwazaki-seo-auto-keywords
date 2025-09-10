(function ($) {
    'use strict';

    // エラーメッセージを整形する関数
    function formatErrorMessage(errorMsg) {
        if (!errorMsg) {
            errorMsg = '不明なエラーが発生しました';
        }

        // HTMLエスケープ
        errorMsg = $('<div>').text(errorMsg).html();

        // URLを検出して短縮表示
        errorMsg = errorMsg.replace(/(https?:\/\/[^\s\)]+)/g, function (match) {
            var displayUrl = match.length > 50 ? match.substring(0, 47) + '...' : match;
            return '<a href="' + match + '" target="_blank" style="color: #0073aa; text-decoration: underline;">' + displayUrl + '</a>';
        });

        // 長い行を分割
        var lines = errorMsg.split('\n');
        var formattedLines = [];

        lines.forEach(function (line) {
            if (line.length > 80) {
                // 長い行を適切な位置で分割
                var words = line.split(' ');
                var currentLine = '';

                words.forEach(function (word) {
                    if ((currentLine + word).length > 80 && currentLine.length > 0) {
                        formattedLines.push(currentLine.trim());
                        currentLine = word + ' ';
                    } else {
                        currentLine += word + ' ';
                    }
                });

                if (currentLine.trim()) {
                    formattedLines.push(currentLine.trim());
                }
            } else {
                formattedLines.push(line);
            }
        });

        var finalMessage = formattedLines.join('<br>');

        // エラータイプに応じたアイコンとスタイルを適用
        var errorType = 'エラー';
        var iconStyle = '❌';
        var bgColor = '#ffe6e6';
        var borderColor = '#ff9999';
        var textColor = '#d32f2f';

        if (finalMessage.includes('タイムアウト')) {
            errorType = 'タイムアウト';
            iconStyle = '⏰';
            bgColor = '#fff3e0';
            borderColor = '#ffcc02';
            textColor = '#f57c00';
        } else if (finalMessage.includes('ネットワーク')) {
            errorType = 'ネットワークエラー';
            iconStyle = '🌐';
            bgColor = '#e3f2fd';
            borderColor = '#2196f3';
            textColor = '#1976d2';
        } else if (finalMessage.includes('レート制限') || finalMessage.includes('Rate limit')) {
            errorType = 'レート制限エラー';
            iconStyle = '⏰';
            bgColor = '#fff8e1';
            borderColor = '#ffb74d';
            textColor = '#ef6c00';
        } else if (finalMessage.includes('API')) {
            errorType = 'API エラー';
            iconStyle = '🔑';
        }

        return '<div style="background: ' + bgColor + '; border: 1px solid ' + borderColor + '; border-radius: 5px; padding: 15px; margin: 10px 0;">' +
            '<div style="display: flex; align-items: center; margin-bottom: 8px;">' +
            '<span style="font-size: 18px; margin-right: 8px;">' + iconStyle + '</span>' +
            '<strong style="color: ' + textColor + '; font-size: 14px;">' + errorType + '</strong>' +
            '</div>' +
            '<div style="color: ' + textColor + '; font-size: 13px; line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word;">' +
            finalMessage +
            '</div>' +
            '<div style="margin-top: 10px; padding: 8px; background: rgba(255,255,255,0.7); border-radius: 3px; font-size: 11px; color: #666;">' +
            '<strong>💡 対処法:</strong> ' +
            '<span>' + getSolutionMessage(errorType) + '</span>' +
            '</div>' +
            '</div>';
    }

    // エラータイプに応じた対処法メッセージを返す関数
    function getSolutionMessage(errorType) {
        if (errorType === 'レート制限エラー') {
            return '明日まで待つか、軽量なモデルに変更してください。';
        } else if (errorType === 'タイムアウト') {
            return 'ネットワーク環境を確認するか、記事を短くしてから再度お試しください。';
        } else if (errorType === 'ネットワークエラー') {
            return 'インターネット接続を確認してから再度お試しください。';
        } else {
            return '設定画面でモデルやAPIキーを確認してください。問題が続く場合は、除外されたモデルを復活させるか、別のモデルを選択してください。';
        }
    }

    // WordPress管理画面での初期化
    $(document).ready(function () {
        console.log('Kashiwazaki SEO Keywords: WordPress管理画面で初期化開始');

        // デバッグログ表示状態管理
        var debugLogVisible = false;

        // デバッグログ関数
        function addDebugLog(message) {
            var timestamp = new Date().toLocaleTimeString();
            var logEntry = '[' + timestamp + '] ' + message;
            console.log(logEntry);

            var $debugContent = $('#debug-content');
            var $debugLog = $('#debug-log');

            if ($debugContent.length) {
                $debugContent.append('<div style="margin: 2px 0; padding: 2px; border-bottom: 1px solid #ddd; word-break: break-all;">' + logEntry + '</div>');

                // デバッグログが表示状態の場合のみ表示
                if (debugLogVisible) {
                    $debugLog.show();
                }
                $debugContent.scrollTop($debugContent[0].scrollHeight);
            }
        }

        // デバッグログ切り替え機能
        $(document).on('click', '#toggle-debug', function () {
            var $debugLog = $('#debug-log');
            var $toggleBtn = $(this);

            debugLogVisible = !debugLogVisible;

            if (debugLogVisible) {
                $debugLog.show();
                $toggleBtn.text('🔧 デバッグログを非表示');
                addDebugLog('デバッグログ表示モード: ON');
            } else {
                $debugLog.hide();
                $toggleBtn.text('🔧 デバッグログを表示');
                console.log('[' + new Date().toLocaleTimeString() + '] デバッグログ表示モード: OFF');
            }
        });

        // 詳細デバッグ情報の出力
        addDebugLog('=== 詳細デバッグ情報開始 ===');
        addDebugLog('ブラウザ情報: ' + navigator.userAgent);
        addDebugLog('ページURL: ' + window.location.href);
        addDebugLog('ページタイトル: ' + document.title);
        addDebugLog('jQueryバージョン: ' + $.fn.jquery);
        addDebugLog('kashiwazaki_ajax: ' + JSON.stringify(kashiwazaki_ajax));
        addDebugLog('ボタン要素確認: ' + $('#generate-keywords-btn').length + '個');
        addDebugLog('WordPress管理画面判定: ' + (window.location.href.indexOf('/wp-admin/') !== -1 ? '管理画面' : 'フロントエンド'));

        // ネットワーク接続状況の確認
        if (navigator.onLine) {
            addDebugLog('ネットワーク接続: オンライン');
        } else {
            addDebugLog('ネットワーク接続: オフライン');
        }

        // APIキー設定確認（管理画面の設定値を確認）
        addDebugLog('API設定確認リクエスト開始');
        $.ajax({
            url: kashiwazaki_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_api_settings',
                nonce: kashiwazaki_ajax.nonce
            },
            success: function (response) {
                addDebugLog('API設定確認レスポンス受信');
                if (response.success) {
                    addDebugLog('API設定確認: ' + JSON.stringify(response.data));
                } else {
                    addDebugLog('API設定確認エラー: ' + JSON.stringify(response));
                }
            },
            error: function (xhr, status, error) {
                addDebugLog('API設定確認AJAXエラー');
                addDebugLog('Status: ' + status);
                addDebugLog('Error: ' + error);
                addDebugLog('Response: ' + xhr.responseText);
            }
        });

        // ボタンが存在するかチェック
        if ($('#generate-keywords-btn').length === 0) {
            addDebugLog('警告: キーワード抽出ボタンが見つかりません');
            addDebugLog('利用可能なボタン要素: ' + $('button').length + '個');
            addDebugLog('利用可能なID要素: ' + $('[id]').length + '個');
            return;
        }

        // ボタンクリックイベント（WordPress管理画面用）
        $(document).on('click', '#generate-keywords-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            addDebugLog('=== キーワード抽出開始 ===');
            addDebugLog('ボタンクリックイベント発火');
            addDebugLog('イベントタイプ: ' + e.type);
            addDebugLog('イベントターゲット: ' + e.target.id);
            addDebugLog('イベントタイムスタンプ: ' + e.timeStamp);

            var $btn = $(this);
            var $loading = $('#keywords-loading');
            var $result = $('#keywords-result');
            var $textarea = $('#keywords-textarea');
            var $debugLog = $('#debug-log');

            // 投稿IDの取得（複数の方法で試行）
            var postId = $('#post_ID').val() || $('#post_ID').attr('value') || window.location.search.match(/post=(\d+)/)?.[1];

            addDebugLog('投稿ID取得: ' + postId);
            addDebugLog('投稿ID取得方法詳細:');
            addDebugLog('- $(\'#post_ID\').val(): ' + $('#post_ID').val());
            addDebugLog('- $(\'#post_ID\').attr("value"): ' + $('#post_ID').attr('value'));
            addDebugLog('- URL検索結果: ' + window.location.search.match(/post=(\d+)/)?.[1]);

            if (!postId) {
                addDebugLog('エラー: 投稿IDが見つかりません');
                addDebugLog('利用可能なフォーム要素: ' + $('form').length + '個');
                addDebugLog('利用可能なinput要素: ' + $('input').length + '個');
                addDebugLog('利用可能なhidden要素: ' + $('input[type="hidden"]').length + '個');

                var formattedError = formatErrorMessage('投稿IDが見つかりません。ページを再読み込みしてください。');
                $result.html(formattedError);
                return;
            }

            // UI状態の更新
            $btn.prop('disabled', true).text('処理中...');
            $loading.show();
            $result.empty();

            addDebugLog('AJAXリクエスト準備完了');
            addDebugLog('送信データ: action=generate_keywords, post_id=' + postId);

            // リクエスト開始時刻を記録
            var requestStartTime = new Date();
            addDebugLog('リクエスト開始時刻: ' + requestStartTime.toISOString());

            // 設定画面で指定されたキーワード数を使用
            addDebugLog('設定で指定されたキーワード数を使用（個別指定は廃止）');

            // AJAXリクエスト
            $.ajax({
                url: kashiwazaki_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'generate_keywords',
                    nonce: kashiwazaki_ajax.nonce,
                    post_id: postId
                },
                timeout: 60000, // 60秒タイムアウト
                beforeSend: function (xhr) {
                    addDebugLog('AJAX beforeSend発火');
                    addDebugLog('リクエストヘッダー設定開始');
                    // カスタムヘッダーの追加
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-WordPress-Plugin', 'Kashiwazaki SEO Auto Keywords');
                    addDebugLog('カスタムヘッダー設定完了');
                },
                success: function (response) {
                    var requestEndTime = new Date();
                    var requestDuration = requestEndTime - requestStartTime;

                    addDebugLog('AJAXレスポンス受信成功');
                    addDebugLog('リクエスト完了時刻: ' + requestEndTime.toISOString());
                    addDebugLog('リクエスト所要時間: ' + requestDuration + 'ms');
                    addDebugLog('レスポンス: ' + JSON.stringify(response));

                    if (response && response.success) {
                        var keywords, switchMessage = '', usedModel = '', modelId = '';

                        // レスポンスデータの構造をチェック
                        if (typeof response.data === 'object' && response.data.keywords) {
                            // 詳細情報がある場合
                            keywords = response.data.keywords;
                            usedModel = response.data.used_model || '';
                            modelId = response.data.model_id || '';

                            addDebugLog('詳細レスポンス受信: usedModel=' + usedModel + ', modelId=' + modelId);

                            if (response.data.message) {
                                switchMessage = response.data.message;
                                addDebugLog('モデル切り替え発生: ' + switchMessage);
                            }
                            if (response.data.switched_model) {
                                addDebugLog('切り替え先モデル: ' + response.data.switched_model);
                            }
                        } else {
                            // 旧形式の場合 - デフォルトモデル名を設定
                            keywords = response.data;
                            usedModel = 'Llama 4 Maverick';  // デフォルト
                            modelId = 'meta-llama/llama-4-maverick:free';
                            addDebugLog('旧形式レスポンス: デフォルトモデル使用');
                        }

                        addDebugLog('キーワード取得成功: ' + keywords);
                        addDebugLog('キーワードデータ型: ' + typeof keywords);
                        addDebugLog('キーワード長: ' + (typeof keywords === 'string' ? keywords.length : 'N/A'));

                        if (typeof keywords === 'string' && keywords.trim()) {
                            var keywordArray = keywords.split(',');
                            addDebugLog('キーワード配列作成: ' + keywordArray.length + '個');

                            // キーワードの重複削除処理
                            var uniqueKeywords = [];
                            var seenKeywords = new Set();

                            keywordArray.forEach(function (keyword, index) {
                                keyword = keyword.trim();
                                addDebugLog('キーワード処理[' + index + ']: "' + keyword + '"');

                                if (keyword) {
                                    // 前後の空白のみ除去
                                    keyword = keyword.trim();
                                    addDebugLog('キーワード処理: "' + keyword + '"');

                                    if (keyword) {
                                        // 重複チェック（大文字小文字無視）
                                        var keywordLower = keyword.toLowerCase();
                                        if (!seenKeywords.has(keywordLower)) {
                                            seenKeywords.add(keywordLower);
                                            uniqueKeywords.push(keyword);
                                        } else {
                                            addDebugLog('重複キーワードをスキップ: "' + keyword + '"');
                                        }
                                    }
                                }
                            });

                            addDebugLog('重複削除後: ' + uniqueKeywords.length + '個の一意キーワード');

                            // キーワード表示とコピーボタンの作成
                            var html = '<div class="keywords-display">';

                            uniqueKeywords.forEach(function (keyword) {
                                html += '<span class="keyword-tag">' + keyword + '</span>';
                            });

                            html += '</div>';
                            html += '<div style="margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';
                            html += '<button type="button" id="copy-keywords-btn" style="padding: 4px 12px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">📋 キーワードをコピー</button>';

                            // 使用モデル情報を表示
                            if (usedModel || modelId) {
                                // モデル名のフォールバック処理
                                var displayModel = usedModel;
                                if (!displayModel || displayModel === '' || displayModel === 'null') {
                                    // モデルIDから表示名を生成
                                    if (modelId) {
                                        if (modelId.includes('llama-4-maverick')) displayModel = 'Llama 4 Maverick';
                                        else if (modelId.includes('llama-4-scout')) displayModel = 'Llama 4 Scout';
                                        else if (modelId.includes('gemini-2.5-pro')) displayModel = 'Gemini 2.5 Pro';
                                        else if (modelId.includes('mistral-small')) displayModel = 'Mistral Small 3.1 24B';
                                        else if (modelId.includes('qwen3-30b')) displayModel = 'Qwen3 30B A3B';
                                        else if (modelId.includes('qwen3-14b')) displayModel = 'Qwen3 14B';
                                        else if (modelId.includes('qwen3-4b')) displayModel = 'Qwen3 4B';
                                        else displayModel = 'デフォルトモデル';
                                    } else {
                                        displayModel = 'デフォルトモデル';
                                    }
                                }

                                // HTMLエスケープを避けるため、テキストとして安全に処理
                                var escapedModel = $('<div>').text(displayModel).html();
                                html += '<span style="padding: 3px 8px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 3px; font-size: 11px; color: #0073aa; display: inline-flex; align-items: center; gap: 4px;">';
                                html += '<span style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; display: inline-block;"></span>';
                                html += '使用モデル: <strong>' + escapedModel + '</strong>';
                                html += '</span>';
                                addDebugLog('使用モデル表示: ' + displayModel + ' (元データ: ' + usedModel + ', ID: ' + modelId + ')');
                            }

                            html += '</div>';

                            // モデル切り替えメッセージがある場合は表示
                            if (switchMessage) {
                                html += '<div style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; color: #856404; font-size: 12px;">';
                                html += '<strong>🔄 自動切り替え:</strong> ' + switchMessage;
                                html += '</div>';
                                addDebugLog('モデル切り替え通知表示: ' + switchMessage);
                            }

                            $result.html(html);

                            // テキストエリアには重複削除後のキーワードを設定
                            var uniqueKeywordsString = uniqueKeywords.join(',');
                            $textarea.val(uniqueKeywordsString);
                            addDebugLog('キーワード表示完了: ' + uniqueKeywords.length + '個の一意キーワード');
                        } else {
                            addDebugLog('警告: キーワードが空です');
                            addDebugLog('キーワード値: "' + keywords + '"');

                            var warningHtml = '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 10px 0;">' +
                                '<div style="display: flex; align-items: center; margin-bottom: 8px;">' +
                                '<span style="font-size: 18px; margin-right: 8px;">⚠️</span>' +
                                '<strong style="color: #856404; font-size: 14px;">キーワード生成なし</strong>' +
                                '</div>' +
                                '<div style="color: #856404; font-size: 13px; line-height: 1.5;">' +
                                'コンテンツからキーワードを抽出できませんでした。' +
                                '</div>' +
                                '<div style="margin-top: 10px; padding: 8px; background: rgba(255,255,255,0.7); border-radius: 3px; font-size: 11px; color: #666;">' +
                                '<strong>💡 改善方法:</strong> ' +
                                '<span>記事のタイトルや本文に十分なテキストがあるか確認してください。短すぎる内容では適切なキーワードが生成されない場合があります。</span>' +
                                '</div>' +
                                '</div>';

                            $result.html(warningHtml);
                        }
                    } else {
                        var errorMsg = (response && response.data) ? response.data : 'エラーが発生しました';
                        addDebugLog('エラー: ' + errorMsg);
                        addDebugLog('エラーレスポンス詳細: ' + JSON.stringify(response));

                        // エラーメッセージを整形
                        var formattedError = formatErrorMessage(errorMsg);
                        $result.html(formattedError);
                    }
                },
                error: function (xhr, status, error) {
                    var requestEndTime = new Date();
                    var requestDuration = requestEndTime - requestStartTime;

                    addDebugLog('AJAXエラー発生');
                    addDebugLog('リクエスト完了時刻: ' + requestEndTime.toISOString());
                    addDebugLog('リクエスト所要時間: ' + requestDuration + 'ms');
                    addDebugLog('Status: ' + status);
                    addDebugLog('Error: ' + error);
                    addDebugLog('HTTP Status: ' + xhr.status);
                    addDebugLog('HTTP Status Text: ' + xhr.statusText);
                    addDebugLog('Response Text: ' + xhr.responseText);
                    addDebugLog('Response Headers: ' + JSON.stringify(xhr.getAllResponseHeaders()));

                    // レスポンスの詳細分析
                    try {
                        var responseJson = JSON.parse(xhr.responseText);
                        addDebugLog('レスポンスJSON解析成功: ' + JSON.stringify(responseJson));
                    } catch (e) {
                        addDebugLog('レスポンスJSON解析失敗: ' + e.message);
                    }

                    var errorMsg = '通信エラーが発生しました';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg = xhr.responseJSON.data;
                    } else if (xhr.statusText) {
                        errorMsg += ' (' + xhr.statusText + ')';
                    } else if (status === 'timeout') {
                        errorMsg = 'タイムアウトエラー（60秒）';
                    } else if (status === 'error') {
                        errorMsg = 'ネットワークエラー';
                    } else if (status === 'abort') {
                        errorMsg = 'リクエストが中断されました';
                    }

                    // エラーメッセージを整形
                    var formattedError = formatErrorMessage(errorMsg);
                    $result.html(formattedError);
                },
                complete: function (xhr, status) {
                    addDebugLog('AJAX処理完了');
                    addDebugLog('最終ステータス: ' + status);
                    addDebugLog('最終HTTPステータス: ' + xhr.status);
                    $btn.prop('disabled', false).text('🔍 キーワード抽出');
                    $loading.hide();
                }
            });
        });

        // 初期化完了ログ
        addDebugLog('=== プラグイン初期化完了 ===');
        addDebugLog('イベントリスナー設定完了');
        addDebugLog('利用可能なDOM要素数: ' + $('*').length);
        addDebugLog('利用可能なスクリプト要素数: ' + $('script').length);

        // キーワードコピー機能
        $(document).on('click', '#copy-keywords-btn', function () {
            var keywords = $('#keywords-textarea').val();
            addDebugLog('キーワードコピー機能実行: "' + keywords + '"');

            if (keywords && keywords.trim()) {
                navigator.clipboard.writeText(keywords).then(function () {
                    addDebugLog('キーワードコピー成功');
                    $('#copy-keywords-btn').text('✓ コピー完了').css('background', '#1e7e34');
                    setTimeout(function () {
                        $('#copy-keywords-btn').text('📋 キーワードをコピー').css('background', '#28a745');
                    }, 2000);
                }).catch(function (err) {
                    addDebugLog('キーワードコピー失敗: ' + err.message);
                    $('#copy-keywords-btn').text('✗ コピー失敗').css('background', '#dc3545');
                    setTimeout(function () {
                        $('#copy-keywords-btn').text('📋 キーワードをコピー').css('background', '#28a745');
                    }, 2000);
                });
            } else {
                addDebugLog('コピー対象のキーワードがありません');
                $('#copy-keywords-btn').text('キーワードなし').css('background', '#ffc107');
                setTimeout(function () {
                    $('#copy-keywords-btn').text('📋 キーワードをコピー').css('background', '#28a745');
                }, 2000);
            }
        });

        // 全ログコピー機能
        $(document).on('click', '#copy-all-logs', function () {
            addDebugLog('全ログコピー機能実行');
            var allLogs = [];
            $('#debug-content div').each(function () {
                var logText = $(this).text().trim();
                if (logText) {
                    allLogs.push(logText);
                }
            });

            var fullLog = allLogs.join('\n');
            addDebugLog('コピー対象ログ数: ' + allLogs.length + '行');
            addDebugLog('コピー対象ログ長: ' + fullLog.length + '文字');

            navigator.clipboard.writeText(fullLog).then(function () {
                addDebugLog('ログコピー成功');
                $('#copy-all-logs').text('✓ コピー完了').css('background', '#28a745');
                setTimeout(function () {
                    $('#copy-all-logs').text('全ログコピー').css('background', '#0073aa');
                }, 2000);
            }).catch(function (err) {
                addDebugLog('ログコピー失敗: ' + err.message);
                $('#copy-all-logs').text('✗ コピー失敗').css('background', '#dc3545');
                setTimeout(function () {
                    $('#copy-all-logs').text('全ログコピー').css('background', '#0073aa');
                }, 2000);
            });
        });
    });

})(jQuery);
