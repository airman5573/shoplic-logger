<?php
/**
 * 쇼플릭 로거 - 시스템 정보 리포터 클래스
 *
 * @package ShoplLogger
 * @subpackage Admin
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_SysInfo_Reporter
 * 시스템 정보 수집 및 표시 처리
 */
class SL_SysInfo_Reporter {
    
    /**
     * 형식을 위한 열 너비
     */
    protected $column_width = 35;
    
    /**
     * 시스템 정보 가져오기
     *
     * @return array
     */
    protected function get_info() {
        global $wp_version, $wp_db_version;
        
        return array(
            'wordpress_info' => array(
                'title' => '워드프레스 정보',
                'data' => array(
                    '워드프레스 버전'    => $wp_version,
                    '워드프레스 DB 버전' => $wp_db_version,
                    '<span title="사이트의 기본 주소">사이트 URL</span>'             => site_url(),
                    '<span title="홈페이지 주소">홈 URL</span>'             => home_url(),
                    '<span title="워드프레스 언어 설정">WP 로케일</span>'            => get_locale(),
                    '<span title="워드프레스 시간대 설정">WP 타임존</span>'          => get_option( 'timezone_string' ) ?: 'UTC',
                    'WP 날짜 형식'       => get_option( 'date_format' ),
                    'WP 시간 형식'       => get_option( 'time_format' ),
                ),
            ),
            'server_info' => array(
                'title' => '서버 정보',
                'data' => array(
                    '<span title="서버에서 실행 중인 PHP 프로그래밍 언어의 버전">PHP 버전</span>'      => PHP_VERSION,
                    '<span title="데이터베이스 관리 시스템">MySQL 버전</span>'    => $this->get_mysql_version(),
                    '<span title="웹 서비스를 제공하는 소프트웨어">웹 서버</span>'       => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                    '<span title="HTTP 프로토콜 버전">서버 프로토콜</span>'  => wp_get_server_protocol(),
                    '<span title="보안 연결 사용 여부">HTTPS</span>'            => is_ssl() ? '예' : '아니오',
                    '서버 시간'      => date( 'Y-m-d H:i:s' ),
                ),
            ),
            'php_info' => array(
                'title' => 'PHP 설정',
                'data' => array(
                    '<span title="PHP가 사용할 수 있는 최대 메모리 크기">PHP 메모리 제한</span>'     => ini_get( 'memory_limit' ),
                    '<span title="현재 사용 중인 메모리 양">PHP 메모리 사용량</span>'     => $this->get_memory_usage(),
                    '<span title="입력할 수 있는 최대 변수 개수">PHP 최대 입력 변수</span>'   => ini_get( 'max_input_vars' ),
                    '<span title="POST 요청으로 전송할 수 있는 최대 크기">PHP POST 최대 크기</span>'    => ini_get( 'post_max_size' ),
                    '<span title="업로드할 수 있는 파일의 최대 크기">PHP 업로드 최대 크기</span>'  => ini_get( 'upload_max_filesize' ),
                    '<span title="스크립트가 실행될 수 있는 최대 시간">PHP 최대 실행 시간</span>' => ini_get( 'max_execution_time' ) . '초',
                    '<span title="오류를 화면에 표시할지 여부">PHP 오류 표시</span>'   => ini_get( 'display_errors' ) ? '켜짐' : '꺼짐',
                    '<span title="URL 통신을 위한 라이브러리">PHP cURL 지원</span>'     => function_exists( 'curl_init' ) ? '예' : '아니오',
                    '<span title="이미지 처리를 위한 라이브러리">PHP GD 지원</span>'       => function_exists( 'gd_info' ) ? '예' : '아니오',
                ),
            ),
            'wordpress_config' => array(
                'title' => '워드프레스 설정',
                'data' => array(
                    '<span title="워드프레스가 사용할 수 있는 메모리 제한">WP 메모리 제한</span>'      => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '기본값',
                    '<span title="관리자 페이지에서의 최대 메모리 제한">WP 최대 메모리 제한</span>'  => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '기본값',
                    'WP 디버그'             => defined( 'WP_DEBUG' ) && WP_DEBUG ? '활성화' : '비활성화',
                    'WP 디버그 로그'         => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? '활성화' : '비활성화',
                    'WP 디버그 표시'     => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? '활성화' : '비활성화',
                    '스크립트 디버그'         => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '활성화' : '비활성화',
                    '<span title="데이터베이스 문자 인코딩">DB 문자셋</span>'           => defined( 'DB_CHARSET' ) ? DB_CHARSET : '정의되지 않음',
                    '<span title="데이터베이스 정렬 규칙">DB 콜레이션</span>'           => defined( 'DB_COLLATE' ) ? DB_COLLATE : '정의되지 않음',
                ),
            ),
            'shoplic_logger_info' => array(
                'title' => '쇼플릭 로거 정보',
                'data' => array(
                    '로그 디렉토리'        => SL::get_log_dir(),
                    '<span title="로그 디렉토리에 쓰기 가능 여부">로그 디렉토리 쓰기 가능</span>' => is_writable( SL::get_log_dir() ) ? '예' : '아니오',
                    '<span title="파일 시스템에서의 접근 권한">로그 파일 권한</span>' => $this->get_log_permissions(),
                    '디버그 모드'           => SL::is_debug_mode() ? '활성화' : '비활성화',
                    '<span title="보관할 최대 로그 파일 개수">최대 로그 파일</span>'        => get_option( 'sl_max_log_files', 30 ),
                    '<span title="로그 파일 교체 주기">로그 로테이션</span>'         => get_option( 'sl_log_rotation', 'daily' ) === 'daily' ? '매일' : get_option( 'sl_log_rotation', 'daily' ),
                ),
            ),
        );
    }
    
