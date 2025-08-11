<?php
/**
 * 쇼플릭 로거 - 디버그 설정 클래스
 *
 * @package ShoplLogger
 * @subpackage Debug
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_Debug_Settings
 * 워드프레스 디버그 상수 설정 처리
 */
class SL_Debug_Settings {
    
    /**
     * 디버그 설정 페이지 렌더링
     */
    public function render_page() {
        // POST 요청 처리
        if ( isset( $_POST['sl_save_debug_settings'] ) && wp_verify_nonce( $_POST['sl_debug_nonce'], 'sl_debug_settings' ) ) {
            $this->process_form_submission();
        }
        
        // 현재 상수 값 가져오기
        $current_settings = $this->get_current_settings();
        ?>
        <div class="sl-debug-settings-wrap" style="margin-top: 20px;">
            
            <div class="notice notice-info">
                <p>
                    <strong>주의:</strong>
                    이 설정들은 <span title="워드프레스의 주요 설정 파일">wp-config.php</span> 파일을 직접 수정합니다. 변경 전 백업을 권장합니다.
                </p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=sl_download_wp_config&nonce=' . wp_create_nonce( 'sl_ajax_nonce' ) ) ); ?>" 
                   class="button button-secondary">
                    wp-config.php 백업 다운로드
                </a>
            </div>
            
            <form id="sl-debug-settings-form" method="post" action="">
                <?php wp_nonce_field( 'sl_debug_settings', 'sl_debug_nonce' ); ?>
                <table class="form-table">
                    <tbody>
                        <!-- WP_DEBUG -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug">
                                    <span title="워드프레스 디버그 모드를 활성화하는 상수">WP_DEBUG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug" 
                                           name="wp_debug" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG'] ); ?>>
                                    워드프레스 <span title="개발 중 문제를 해결하기 위해 사용하는 모드">디버그 모드</span> 활성화
                                </label>
                                <p class="description">
                                    <span title="프로그래밍 언어">PHP</span> 오류, 알림, 경고를 표시하거나 기록합니다.<br>
                                    • 활성화 시: 모든 PHP 오류와 경고가 표시/기록됩니다<br>
                                    • 권장: 개발 환경에서만 활성화<br>
                                    • 주의: 운영 사이트에서는 반드시 비활성화하세요 (보안 위험)
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DEBUG_LOG -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug_log">
                                    <span title="디버그 메시지를 파일에 기록하는 상수">WP_DEBUG_LOG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug_log" 
                                           name="wp_debug_log" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG_LOG'] ); ?>>
                                    디버그 로그 파일 생성
                                </label>
                                <p class="description">
                                    오류를 /wp-content/debug.log 파일에 저장합니다.<br>
                                    • 위치: /wp-content/debug.log<br>
                                    • 조건: WP_DEBUG가 true일 때만 작동<br>
                                    • 용도: 화면에 표시하지 않고 오류를 기록할 때 유용<br>
                                    • 팁: 로그 파일이 커질 수 있으므로 정기적으로 확인 및 정리 필요
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DEBUG_DISPLAY -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug_display">
                                    <span title="오류를 화면에 표시할지 결정하는 상수">WP_DEBUG_DISPLAY</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug_display" 
                                           name="wp_debug_display" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG_DISPLAY'] ); ?>>
                                    화면에 오류 표시
                                </label>
                                <p class="description">
                                    오류를 웹페이지에 직접 표시합니다.<br>
                                    • true: 오류가 화면에 바로 출력됨<br>
                                    • false: 오류가 화면에 표시되지 않음 (로그에만 기록)<br>
                                    • 권장: 운영 사이트에서는 반드시 false로 설정<br>
                                    • 보안: 오류 메시지에 민감한 정보가 노출될 수 있음
                                </p>
                            </td>
                        </tr>
                        
                        <!-- SCRIPT_DEBUG -->
                        <tr>
                            <th scope="row">
                                <label for="script_debug">
                                    <span title="스크립트 디버그 모드를 활성화하는 상수">SCRIPT_DEBUG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="script_debug" 
                                           name="script_debug" 
                                           value="1" 
                                           <?php checked( $current_settings['SCRIPT_DEBUG'] ); ?>>
                                    스크립트 디버그 모드
                                </label>
                                <p class="description">
                                    워드프레스가 압축되지 않은 <span title="자바스크립트/캐스케이딩 스타일 시트">JS/CSS</span> 파일을 사용합니다.<br>
                                    • 활성화 시: .min.js/.min.css 대신 원본 파일 사용<br>
                                    • 장점: 디버깅이 쉬워짐, 코드 추적 가능<br>
                                    • 단점: 페이지 로딩 속도가 느려짐<br>
                                    • 권장: 개발 환경에서만 사용
                                </p>
                            </td>
                        </tr>
                        
                        <!-- SAVEQUERIES -->
                        <tr>
                            <th scope="row">
                                <label for="savequeries">
                                    <span title="데이터베이스 쿼리를 저장하는 상수">SAVEQUERIES</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="savequeries" 
                                           name="savequeries" 
                                           value="1" 
                                           <?php checked( $current_settings['SAVEQUERIES'] ); ?>>
                                    <span title="데이터베이스에 요청하는 SQL 명령어">데이터베이스 쿼리</span> 저장
                                </label>
                                <p class="description">
                                    모든 <span title="데이터베이스에 요청하는 SQL 명령어">데이터베이스 쿼리</span>를 메모리에 저장합니다. 성능 분석에 유용하지만 메모리 사용량이 증가합니다.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_MEMORY_LIMIT -->
                        <tr>
                            <th scope="row">
                                <label for="wp_memory_limit">
                                    <span title="워드프레스가 사용할 수 있는 PHP 메모리 제한">WP_MEMORY_LIMIT</span>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="wp_memory_limit" 
                                       name="wp_memory_limit" 
                                       value="<?php echo esc_attr( $current_settings['WP_MEMORY_LIMIT'] ); ?>"
                                       min="32"
                                       style="width: 80px;">
                                <span>MB</span>
                                <p class="description">
                                    워드프레스가 일반적으로 사용할 수 있는 <span title="프로그래밍 언어">PHP</span> 메모리 제한입니다.<br>
                                    • 기본값: 40MB (멀티사이트는 64MB)<br>
                                    • 최소값: 32MB (워드프레스 최소 요구사항)<br>
                                    • 권장값: 128MB 이상 (대용량 플러그인/테마 사용 시)<br>
                                    • 주의: 서버의 PHP memory_limit 설정보다 클 수 없습니다.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_MAX_MEMORY_LIMIT -->
                        <tr>
                            <th scope="row">
                                <label for="wp_max_memory_limit">
                                    <span title="관리자 영역에서 사용할 수 있는 최대 PHP 메모리 제한">WP_MAX_MEMORY_LIMIT</span>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="wp_max_memory_limit" 
                                       name="wp_max_memory_limit" 
                                       value="<?php echo esc_attr( $current_settings['WP_MAX_MEMORY_LIMIT'] ); ?>"
                                       min="32"
                                       style="width: 80px;">
                                <span>MB</span>
                                <p class="description">
                                    관리자 페이지에서 사용할 수 있는 최대 <span title="프로그래밍 언어">PHP</span> 메모리 제한입니다.<br>
                                    • 기본값: 256MB<br>
                                    • 권장값: WP_MEMORY_LIMIT 이상의 값<br>
                                    • 용도: 미디어 업로드, 플러그인 업데이트, 대량 데이터 처리 등<br>
                                    • 팁: 이미지 처리나 백업 작업 시 메모리 부족 오류가 발생하면 이 값을 증가시키세요.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DISABLE_FATAL_ERROR_HANDLER -->
                        <?php if ( version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="wp_disable_fatal_error_handler">
                                    <span title="치명적 오류 핸들러를 비활성화하는 상수">WP_DISABLE_FATAL_ERROR_HANDLER</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_disable_fatal_error_handler" 
                                           name="wp_disable_fatal_error_handler" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DISABLE_FATAL_ERROR_HANDLER'] ); ?>>
                                    <span title="사이트를 다운시킬 수 있는 심각한 오류">치명적 오류</span> 핸들러 비활성화
                                </label>
                                <p class="description">
                                    워드프레스의 <span title="치명적 오류 발생 시 자동으로 대체 코드를 실행하는 기능">자동 복구 모드</span>를 비활성화합니다.<br>
                                    • 기본 동작: 치명적 오류 시 복구 모드로 전환<br>
                                    • 활성화 시: 복구 모드 없이 실제 오류 표시<br>
                                    • 용도: 개발 중 정확한 오류 위치 파악<br>
                                    • 주의: 운영 사이트에서는 사용하지 마세요<br>
                                    • 요구사항: 워드프레스 5.2 이상
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" name="sl_save_debug_settings" id="sl-save-debug-settings">
                        설정 저장
                    </button>
                </p>
            </form>
        </div>
        
        <style>
            .sl-debug-settings-wrap .form-table th {
                width: 250px;
            }
            .sl-debug-settings-wrap .spinner {
                float: none;
                margin-left: 10px;
            }
            .sl-debug-settings-wrap .notice {
                margin-left: 0;
            }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sl-debug-settings-form').on('submit', function(e) {
                // 메모리 제한 유효성 검사
                var wpMemoryLimit = parseInt($('#wp_memory_limit').val());
                var wpMaxMemoryLimit = parseInt($('#wp_max_memory_limit').val());
                
                if (wpMemoryLimit < 32) {
                    alert('WP_MEMORY_LIMIT는 최소 32MB 이상이어야 합니다.');
                    e.preventDefault();
                    return false;
                }
                
                if (wpMaxMemoryLimit < wpMemoryLimit) {
                    alert('WP_MAX_MEMORY_LIMIT는 WP_MEMORY_LIMIT(' + wpMemoryLimit + 'MB) 이상이어야 합니다.');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
        </script>
        <?php
    }
    
    /**
     * 폼 제출 처리
     */
    private function process_form_submission() {
        // wp-config.php 경로 찾기
        $config_path = $this->find_wp_config();
        
        if ( ! $config_path ) {
            echo '<div class="notice notice-error"><p>wp-config.php 파일을 찾을 수 없습니다.</p></div>';
            return;
        }
        
        if ( ! is_writable( $config_path ) ) {
            echo '<div class="notice notice-error"><p>wp-config.php 파일에 쓰기 권한이 없습니다.</p></div>';
            return;
        }
        
        // vendor 파일 포함
        require_once SL_PLUGIN_DIR . '/vendor/WPConfigTransformer.php';
        
        $config_transformer = new \DebugLogConfigTool\vendor\WPConfigTransformer( $config_path );
        
        // POST에서 디버그 설정 가져오기
        $settings = array(
            'WP_DEBUG' => isset( $_POST['wp_debug'] ) && $_POST['wp_debug'] === '1',
            'WP_DEBUG_LOG' => isset( $_POST['wp_debug_log'] ) && $_POST['wp_debug_log'] === '1',
            'WP_DEBUG_DISPLAY' => isset( $_POST['wp_debug_display'] ) && $_POST['wp_debug_display'] === '1',
            'SCRIPT_DEBUG' => isset( $_POST['script_debug'] ) && $_POST['script_debug'] === '1',
            'SAVEQUERIES' => isset( $_POST['savequeries'] ) && $_POST['savequeries'] === '1',
            'WP_DISABLE_FATAL_ERROR_HANDLER' => isset( $_POST['wp_disable_fatal_error_handler'] ) && $_POST['wp_disable_fatal_error_handler'] === '1'
        );
        
        // 메모리 제한 설정
        $memory_settings = array(
            'WP_MEMORY_LIMIT' => isset( $_POST['wp_memory_limit'] ) ? intval( $_POST['wp_memory_limit'] ) . 'M' : '40M',
            'WP_MAX_MEMORY_LIMIT' => isset( $_POST['wp_max_memory_limit'] ) ? intval( $_POST['wp_max_memory_limit'] ) . 'M' : '256M'
        );
        
        try {
            // Boolean 상수 업데이트
            foreach ( $settings as $constant => $value ) {
                if ( $config_transformer->exists( 'constant', $constant ) ) {
                    $config_transformer->update( 'constant', $constant, $value ? 'true' : 'false', array( 'raw' => true ) );
                } else {
                    $config_transformer->add( 'constant', $constant, $value ? 'true' : 'false', array( 'raw' => true ) );
                }
            }
            
            // 메모리 제한 상수 업데이트
            foreach ( $memory_settings as $constant => $value ) {
                if ( $config_transformer->exists( 'constant', $constant ) ) {
                    $config_transformer->update( 'constant', $constant, $value );
                } else {
                    $config_transformer->add( 'constant', $constant, $value );
                }
            }
            
            echo '<div class="notice notice-success"><p>디버그 설정이 성공적으로 저장되었습니다.</p></div>';
            
        } catch ( Exception $e ) {
            echo '<div class="notice notice-error"><p>설정 저장 중 오류가 발생했습니다: ' . esc_html( $e->getMessage() ) . '</p></div>';
        }
    }
    
    /**
     * wp-config.php 파일 경로 찾기
     */
    private function find_wp_config() {
        $config_path = ABSPATH . 'wp-config.php';
        
        if ( file_exists( $config_path ) ) {
            return $config_path;
        }
        
        // 한 단계 상위 디렉토리 확인
        $config_path = dirname( ABSPATH ) . '/wp-config.php';
        
        if ( file_exists( $config_path ) ) {
            return $config_path;
        }
        
        return false;
    }
    
    /**
     * 현재 디버그 상수 설정 가져오기
     */
    private function get_current_settings() {
        $settings = array(
            'WP_DEBUG' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'WP_DEBUG_LOG' => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
            'WP_DEBUG_DISPLAY' => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
            'SCRIPT_DEBUG' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
            'SAVEQUERIES' => defined( 'SAVEQUERIES' ) && SAVEQUERIES,
            'WP_DISABLE_FATAL_ERROR_HANDLER' => defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) && WP_DISABLE_FATAL_ERROR_HANDLER
        );
        
        // 메모리 제한 설정 가져오기 (MB 단위로 변환)
        $settings['WP_MEMORY_LIMIT'] = $this->convert_memory_limit_to_mb( 
            defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '40M' 
        );
        $settings['WP_MAX_MEMORY_LIMIT'] = $this->convert_memory_limit_to_mb( 
            defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '256M' 
        );
        
        return $settings;
    }
    
    /**
     * 메모리 제한 값을 MB 단위로 변환
     */
    private function convert_memory_limit_to_mb( $value ) {
        $value = trim( $value );
        $last = strtolower( $value[ strlen( $value ) - 1 ] );
        $value = (int) $value;
        
        switch ( $last ) {
            case 'g':
                $value *= 1024;
                break;
            case 'k':
                $value /= 1024;
                break;
        }
        
        return $value;
    }
}