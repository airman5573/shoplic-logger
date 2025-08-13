jQuery(document).ready(function($) {

    // 로그 비우기
    $(document).on('click', '.sl-clear-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = button.data('date');
        var logType = button.data('log-type');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_clear_log',
                plugin: plugin,
                date: date,
                log_type: logType,
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 파일 내용이 비워졌으므로 로그를 새로고침
                    card.find('.sl-log-content').html('<p>로그가 없습니다.</p>');
                    card.find('.sl-log-size').text('0 B');
                } else {
                    // Show detailed error message from server
                    alert('전체 비우기에 실패했습니다: ' + (response.data || 'Unknown error'));
                }
                card.removeClass('sl-loading');
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                console.error('AJAX Error:', xhr.responseText);
                alert('전체 비우기에 실패했습니다: ' + error);
            }
        });
    });
    
    // 로그 파일 삭제
    $(document).on('click', '.sl-delete-file', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = button.data('date');
        var logType = button.data('log-type');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_delete_file',
                plugin: plugin,
                date: date,
                log_type: logType,
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 파일이 삭제되었으므로 날짜 선택기에서 해당 날짜 제거
                    var option = card.find('.sl-log-date-select option[value="' + date + '"]');
                    option.remove();
                    
                    // 다른 날짜가 있으면 첫 번째 날짜로 자동 전환
                    var newDate = card.find('.sl-log-date-select option:first').val();
                    if (newDate) {
                        card.find('.sl-log-date-select').val(newDate).trigger('change');
                    } else {
                        // 모든 로그가 삭제되면 카드 제거
                        card.fadeOut(function() {
                            card.remove();
                        });
                    }
                } else {
                    card.removeClass('sl-loading');
                    alert('파일 삭제에 실패했습니다.');
                }
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('파일 삭제에 실패했습니다.');
            }
        });
    });
    
    // 로그 복사
    $(document).on('click', '.sl-copy-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = button.data('date');
        var logType = button.data('log-type');
        
        // Get content via AJAX to ensure we get the exact file content
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_copy_log',
                plugin: plugin,
                date: date,
                log_type: logType,
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.content) {
                    var content = response.data.content;
                    
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(content).then(function() {
                            var originalText = button.text();
                            button.text('✓ 복사됨');
                            setTimeout(function() {
                                button.text(originalText);
                            }, 2000);
                        });
                    } else {
                        // 폴백
                        var textArea = $('<textarea>').val(content).css({
                            position: 'fixed',
                            left: '-999999px'
                        }).appendTo('body');
                        textArea[0].select();
                        document.execCommand('copy');
                        textArea.remove();
                        
                        var originalText = button.text();
                        button.text('✓ 복사됨');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    }
                }
            }
        });
    });
    
    // 로그 새로고침
    $(document).on('click', '.sl-refresh-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = card.find('.sl-log-date-select').val();
        var logType = button.data('log-type');
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_log',
            plugin: plugin,
            date: date,
            log_type: logType,
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-log-content').html(response.data.content);
                card.find('.sl-log-size').text(response.data.size);
                card.trigger('sl-content-updated');
            }
            card.removeClass('sl-loading');
        });
    });
    
    // 날짜 변경
    $(document).on('change', '.sl-log-date-select', function() {
        var select = $(this);
        var card = select.closest('.sl-log-card');
        var plugin = card.find('.sl-refresh-log').data('plugin');
        var date = select.val();
        var logType = card.data('log-type');
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_log',
            plugin: plugin,
            date: date,
            log_type: logType,
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-log-content').html(response.data.content);
                card.find('.sl-log-size').text(response.data.size);
                
                // data-date 속성 업데이트
                card.find('.sl-clear-log, .sl-copy-log, .sl-refresh-log, .sl-delete-file').attr('data-date', date);
            }
            card.removeClass('sl-loading');
        });
    });
    
    // debug.log 비우기
    $(document).on('click', '.sl-clear-debug-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_clear_debug_log',
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    card.find('.sl-debug-log-content').html('<p>debug.log 파일이 없습니다.</p>');
                    card.find('.sl-debug-log-size').text('0 B');
                } else {
                    alert(response.data || '비우기에 실패했습니다.');
                }
                card.removeClass('sl-loading');
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('전체 비우기에 실패했습니다.');
            }
        });
    });
    
    // debug.log 복사
    $(document).on('click', '.sl-copy-debug-log', function() {
        var button = $(this);
        var content = button.closest('.sl-log-card').find('.sl-debug-log-content').text();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(function() {
                var originalText = button.text();
                button.text('✓ 복사됨');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        } else {
            // 폴백
            var textArea = $('<textarea>').val(content).css({
                position: 'fixed',
                left: '-999999px'
            }).appendTo('body');
            textArea[0].select();
            document.execCommand('copy');
            textArea.remove();
            
            var originalText = button.text();
            button.text('✓ 복사됨');
            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        }
    });
    
    // debug.log 새로고침
    $(document).on('click', '.sl-refresh-debug-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_debug_log',
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-debug-log-content').html(response.data.content);
                card.find('.sl-debug-log-size').text(response.data.size);
                
                // 원본 컨텐츠 저장
                card.find('.sl-debug-log-content').data('original-content', response.data.content);
            }
            card.removeClass('sl-loading');
        });
    });
    
    // Debug log 필터 버튼 클릭
    $(document).on('click', '.sl-debug-filter-btn', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        
        // 토글 동작
        button.toggleClass('active');
        
        // 필터 적용
        applyDebugFilters(card);
    });
    
    // Debug log 필터 적용 함수
    function applyDebugFilters(card) {
        var logContent = card.find('.sl-debug-log-content');
        var originalContent = logContent.data('original-content');
        var activeButtons = card.find('.sl-debug-filter-btn.active');
        
        if (!originalContent) {
            originalContent = logContent.html();
            logContent.data('original-content', originalContent);
        }
        
        if (activeButtons.length === 0) {
            // 모든 필터 해제
            logContent.html(originalContent);
            return;
        }
        
        // 선택된 타입들 수집
        var selectedTypes = [];
        activeButtons.each(function() {
            selectedTypes.push($(this).data('type'));
        });
        
        // 필터 적용
        var tempDiv = $('<div>').html(originalContent);
        var lines = tempDiv.find('.sl-debug-line');
        var hasMatchingLines = false;
        
        lines.each(function() {
            var line = $(this);
            var lineType = line.data('debug-type');
            
            // 선택된 타입 중 하나라도 일치하면 표시
            if (selectedTypes.indexOf(lineType) !== -1) {
                hasMatchingLines = true;
            } else {
                line.hide();
            }
        });
        
        if (hasMatchingLines) {
            logContent.html(tempDiv.html());
        } else {
            logContent.html('<p style="color: #666; padding: 20px; text-align: center;">선택한 필터 조건에 맞는 로그가 없습니다.</p>');
        }
    }
    
    // Debug log 필터 모두 해제
    $(document).on('click', '.sl-debug-filter-clear', function() {
        var card = $(this).closest('.sl-log-card');
        var logContent = card.find('.sl-debug-log-content');
        var originalContent = logContent.data('original-content');
        
        // 모든 필터 버튼 비활성화
        card.find('.sl-debug-filter-btn').removeClass('active');
        
        // 원본 컨텐츠 복원
        if (originalContent) {
            logContent.html(originalContent);
        }
    });
    
    // 모든 로그 새로고침
    $(document).on('click', '.sl-refresh-all-logs', function() {
        var button = $(this);
        var originalText = button.html();
        
        // 버튼 비활성화 및 로딩 표시
        button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> 새로고침 중...');
        
        // 모든 로그 카드 찾기
        var allCards = $('.sl-log-card');
        var refreshPromises = [];
        
        allCards.each(function() {
            var card = $(this);
            var plugin = card.data('plugin');
            
            if (plugin === 'debug-log') {
                // debug.log 새로고침
                card.addClass('sl-loading');
                
                var promise = $.post(sl_ajax.ajax_url, {
                    action: 'sl_refresh_debug_log',
                    nonce: sl_ajax.nonce
                }).done(function(response) {
                    if (response.success) {
                        card.find('.sl-debug-log-content').html(response.data.content);
                        card.find('.sl-debug-log-size').text(response.data.size);
                        card.find('.sl-debug-log-content').data('original-content', response.data.content);
                    }
                }).always(function() {
                    card.removeClass('sl-loading');
                });
                
                refreshPromises.push(promise);
            } else {
                // 일반 플러그인 로그 새로고침
                var logType = card.data('log-type');
                var date = card.find('.sl-log-date-select').val();
                
                if (date) {
                    card.addClass('sl-loading');
                    
                    var promise = $.post(sl_ajax.ajax_url, {
                        action: 'sl_refresh_log',
                        plugin: plugin,
                        date: date,
                        log_type: logType,
                        nonce: sl_ajax.nonce
                    }).done(function(response) {
                        if (response.success) {
                            card.find('.sl-log-content').html(response.data.content);
                            card.find('.sl-log-size').text(response.data.size);
                            card.trigger('sl-content-updated');
                        }
                    }).always(function() {
                        card.removeClass('sl-loading');
                    });
                    
                    refreshPromises.push(promise);
                }
            }
        });
        
        // 모든 새로고침이 완료되면 버튼 복원
        $.when.apply($, refreshPromises).always(function() {
            button.prop('disabled', false).html(originalText);
        });
    });
    
    // 모든 로그 지우기
    $(document).on('click', '.sl-clear-all-logs', function() {
        var button = $(this);
        var originalText = button.html();
        
        // 버튼 비활성화 및 로딩 표시
        button.prop('disabled', true).html('<span class="dashicons dashicons-trash spin"></span> 지우는 중...');
        
        // 모든 로그 카드 찾기
        var allCards = $('.sl-log-card');
        var clearPromises = [];
        
        allCards.each(function() {
            var card = $(this);
            var plugin = card.data('plugin');
            
            if (plugin === 'debug-log') {
                // debug.log 지우기
                card.addClass('sl-loading');
                
                var promise = $.ajax({
                    url: sl_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_clear_debug_log',
                        nonce: sl_ajax.nonce
                    }
                }).done(function(response) {
                    if (response.success) {
                        card.find('.sl-debug-log-content').html('<p>debug.log 파일이 없습니다.</p>');
                        card.find('.sl-debug-log-size').text('0 B');
                    }
                }).always(function() {
                    card.removeClass('sl-loading');
                });
                
                clearPromises.push(promise);
            } else {
                // 일반 플러그인 로그 지우기
                var logType = card.data('log-type');
                var date = card.find('.sl-log-date-select').val();
                
                if (date) {
                    card.addClass('sl-loading');
                    
                    var promise = $.ajax({
                        url: sl_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'sl_clear_log',
                            plugin: plugin,
                            date: date,
                            log_type: logType,
                            nonce: sl_ajax.nonce
                        }
                    }).done(function(response) {
                        if (response.success) {
                            card.find('.sl-log-content').html('<p>로그가 없습니다.</p>');
                            card.find('.sl-log-size').text('0 B');
                        }
                    }).always(function() {
                        card.removeClass('sl-loading');
                    });
                    
                    clearPromises.push(promise);
                }
            }
        });
        
        // 모든 지우기가 완료되면 버튼 복원
        $.when.apply($, clearPromises).always(function() {
            button.prop('disabled', false).html(originalText);
            
            // 성공 메시지 표시
            var successMsg = $('<span style="color: #46b450; margin-left: 10px;">✓ 모든 로그가 지워졌습니다.</span>');
            button.after(successMsg);
            setTimeout(function() {
                successMsg.fadeOut(function() {
                    successMsg.remove();
                });
            }, 3000);
        });
    });
});