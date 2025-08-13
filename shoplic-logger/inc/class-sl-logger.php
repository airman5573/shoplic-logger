<?php
/**
 * 쇼플릭 로거 - 로거 클래스
 *
 * @package ShoplLogger
 * @subpackage Logger
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL
 * 쇼플릭 로거의 메인 로거 클래스
 */
class SL {
    
    /**
     * 로그 레벨
     */
    const LOG = 'LOG';
    const ERROR = 'ERROR';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const WARNING = 'WARNING';
    
    /**
     * 통합 로그 기록
     */
    public static function log( $log_level, $plugin_name, $file_path, $class_name, $function_name, $message, $data = null, $tags = [] ) {
        // Validate log level
        $valid_levels = [ self::LOG, self::ERROR, self::INFO, self::DEBUG, self::WARNING ];
        if ( ! in_array( $log_level, $valid_levels ) ) {
            $log_level = self::LOG;
        }
        
        
        // Determine source from caller
        $source = 'backend';
        if ( strpos( $file_path, '.js' ) !== false ) {
            $source = 'frontend';
        }
        
        self::write( $log_level, $plugin_name, $file_path, $class_name, $function_name, $message, $data, $tags, $source );
    }
    
    /**
     * 로그 파일에 쓰기
     * @param string $source 'frontend' or 'backend' to indicate log source
     */
    private static function write( $level, $plugin_name, $file_path, $class_name, $function_name, $message, $data = null, $tags = [], $source = 'backend' ) {
        // Check if any tag has @on suffix
        $should_output = false;
        if ( ! empty( $tags ) ) {
            foreach ( $tags as $tag ) {
                if ( strpos( $tag, '@on' ) !== false ) {
                    $should_output = true;
                    break;
                }
            }
            // If tags exist but none have @on, don't output
            if ( ! $should_output ) {
                return;
            }
        }
        // If no tags provided, output normally (for backward compatibility)
        
        // Transform file path to relative path
        $relative_file_path = self::get_relative_path( $file_path );
        
        // 필요한 경우 로그 디렉토리 생성
        $log_dir = SL_LOG_DIR . '/' . $plugin_name;
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            
            // 보안을 위해 .htaccess 추가
            $htaccess = SL_LOG_DIR . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, 'deny from all' );
            }
        }
        
        // 로그 항목 생성
        $timestamp = date( 'Y-m-d H:i:s' );
        
        // Extract line number from file path if it contains colon
        $file_info = $relative_file_path;
        if ( strpos( $relative_file_path, ':' ) === false ) {
            // If no line number provided, just use the file path
            $file_info = $relative_file_path;
        }
        
        // Process tags - remove prefix and on/off state for log storage
        $clean_tags = array();
        if ( ! empty( $tags ) ) {
            foreach ( $tags as $tag ) {
                // Remove slt# prefix and @on/@off suffix
                $clean_tag = preg_replace( '/^slt#/', '', $tag );
                $clean_tag = preg_replace( '/@(on|off)$/', '', $clean_tag );
                if ( ! empty( $clean_tag ) ) {
                    $clean_tags[] = $clean_tag;
                }
            }
        }
        
        // Build class::function notation
        $class_function = '';
        if ( ! empty( $class_name ) && ! empty( $function_name ) ) {
            $class_function = "[{$class_name}::{$function_name}] ";
        } elseif ( ! empty( $function_name ) ) {
            $class_function = "[{$function_name}] ";
        }
        
        $log_entry = sprintf(
            "[%s] [%s] %s%s - %s",
            $timestamp,
            $level,
            $class_function,
            $file_info,
            $message
        );
        
        // Add tags if present
        if ( ! empty( $clean_tags ) ) {
            $log_entry .= ' [TAGS: ' . implode( ', ', $clean_tags ) . ']';
        }
        
        // 데이터가 제공된 경우 추가
        if ( $data !== null ) {
            $log_entry .= "\n    Data: " . print_r( $data, true );
        }
        
        $log_entry .= "\n";
        
        // 파일에 쓰기 - frontend logs use fe-log- prefix
        $log_prefix = ( $source === 'frontend' ) ? 'fe-log-' : 'log-';
        $log_file = $log_dir . '/' . $log_prefix . date( 'Y-m-d' ) . '.log';
        error_log( $log_entry, 3, $log_file );
    }
    
    /**
     * Get relative path from absolute path
     * @param string $file_path Absolute file path
     * @return string Relative path
     */
    private static function get_relative_path( $file_path ) {
        $file_path = str_replace( '\\', '/', $file_path );
        
        // Remove WordPress directory paths
        $paths_to_remove = [
            str_replace( '\\', '/', WP_PLUGIN_DIR ) . '/',
            str_replace( '\\', '/', WPMU_PLUGIN_DIR ) . '/',
            str_replace( '\\', '/', get_theme_root() ) . '/',
            str_replace( '\\', '/', WP_CONTENT_DIR ) . '/',
            str_replace( '\\', '/', ABSPATH )
        ];
        
        foreach ( $paths_to_remove as $path ) {
            if ( strpos( $file_path, $path ) === 0 ) {
                return substr( $file_path, strlen( $path ) );
            }
        }
        
        // If no match, return basename
        return basename( $file_path );
    }
    
    /**
     * 로그 디렉토리 가져오기
     */
    public static function get_log_dir() {
        return SL_LOG_DIR;
    }
    
    /**
     * <span title="개발 중 문제를 해결하기 위해 사용하는 모드">디버그 모드</span>가 활성화되었는지 확인
     */
    public static function is_debug_mode() {
        return true; // shoplic-logger는 WP_DEBUG와 독립적으로 작동
    }
    
    /**
     * 오래된 로그 정리 (7일 이상 된 로그)
     */
    public static function cleanup_old_logs() {
        if ( ! is_dir( SL_LOG_DIR ) ) {
            return;
        }
        
        $dirs = glob( SL_LOG_DIR . '/*', GLOB_ONLYDIR );
        $now = time();
        
        foreach ( $dirs as $dir ) {
            $files = glob( $dir . '/log-*.log' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    if ( $now - filemtime( $file ) >= 7 * 24 * 60 * 60 ) {
                        unlink( $file );
                    }
                }
            }
        }
    }
}