    /**
     * MySQL 버전 가져오기
     *
     * @return string
     */
    protected function get_mysql_version() {
        global $wpdb;
        return $wpdb->db_version();
    }
    
    /**
     * 메모리 사용량 가져오기
     *
     * @return string
     */
    protected function get_memory_usage() {
        $memory = memory_get_usage( true );
        $memory_limit = $this->convert_to_bytes( ini_get( 'memory_limit' ) );
        $percentage = round( ( $memory / $memory_limit ) * 100, 2 );
        return size_format( $memory ) . ' / ' . ini_get( 'memory_limit' ) . ' (' . $percentage . '%)';
    }
    
    /**
     * 문자열을 바이트로 변환
     *
     * @param string $value
     * @return int
     */
    protected function convert_to_bytes( $value ) {
        $value = trim( $value );
        $last = strtolower( $value[ strlen( $value ) - 1 ] );
        $value = (int) $value;
        
        switch ( $last ) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * 로그 파일 권한 가져오기
     *
     * @return string
     */
    protected function get_log_permissions() {
        $log_dir = SL::get_log_dir();
        if ( file_exists( $log_dir ) ) {
            $perms = fileperms( $log_dir );
            return substr( sprintf( '%o', $perms ), -4 );
        }
        return 'N/A';
    }
    
    /**
     * 시스템 정보 표시
     */
    public static function display() {
        $reporter = new self();
        ?>
        <div class="sl-sysinfo-container">
            <p class="sl-sysinfo-actions">
                <button type="button" class="button button-primary" id="sl-copy-sysinfo">
                    <span title="시스템 정보를 컴퓨터의 임시 저장소에 복사">클립보드에 복사</span>
                </button>
                <span class="sl-copy-message" style="display:none;color:green;margin-left:10px;">
                    복사됨!
                </span>
            </p>
            
            <div id="sl-sysinfo-content" class="sl-sysinfo-grid">
                <?php
                foreach ( $reporter->get_info() as $section_key => $section ) {
                    ?>
                    <div class="postbox">
                        <h2 class="hndle"><span><?php echo esc_html( $section['title'] ); ?></span></h2>
                        <div class="inside">
                            <table class="widefat striped">
                                <tbody>
                                    <?php
                                    foreach ( $section['data'] as $label => $value ) {
                                        ?>
                                        <tr>
                                            <th scope="row"><?php echo wp_kses( $label, array( 'span' => array( 'title' => array() ) ) ); ?></th>
                                            <td><?php echo esc_html( $value ); ?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div style="display:none;">
                <textarea id="sl-sysinfo-text" readonly><?php echo esc_textarea( $reporter->get_plain_text_report() ); ?></textarea>
            </div>
        </div>
        
        <style>
        .sl-sysinfo-container {
            margin-top: 20px;
        }
        .sl-sysinfo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .sl-sysinfo-grid .postbox {
            margin: 0;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            background: #fff;
            position: relative;
        }
        .sl-sysinfo-grid .postbox .hndle {
            font-size: 14px;
            padding: 8px 12px;
            margin: 0;
            line-height: 1.4;
            border-bottom: 1px solid #c3c4c7;
            background: #f6f7f7;
            color: #32373c;
            font-weight: 600;
            cursor: default;
        }
        .sl-sysinfo-grid .postbox .inside {
            padding: 0;
            margin: 0;
        }
        .sl-sysinfo-grid table.widefat {
            border: 0;
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        .sl-sysinfo-grid table.widefat thead,
        .sl-sysinfo-grid table.widefat tfoot {
            display: none;
        }
        .sl-sysinfo-grid table.widefat tr:first-child td,
        .sl-sysinfo-grid table.widefat tr:first-child th {
            border-top: 0;
        }
        .sl-sysinfo-grid table.widefat th {
            font-weight: 400;
            color: #666;
            padding: 10px 12px;
            width: 40%;
            vertical-align: top;
            border-left: 4px solid #f6f7f7;
        }
        .sl-sysinfo-grid table.widefat td {
            padding: 10px 12px;
            color: #2c3338;
            word-break: break-word;
        }
        .sl-sysinfo-grid table.striped > tbody > tr:nth-child(odd) {
            background-color: #f6f7f7;
        }
        .sl-sysinfo-grid table.striped > tbody > tr:nth-child(even) {
            background-color: #fff;
        }
        .sl-sysinfo-actions {
            margin-bottom: 15px;
        }
        .sl-sysinfo-actions .button {
            height: 32px;
            line-height: 30px;
            padding: 0 12px;
        }
        /* Hover effects */
        .sl-sysinfo-grid .postbox:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }
        /* Responsive: 2 columns on medium screens */
        @media (max-width: 1400px) and (min-width: 783px) {
            .sl-sysinfo-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        /* 1 column on small screens */
        @media (max-width: 782px) {
            .sl-sysinfo-grid {
                grid-template-columns: 1fr;
            }
            .sl-sysinfo-grid .postbox .hndle {
                padding: 12px;
            }
            .sl-sysinfo-grid table.widefat th,
            .sl-sysinfo-grid table.widefat td {
                padding: 12px;
            }
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sl-copy-sysinfo').on('click', function() {
                var button = $(this);
                var text = $('#sl-sysinfo-text').val();
                var message = $('.sl-copy-message');
                
                // Disable button temporarily
                button.prop('disabled', true);
                
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        message.fadeIn().delay(2000).fadeOut();
                        button.prop('disabled', false);
                    }).catch(function() {
                        // Fallback
                        copyFallback(text);
                        button.prop('disabled', false);
                    });
                } else {
                    // Fallback
                    copyFallback(text);
                    button.prop('disabled', false);
                }
                
                function copyFallback(text) {
                    var textArea = $('<textarea>').val(text).css({
                        position: 'fixed',
                        left: '-999999px'
                    }).appendTo('body');
                    textArea[0].select();
                    try {
                        document.execCommand('copy');
                        message.fadeIn().delay(2000).fadeOut();
                    } catch (err) {
                        alert('복사에 실패했습니다. 수동으로 복사해주세요.');
                    }
                    textArea.remove();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * 텍스트 리포트 가져오기
     *
     * @return string
     */
    public function get_plain_text_report() {
        $report = "=== 파일 로거 시스템 정보 ===\n";
        $report .= "생성일: " . current_time( 'mysql' ) . "\n\n";
        
        foreach ( $this->get_info() as $section ) {
            $report .= "--- " . $section['title'] . " ---\n";
            foreach ( $section['data'] as $label => $value ) {
                $label = str_pad( $label . ':', $this->column_width );
                $report .= $label . $value . "\n";
            }
            $report .= "\n";
        }
        
        return $report;
    }
}