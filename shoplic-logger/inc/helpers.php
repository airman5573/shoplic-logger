<?php
/**
 * 쇼플릭 로거 - 헬퍼 함수
 *
 * @package ShoplLogger
 * @subpackage Helpers
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ====================================================================
 * 더 쉬운 사용을 위한 헬퍼 함수
 * ====================================================================
 * 이 함수들은 매번 \SL::을 입력하지 않고도 SL 로거를
 * 더 간편하게 사용할 수 있는 방법을 제공합니다.
 */

// 통합 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'sl_log' ) ) {
    function sl_log( $log_level, $plugin_name, $file_path, $class_name, $function_name, $message, $data = null ) {
        \SL::log( $log_level, $plugin_name, $file_path, $class_name, $function_name, $message, $data );
    }
}
