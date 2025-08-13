<?php
/**
 * 쇼플릭 로거 - 관리자 뷰어 클래스
 *
 * @package ShoplLogger
 * @subpackage Admin
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_Admin_Viewer
 * 로그를 볼 수 있는 관리자 인터페이스 처리
 */
class SL_Admin_Viewer {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_menu_page(
            '쇼플릭 로거',
            '쇼플릭 로거',
            'manage_options',
            'shoplic-logger',
            array( $this, 'render_page' ),
            'dashicons-media-text',
            80
        );
    }
    
    /**
     * 스크립트 등록
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_shoplic-logger' !== $hook ) {
            return;
        }
        
        // CSS 파일 등록 및 로드
        wp_enqueue_style(
            'sl-admin-viewer-style',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-viewer.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript 파일 등록 및 로드
        wp_enqueue_script(
            'sl-admin-viewer',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin-viewer.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        
        // 로컬라이즈 데이터
        wp_localize_script( 'sl-admin-viewer', 'sl_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sl_ajax_nonce' )
        ) );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_page() {
        // 현재 탭 가져오기
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'logs';
        ?>
        <div class="wrap">
            <h1>쇼플릭 로거</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=shoplic-logger&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <span title="애플리케이션에서 기록된 이벤트나 오류 메시지">로그</span>
                </a>
                <a href="?page=shoplic-logger&tab=manual" class="nav-tab <?php echo $current_tab === 'manual' ? 'nav-tab-active' : ''; ?>">
                    <span title="쇼플릭 로거 사용 방법 및 예제">사용법</span>
                </a>
                <a href="?page=shoplic-logger&tab=debug-settings" class="nav-tab <?php echo $current_tab === 'debug-settings' ? 'nav-tab-active' : ''; ?>">
                    <span title="개발 중 문제 해결을 위한 워드프레스 디버그 모드 설정">디버그 설정</span>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'manual':
                        $this->render_manual_tab();
                        break;
                        
                    case 'debug-settings':
                        $debug_settings = new SL_Debug_Settings();
                        $debug_settings->render_page();
                        break;
                        
                    
                    case 'logs':
                    default:
                        $this->render_logs_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 로그 탭 컨텐츠 렌더링
     */
    private function render_logs_tab() {
        ?>
        
        <?php
        // 사용 가능한 플러그인 가져오기
        $plugins = $this->get_logged_plugins();
        
        if ( ! empty( $plugins ) || file_exists( WP_CONTENT_DIR . '/debug.log' ) ) : ?>
            <div style="margin: 20px 0;">
                <button type="button" class="button button-primary sl-refresh-all-logs" style="font-size: 14px; padding: 8px 16px;">
                    <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                    모든 로그 새로고침
                </button>
            </div>
            <div id="sl-logs-grid">
                <?php
                foreach ( $plugins as $plugin ) {
                    // 백엔드 로그 카드
                    $this->display_log_card( $plugin, 'backend' );
                    // 프론트엔드 로그 카드
                    $this->display_log_card( $plugin, 'frontend' );
                }
                
                // debug.log 카드를 마지막에 추가
                if ( file_exists( WP_CONTENT_DIR . '/debug.log' ) ) {
                    $this->display_debug_log_card();
                }
                ?>
            </div>
        <?php else : ?>
            <p>아직 로그가 없습니다.</p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * 로그 카드 표시
     */
    private function display_log_card( $plugin, $log_type = 'backend' ) {
        $log_files = $this->get_log_files( $plugin, $log_type );
        
        // 해당 타입의 로그 파일이 없으면 카드를 표시하지 않음
        if ( empty( $log_files ) ) {
            return;
        }
        
        $current_date = $log_files[0]['date'];
        $log_prefix = ( $log_type === 'frontend' ) ? 'fe-log-' : 'log-';
        $log_file = SL_LOG_DIR . '/' . $plugin . '/' . $log_prefix . $current_date . '.log';
        
        $log_type_label = ( $log_type === 'frontend' ) ? 'Frontend Logs' : 'Backend Logs';
        ?>
        <div class="sl-log-card" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-log-type="<?php echo esc_attr( $log_type ); ?>">
            <h3><?php echo esc_html( $plugin ); ?> - <?php echo esc_html( $log_type_label ); ?></h3>
            
            <div class="sl-log-date-selector">
                <select class="sl-log-date-select">
                    <?php foreach ( $log_files as $file ) : ?>
                        <option value="<?php echo esc_attr( $file['date'] ); ?>" <?php selected( $current_date, $file['date'] ); ?>>
                            <?php echo esc_html( $file['date'] ); ?> (<?php echo esc_html( $file['size'] ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="sl-log-actions">
                <button type="button" class="button sl-clear-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>" data-log-type="<?php echo esc_attr( $log_type ); ?>">전체 비우기</button>
                <button type="button" class="button sl-copy-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>" data-log-type="<?php echo esc_attr( $log_type ); ?>">복사</button>
                <button type="button" class="button sl-refresh-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>" data-log-type="<?php echo esc_attr( $log_type ); ?>">새로고침</button>
                <button type="button" class="button sl-delete-file" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>" data-log-type="<?php echo esc_attr( $log_type ); ?>">파일삭제</button>
            </div>
            
            <div class="sl-log-content" data-original-content="">
                <?php
                if ( file_exists( $log_file ) ) {
                    $content = file_get_contents( $log_file );
                    echo $this->format_log_content( $content );
                } else {
                    echo '<p>로그가 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * debug.log 카드 표시
     */
    private function display_debug_log_card() {
        $debug_log_file = WP_CONTENT_DIR . '/debug.log';
        $file_size = file_exists( $debug_log_file ) ? size_format( filesize( $debug_log_file ) ) : '0 B';
        ?>
        <div class="sl-log-card" data-plugin="debug-log">
            <h3>WordPress Debug Log</h3>
            
            <div class="sl-log-date-selector">
                <p style="margin: 5px 0; color: #666;">파일 크기: <span class="sl-debug-log-size"><?php echo esc_html( $file_size ); ?></span></p>
            </div>
            
            <div class="sl-debug-filter-wrapper" style="margin-bottom: 15px; border: 1px solid #c3c4c7; border-radius: 4px; padding: 10px; background: #f6f7f7;">
                <div class="sl-debug-filter-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 13px; color: #50575e;">필터:</span>
                    <button type="button" class="button button-small sl-debug-filter-clear" style="background: #d63638; border-color: #d63638; color: #fff;">모든 필터 해제</button>
                </div>
                <div class="sl-debug-filter-buttons" style="display: flex; gap: 5px;">
                    <button type="button" class="button button-small sl-debug-filter-btn" data-type="notice" style="background: #f0f0f1;">
                        Notice
                    </button>
                    <button type="button" class="button button-small sl-debug-filter-btn" data-type="fatal" style="background: #f0f0f1;">
                        Fatal Error
                    </button>
                    <button type="button" class="button button-small sl-debug-filter-btn" data-type="normal" style="background: #f0f0f1;">
                        Normal Debug
                    </button>
                </div>
            </div>
            
            <div class="sl-log-actions">
                <button type="button" class="button sl-clear-debug-log">전체 비우기</button>
                <button type="button" class="button sl-copy-debug-log">복사</button>
                <button type="button" class="button sl-refresh-debug-log">새로고침</button>
            </div>
            
            <div class="sl-log-content sl-debug-log-content" data-original-content="">
                <?php
                if ( file_exists( $debug_log_file ) ) {
                    // 파일 크기가 너무 크면 마지막 부분만 읽기
                    $max_size = 1024 * 1024; // 1MB
                    $file_size_bytes = filesize( $debug_log_file );
                    
                    if ( $file_size_bytes > $max_size ) {
                        // 파일의 마지막 1MB만 읽기
                        $handle = fopen( $debug_log_file, 'r' );
                        fseek( $handle, -$max_size, SEEK_END );
                        $content = fread( $handle, $max_size );
                        fclose( $handle );
                        
                        // 첫 줄이 잘릴 수 있으므로 첫 개행 문자 이후부터 표시
                        $content = substr( $content, strpos( $content, "\n" ) + 1 );
                        echo '<p style="color: #ff6b6b; margin-bottom: 10px;">⚠️ 파일이 너무 커서 마지막 1MB만 표시합니다.</p>';
                    } else {
                        $content = file_get_contents( $debug_log_file );
                    }
                    
                    echo $this->format_debug_log_content( $content );
                } else {
                    echo '<p>debug.log 파일이 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 플러그인의 로그 파일 가져오기
     */
    private function get_log_files( $plugin, $log_type = 'backend' ) {
        $files = array();
        $dir = SL_LOG_DIR . '/' . $plugin;
        
        if ( is_dir( $dir ) ) {
            // 로그 타입에 따른 파일 패턴
            $log_prefix = ( $log_type === 'frontend' ) ? 'fe-log-' : 'log-';
            $log_files = glob( $dir . '/' . $log_prefix . '*.log' );
            rsort( $log_files ); // 최신 순으로 정렬
            
            foreach ( $log_files as $file ) {
                if ( preg_match( '/' . $log_prefix . '(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches ) ) {
                    $files[] = array(
                        'date' => $matches[1],
                        'size' => size_format( filesize( $file ) ),
                        'path' => $file
                    );
                }
            }
        }
        
        return $files;
    }
    
    /**
     * 로그 내용을 색상으로 형식화
     */
    public function format_log_content( $content ) {
        // 먼저 HTML 이스케이프
        $content = esc_html( $content );
        
        // 로그 레벨별 색상 코드
        $content = preg_replace( '/\[ERROR\]/', '<span style="color: #dc3545;">[ERROR]</span>', $content );
        $content = preg_replace( '/\[WARNING\]/', '<span style="color: #ffc107;">[WARNING]</span>', $content );
        $content = preg_replace( '/\[INFO\]/', '<span style="color: #17a2b8;">[INFO]</span>', $content );
        $content = preg_replace( '/\[DEBUG\]/', '<span style="color: #6c757d;">[DEBUG]</span>', $content );
        $content = preg_replace( '/\[LOG\]/', '<span style="color: #28a745;">[LOG]</span>', $content );
        
        return $content;
    }
    
    /**
     * debug.log 내용을 색상으로 형식화
     */
    public function format_debug_log_content( $content ) {
        // 먼저 HTML 이스케이프
        $content = esc_html( $content );
        
        // 각 줄을 분석하여 타입 추가
        $lines = explode( "\n", $content );
        $formatted_lines = array();
        
        foreach ( $lines as $line ) {
            $line_class = '';
            $line_type = '';
            
            // PHP Notice
            if ( preg_match( '/PHP Notice:/i', $line ) ) {
                $line_class = 'sl-debug-line-notice';
                $line_type = 'notice';
                $line = preg_replace( '/(PHP Notice:)/i', '<span style="color: #ffc107; font-weight: bold;">$1</span>', $line );
            }
            // PHP Fatal error
            elseif ( preg_match( '/PHP Fatal error:/i', $line ) ) {
                $line_class = 'sl-debug-line-fatal';
                $line_type = 'fatal';
                $line = preg_replace( '/(PHP Fatal error:)/i', '<span style="color: #dc3545; font-weight: bold;">$1</span>', $line );
            }
            // PHP Warning
            elseif ( preg_match( '/PHP Warning:/i', $line ) ) {
                $line_class = 'sl-debug-line-warning';
                $line_type = 'fatal'; // Group warnings with fatal for filtering
                $line = preg_replace( '/(PHP Warning:)/i', '<span style="color: #ff6b6b; font-weight: bold;">$1</span>', $line );
            }
            // PHP Parse error
            elseif ( preg_match( '/PHP Parse error:/i', $line ) ) {
                $line_class = 'sl-debug-line-parse-error';
                $line_type = 'fatal';
                $line = preg_replace( '/(PHP Parse error:)/i', '<span style="color: #dc3545; font-weight: bold;">$1</span>', $line );
            }
            // Stack trace
            elseif ( preg_match( '/Stack trace:/i', $line ) || preg_match( '/^\s*#\d+/', $line ) ) {
                $line_class = 'sl-debug-line-trace';
                $line_type = 'normal';
                $line = '<span style="color: #6c757d;">' . $line . '</span>';
            }
            // Normal debug output
            else {
                $line_class = 'sl-debug-line-normal';
                $line_type = 'normal';
            }
            
            // Always wrap lines in div with debug type for consistent filtering
            if ( empty( $line_type ) ) {
                // Empty lines or lines without recognized patterns are treated as normal
                $line_type = 'normal';
                $line_class = 'sl-debug-line-normal';
            }
            
            $formatted_lines[] = '<div class="sl-debug-line ' . $line_class . '" data-debug-type="' . $line_type . '">' . $line . '</div>';
        }
        
        return implode( "\n", $formatted_lines );
    }
    
    /**
     * 로그가 있는 플러그인 목록 가져오기
     */
    private function get_logged_plugins() {
        $plugins = array();
        
        if ( is_dir( SL_LOG_DIR ) ) {
            $dirs = glob( SL_LOG_DIR . '/*', GLOB_ONLYDIR );
            foreach ( $dirs as $dir ) {
                $plugins[] = basename( $dir );
            }
        }
        
        return $plugins;
    }
    
    /**
     * 액션 URL 가져오기
     */
    private function get_action_url( $action, $plugin, $date ) {
        return wp_nonce_url(
            add_query_arg( array(
                'page' => 'shoplic-logger',
                'action' => $action,
                'plugin' => $plugin,
                'date' => $date
            ), admin_url( 'admin.php' ) ),
            'sl_' . $action
        );
    }
    
    /**
     * 액션 처리
     */
    public function handle_actions() {
        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_GET['action'] );
        $plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( $_GET['plugin'] ) : '';
        $date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
        
        if ( ! $plugin || ! $date ) {
            return;
        }
        
        $log_file = SL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        switch ( $action ) {
            case 'download':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_download' ) && file_exists( $log_file ) ) {
                    header( 'Content-Type: text/plain' );
                    header( 'Content-Disposition: attachment; filename="' . $plugin . '-' . $date . '.log"' );
                    header( 'Content-Length: ' . filesize( $log_file ) );
                    readfile( $log_file );
                    exit;
                }
                break;
                
            case 'clear':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_clear' ) && file_exists( $log_file ) ) {
                    unlink( $log_file );
                    wp_redirect( add_query_arg( array(
                        'page' => 'shoplic-logger',
                        'plugin' => $plugin,
                        'cleared' => 1
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
                
            case 'refresh':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'sl_refresh' ) ) {
                    wp_redirect( add_query_arg( array(
                        'page' => 'shoplic-logger',
                        'plugin' => $plugin,
                        'date' => $date
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
        }
    }
    
    /**
     * 메뉴얼 탭 컨텐츠 렌더링
     */
    private function render_manual_tab() {
        ?>
        <div class="wrap">
            <div style="max-width: 800px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                    <h2 style="margin-top: 0;">쇼플릭 로거 사용법</h2>
                    
                    <h3>1. 태그 기반 로깅 시스템</h3>
                    <p>쇼플릭 로거는 태그 기반 제어 시스템을 사용합니다. 모든 로그는 기본적으로 <code>@off</code> 상태의 태그로 작성되며, 필요한 태그를 <code>@on</code>으로 변경하여 로그를 활성화할 수 있습니다.</p>
                    
                    <h3>2. 핵심 API</h3>
                    
                    <h4>백엔드 (PHP)</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
do_action('sl_log', $log_level, $plugin_name, $file_path, $class_name, $function_name, $message, $data, $tags);</pre>
                    
                    <h4>프론트엔드 (JavaScript)</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
window.sl(log_level, plugin_name, file_path, class_name, function_name, message, data, tags);</pre>
                    
                    <h4>매개변수 설명:</h4>
                    <ul style="line-height: 1.8;">
                        <li><code>log_level</code>: 'LOG', 'ERROR', 'INFO', 'WARNING', 'DEBUG'</li>
                        <li><code>plugin_name</code>: 플러그인 또는 테마 식별자 (예: 'woocommerce', 'my-plugin')</li>
                        <li><code>file_path</code>: 절대 파일 경로 (PHP에서는 __FILE__, JS에서는 전체 경로)</li>
                        <li><code>class_name</code>: 클래스 이름 또는 빈 문자열</li>
                        <li><code>function_name</code>: 함수/메서드 이름 또는 빈 문자열</li>
                        <li><code>message</code>: 로그 메시지</li>
                        <li><code>data</code>: 추가 데이터 (배열/객체) 또는 null</li>
                        <li><code>tags</code>: 태그 배열, 형식: <code>['slt#tagname@off']</code></li>
                    </ul>
                    
                    <h3>3. 기본 사용법</h3>
                    
                    <h4>PHP 예제:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 항상 태그는 @off 상태로 포함
do_action('sl_log',
    'LOG',
    'woocommerce',
    __FILE__,
    __CLASS__,
    __METHOD__,
    'Payment started',
    $payment_data,
    ['slt#payment@off', 'slt#checkout@off']
);

do_action('sl_log',
    'ERROR',
    'my-plugin',
    __FILE__,
    __CLASS__,
    __METHOD__,
    'API timeout',
    $error_data,
    ['slt#api@off', 'slt#critical@off']
);</pre>
                    
                    <h4>JavaScript 예제:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// JavaScript에서도 동일한 패턴 사용
window.sl(
    'LOG',
    'woocommerce',
    '/wp-content/plugins/woocommerce/assets/js/checkout.js',
    'CheckoutForm',
    'processPayment',
    'Payment started',
    paymentData,
    ['slt#payment@off', 'slt#checkout@off']
);

window.sl(
    'ERROR',
    'my-plugin',
    '/wp-content/plugins/my-plugin/assets/js/api.js',
    'ApiClient',
    'fetchData',
    'API timeout',
    errorData,
    ['slt#api@off', 'slt#critical@off']
);</pre>
                    
                    <h3>4. 태그 제어 명령어</h3>
                    
                    <h4>특정 태그 활성화:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
# 결제 관련 로그 활성화 (백엔드와 프론트엔드 모두)
find . -name "*.php" -name "*.js" -type f -exec sed -i 's/slt#payment@off/slt#payment@on/g' {} +

# 에러와 critical 로그 활성화
find . -name "*.php" -name "*.js" -type f -exec sed -i -e 's/slt#error@off/slt#error@on/g' -e 's/slt#critical@off/slt#critical@on/g' {} +

# WooCommerce 로그 활성화
find . -name "*.php" -name "*.js" -type f -exec sed -i 's/slt#woocommerce@off/slt#woocommerce@on/g' {} +</pre>
                    
                    <h4>모든 로그 비활성화 (초기화):</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
# PHP 파일 초기화
find . -name "*.php" -type f -exec sed -i 's/@on\]/@off]/g' {} +

# JavaScript 파일 초기화
find . -name "*.js" -type f -exec sed -i 's/@on\]/@off]/g' {} +</pre>
                    
                    <h3>5. 실제 사용 예제</h3>
                    
                    <h4>WooCommerce 주문 처리:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
    
    do_action('sl_log',
        'INFO',
        'woocommerce',
        __FILE__,
        '',
        __FUNCTION__,
        'New order',
        [
            'order_id' => $order_id,
            'total' => $order->get_total(),
            'customer_email' => $order->get_billing_email()
        ],
        ['slt#woocommerce@off', 'slt#order@off', 'slt#sales@off']
    );
});</pre>
                    
                    <h4>API 에러 처리:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
$response = wp_remote_get($api_url);

if (is_wp_error($response)) {
    do_action('sl_log',
        'ERROR',
        'my-plugin',
        __FILE__,
        __CLASS__,
        __METHOD__,
        'API failed',
        [
            'url' => $api_url,
            'error' => $response->get_error_message()
        ],
        ['slt#api@off', 'slt#error@off', 'slt#critical@off']
    );
}</pre>
                    
                    <h3>6. 태그 검색 및 관리</h3>
                    
                    <h4>코드베이스의 모든 태그 찾기:</h4>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
# PHP 파일
grep -r "slt#" --include="*.php" | grep -o "slt#[^'\"]*" | sort | uniq

# JavaScript 파일
grep -r "slt#" --include="*.js" | grep -o "slt#[^'\"]*" | sort | uniq

# 모든 파일
grep -r "slt#" --include="*.php" --include="*.js" | grep -o "slt#[^'\"]*" | sort | uniq</pre>
                    
                    <h3>7. 주요 기능</h3>
                    <ul style="line-height: 1.8;">
                        <li><strong>태그 기반 제어:</strong> @on/@off 상태로 로그 출력 제어</li>
                        <li><strong>플러그인별 분류:</strong> 플러그인/테마별로 로그 자동 분류</li>
                        <li><strong>백엔드/프론트엔드 분리:</strong> PHP 로그는 log-*.log, JS 로그는 fe-log-*.log로 저장</li>
                        <li><strong>실시간 필터링:</strong> 로그 레벨, 클래스, 함수, 태그별 필터링</li>
                        <li><strong>자동 정리:</strong> 7일 이상 된 로그 자동 삭제</li>
                        <li><strong>배치 처리:</strong> 프론트엔드 로그는 2초마다 자동 전송</li>
                    </ul>
                    
                    <h3>8. 로그 파일 구조</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
/wp-content/sl-logs/
├── woocommerce/
│   ├── log-2024-01-15.log        (백엔드 PHP 로그)
│   └── fe-log-2024-01-15.log     (프론트엔드 JS 로그)
├── contact-form-7/
│   ├── log-2024-01-15.log
│   └── fe-log-2024-01-15.log
└── frontend-unknown/
    └── fe-log-2024-01-15.log     (미확인 프론트엔드 로그)</pre>
                    
                    <h3>9. 로그 형식</h3>
                    <p>로그는 다음 형식으로 저장됩니다:</p>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
[2024-01-15 10:30:45] [INFO] [OrderController::processOrder] woocommerce/includes/class-order-controller.php:123 - New order processed [TAGS: woocommerce, order]</pre>
                    <p>형식: <code>[timestamp] [level] [class::function] relative/file/path:line - message [TAGS: ...]</code></p>
                    
                    <h3>10. 태그 형식 규칙</h3>
                    <ul style="line-height: 1.8;">
                        <li><strong>코드에서:</strong> <code>['slt#tagname@off']</code> 또는 <code>['slt#tagname@on']</code></li>
                        <li><strong>로그에서:</strong> <code>[TAGS: tagname]</code> (접두사/상태 없이 깔끔하게 표시)</li>
                        <li><strong>출력 규칙:</strong> 최소 하나의 <code>@on</code> 태그가 있어야 파일에 기록됨</li>
                    </ul>
                    
                    <h3>11. 중요 참고사항</h3>
                    <ul style="line-height: 1.8;">
                        <li>프론트엔드 로깅은 모든 사용자에게 작동 (인증 불필요)</li>
                        <li>프론트엔드 로그는 자동으로 배치되어 2초마다 전송</li>
                        <li>DEBUG 로그는 WP_DEBUG와 독립적으로 작동</li>
                        <li>파일 경로는 로그에서 자동으로 상대 경로로 변환</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}