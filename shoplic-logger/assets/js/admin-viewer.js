jQuery(document).ready(function($) {
    // 원본 컨텐츠 저장
    $(".sl-log-content").each(function() {
        $(this).data("original-content", $(this).html());
    });
    
    // 필터 버튼 클릭 이벤트 (태그, 레벨, 클래스, 함수)
    $(document).on("click", ".sl-filter-tag-btn, .sl-filter-level-btn, .sl-filter-class-btn, .sl-filter-function-btn", function() {
        var button = $(this);
        var card = button.closest(".sl-log-card");
        
        // 토글 동작
        button.toggleClass("active");
        
        // 필터 적용
        applyMultipleFilters(card);
    });
    
    // 다중 필터 적용 함수
    function applyMultipleFilters(card) {
        var logContent = card.find(".sl-log-content");
        var originalContent = logContent.data("original-content");
        var activeTagButtons = card.find(".sl-filter-tag-btn.active");
        var activeLevelButtons = card.find(".sl-filter-level-btn.active");
        var activeClassButtons = card.find(".sl-filter-class-btn.active");
        var activeFunctionButtons = card.find(".sl-filter-function-btn.active");
        var totalActiveButtons = activeTagButtons.length + activeLevelButtons.length + activeClassButtons.length + activeFunctionButtons.length;
        
        if (totalActiveButtons === 0) {
            // 모든 필터 해제
            logContent.html(originalContent);
            card.find(".sl-filter-info").remove();
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
            return;
        }
        
        // 선택된 필터들 수집
        var selectedTags = [];
        var selectedLevels = [];
        var selectedClasses = [];
        var selectedFunctions = [];
        
        activeTagButtons.each(function() {
            selectedTags.push($(this).data("tag"));
        });
        activeLevelButtons.each(function() {
            selectedLevels.push($(this).data("level"));
        });
        activeClassButtons.each(function() {
            selectedClasses.push($(this).data("class"));
        });
        activeFunctionButtons.each(function() {
            selectedFunctions.push($(this).data("function"));
        });
        
        // 필터 모드 확인 (OR 또는 AND)
        var filterMode = card.find('input[name^="filter-mode"]:checked').val() || 'or';
        
        // 필터 적용
        var lines = originalContent.split("\n");
        var filteredLines = [];
        var inMatchingEntry = false;
        var entryCount = 0;
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            
            // 새로운 로그 항목의 시작을 확인
            if (line.match(/^\[\d{4}-\d{2}-\d{2}/)) {
                inMatchingEntry = false;
                var lineMatches = {
                    tags: false,
                    level: false,
                    class: false,
                    function: false
                };
                
                // 태그 검색
                if (selectedTags.length === 0) {
                    lineMatches.tags = true;
                } else {
                    var tagMatch = line.match(/data-tag="([^"]+)"/g);
                    if (tagMatch) {
                        var lineTags = [];
                        for (var j = 0; j < tagMatch.length; j++) {
                            var tag = tagMatch[j].replace(/data-tag="/, '').replace(/"/, '');
                            lineTags.push(tag);
                        }
                        
                        if (filterMode === 'or') {
                            for (var k = 0; k < selectedTags.length; k++) {
                                if (lineTags.indexOf(selectedTags[k]) !== -1) {
                                    lineMatches.tags = true;
                                    break;
                                }
                            }
                        } else {
                            var matchCount = 0;
                            for (var k = 0; k < selectedTags.length; k++) {
                                if (lineTags.indexOf(selectedTags[k]) !== -1) {
                                    matchCount++;
                                }
                            }
                            if (matchCount === selectedTags.length) {
                                lineMatches.tags = true;
                            }
                        }
                    }
                }
                
                // 로그 레벨 검색
                if (selectedLevels.length === 0) {
                    lineMatches.level = true;
                } else {
                    var levelMatch = line.match(/\[(LOG|ERROR|INFO|DEBUG|WARNING)\]/);
                    if (levelMatch && selectedLevels.indexOf(levelMatch[1]) !== -1) {
                        lineMatches.level = true;
                    }
                }
                
                // 클래스 검색
                if (selectedClasses.length === 0) {
                    lineMatches.class = true;
                } else {
                    var classMatch = line.match(/\[([^:]+)::([^\]]+)\]/);
                    if (classMatch && selectedClasses.indexOf(classMatch[1]) !== -1) {
                        lineMatches.class = true;
                    }
                }
                
                // 함수 검색
                if (selectedFunctions.length === 0) {
                    lineMatches.function = true;
                } else {
                    var funcMatch = line.match(/\[([^:]+)::([^\]]+)\]/);
                    if (funcMatch && selectedFunctions.indexOf(funcMatch[2]) !== -1) {
                        lineMatches.function = true;
                    } else {
                        // 단독 함수 검색
                        var standaloneFuncMatch = line.match(/\s\[([^\]:]+)\]\s/);
                        if (standaloneFuncMatch && selectedFunctions.indexOf(standaloneFuncMatch[1]) !== -1) {
                            lineMatches.function = true;
                        }
                    }
                }
                
                // 필터 모드에 따른 전체 매칭 확인
                if (filterMode === 'or') {
                    // OR 모드: 각 필터 타입 내에서는 OR, 타입 간에는 AND
                    if (lineMatches.tags && lineMatches.level && lineMatches.class && lineMatches.function) {
                        inMatchingEntry = true;
                        entryCount++;
                    }
                } else {
                    // AND 모드: 모든 필터가 매칭되어야 함
                    if (lineMatches.tags && lineMatches.level && lineMatches.class && lineMatches.function) {
                        inMatchingEntry = true;
                        entryCount++;
                    }
                }
            }
            
            if (inMatchingEntry) {
                filteredLines.push(line);
            }
        }
        
        if (filteredLines.length > 0) {
            logContent.html(filteredLines.join("\n"));
            
            // 필터 정보 추가
            card.find(".sl-filter-info").remove();
            var filterTexts = [];
            if (selectedLevels.length > 0) filterTexts.push('레벨: ' + selectedLevels.join(', '));
            if (selectedClasses.length > 0) filterTexts.push('클래스: ' + selectedClasses.join(', '));
            if (selectedFunctions.length > 0) filterTexts.push('함수: ' + selectedFunctions.join(', '));
            if (selectedTags.length > 0) filterTexts.push('태그: ' + selectedTags.join(', '));
            
            var filterText = filterTexts.join(' | ');
            var modeText = filterMode === 'or' ? 'OR' : 'AND';
            card.find(".sl-log-actions").after('<div class="sl-filter-info" style="background: #f0f0f0; padding: 8px 15px; margin: 10px 0; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;"><span>필터링됨 (' + modeText + '): <strong>' + filterText + '</strong> (' + entryCount + '개 항목)</span><a href="#" class="sl-clear-filter" style="color: #d63638;">모든 필터 해제</a></div>');
            
            // 선택된 태그들 하이라이트
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
            selectedTags.forEach(function(tag) {
                card.find('.sl-tag[data-tag="' + tag + '"]').addClass("sl-tag-active").css("background-color", "#d63638");
            });
        } else {
            logContent.html('<p style="color: #666; padding: 20px; text-align: center;">선택한 필터 조건에 맞는 로그가 없습니다.</p>');
            card.find(".sl-filter-info").remove();
        }
    }
    
    // 모든 필터 해제 버튼
    $(document).on("click", ".sl-filter-clear-all", function() {
        var card = $(this).closest(".sl-log-card");
        var logContent = card.find(".sl-log-content");
        var originalContent = logContent.data("original-content");
        
        // 모든 필터 버튼 비활성화
        card.find(".sl-filter-tag-btn, .sl-filter-level-btn, .sl-filter-class-btn, .sl-filter-function-btn").removeClass("active");
        
        // 원본 컨텐츠 복원
        logContent.html(originalContent);
        card.find(".sl-filter-info").remove();
        card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
    });
    
    // 태그 클릭 이벤트
    $(document).on("click", ".sl-tag", function() {
        var tag = $(this).data("tag");
        var card = $(this).closest(".sl-log-card");
        var filterButton = card.find('.sl-filter-tag-btn[data-tag="' + tag + '"]');
        
        if (filterButton.length) {
            filterButton.trigger("click");
        }
    });
    
    // 필터 해제 링크
    $(document).on("click", ".sl-clear-filter", function(e) {
        e.preventDefault();
        var card = $(this).closest(".sl-log-card");
        card.find(".sl-filter-clear-all").trigger("click");
    });
    
    // 필터 모드 변경 이벤트 (OR/AND)
    $(document).on("change", 'input[name^="filter-mode"]', function() {
        var card = $(this).closest(".sl-log-card");
        // 활성 필터가 있으면 다시 적용
        if (card.find(".sl-filter-tag-btn.active, .sl-filter-level-btn.active, .sl-filter-class-btn.active, .sl-filter-function-btn.active").length > 0) {
            applyMultipleFilters(card);
        }
    });
    
    // 날짜 변경 시 원본 컨텐츠 업데이트
    $(document).on("sl-content-updated", ".sl-log-card", function() {
        var logContent = $(this).find(".sl-log-content");
        logContent.data("original-content", logContent.html());
        
        // 태그 필터 드롭다운 업데이트
        var content = logContent.html();
        var availableTags = {};
        
        // HTML에서 data-tag 속성을 찾아서 태그 추출
        var tagMatches = content.match(/data-tag="([^"]+)"/g);
        if (tagMatches) {
            tagMatches.forEach(function(match) {
                var tag = match.replace(/data-tag="/, '').replace(/"/, '');
                if (tag) {
                    availableTags[tag] = true;
                }
            });
        }
        
        var tagArray = Object.keys(availableTags).sort();
        var filterWrapper = $(this).find(".sl-tag-filter-wrapper");
        var filterButtons = $(this).find(".sl-tag-filter-buttons");
        
        if (tagArray.length > 0 && filterWrapper.length === 0) {
            // 필터 UI 전체 추가
            var plugin = $(this).data("plugin");
            var filterHtml = '<div class="sl-tag-filter-wrapper">' +
                '<div class="sl-tag-filter-controls">' +
                '<button type="button" class="button button-small sl-filter-clear-all">모든 필터 해제</button>' +
                '<div class="sl-filter-mode">' +
                '<label><input type="radio" name="filter-mode-' + plugin + '" value="or" checked><span>OR (하나라도)</span></label>' +
                '<label><input type="radio" name="filter-mode-' + plugin + '" value="and"><span>AND (모두)</span></label>' +
                '</div></div>' +
                '<div class="sl-tag-filter-buttons">';
            tagArray.forEach(function(tag) {
                filterHtml += '<button type="button" class="button button-small sl-filter-tag-btn" data-tag="' + tag + '">' + tag + '</button>';
            });
            filterHtml += '</div></div>';
            $(this).find(".sl-log-date-selector").after(filterHtml);
        } else if (filterButtons.length > 0) {
            // 기존 버튼만 업데이트
            var activeTags = [];
            filterButtons.find(".sl-filter-tag-btn.active").each(function() {
                activeTags.push($(this).data("tag"));
            });
            filterButtons.find(".sl-filter-tag-btn").remove();
            
            tagArray.forEach(function(tag) {
                var button = $('<button type="button" class="button button-small sl-filter-tag-btn" data-tag="' + tag + '">' + tag + '</button>');
                if (activeTags.indexOf(tag) !== -1) {
                    button.addClass("active");
                }
                filterButtons.append(button);
            });
            
            // 활성 필터가 있으면 다시 적용
            if (activeTags.length > 0) {
                applyMultipleFilters($(this));
            }
        }
    });

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
                }
                card.removeClass('sl-loading');
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('전체 비우기에 실패했습니다.');
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
                card.trigger('sl-content-updated');
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
});