<?php
/**
 * 쇼플릭 로거 - REST API 핸들러 클래스
 *
 * @package ShoplLogger
 * @subpackage Rest
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SL_Rest_Handler
 * 쇼플릭 로거의 REST API 요청 처리
 */
class SL_Rest_Handler {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * REST API 라우트 등록
     */
    public function register_routes() {
        register_rest_route( 'shoplic-logger/v1', '/log', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_log' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'logs' => array(
                    'required'          => true,
                    'type'              => 'array',
                    'sanitize_callback' => array( $this, 'sanitize_logs' ),
                ),
            ),
        ) );
    }
    
    /**
     * 권한 체크
     */
    public function check_permission( $request ) {
        // Allow anonymous logging - no authentication required
        return true;
    }
    
    /**
     * 로그 데이터 검증 및 정리
     */
    public function sanitize_logs( $logs ) {
        if ( ! is_array( $logs ) ) {
            return array();
        }
        
        $sanitized = array();
        foreach ( $logs as $log ) {
            if ( ! is_array( $log ) ) {
                continue;
            }
            
            $sanitized[] = array(
                'level'         => isset( $log['level'] ) ? sanitize_text_field( $log['level'] ) : 'LOG',
                'plugin_name'   => isset( $log['plugin_name'] ) ? sanitize_text_field( $log['plugin_name'] ) : '',
                'file_path'     => isset( $log['file_path'] ) ? sanitize_text_field( $log['file_path'] ) : '',
                'class_name'    => isset( $log['class_name'] ) ? sanitize_text_field( $log['class_name'] ) : '',
                'function_name' => isset( $log['function_name'] ) ? sanitize_text_field( $log['function_name'] ) : '',
                'message'       => isset( $log['message'] ) ? sanitize_text_field( $log['message'] ) : '',
                'data'          => isset( $log['data'] ) ? $log['data'] : null,
                'tags'          => isset( $log['tags'] ) && is_array( $log['tags'] ) ? array_map( 'sanitize_text_field', $log['tags'] ) : array(),
            );
        }
        
        return $sanitized;
    }
    
    /**
     * 로그 처리
     */
    public function handle_log( $request ) {
        $logs = $request->get_param( 'logs' );
        
        if ( empty( $logs ) || ! is_array( $logs ) ) {
            return new WP_Error( 'invalid_logs', 'Invalid logs data', array( 'status' => 400 ) );
        }
        
        $processed = 0;
        $errors = array();
        
        foreach ( $logs as $log ) {
            try {
                $level         = isset( $log['level'] ) ? $log['level'] : 'LOG';
                $plugin_name   = isset( $log['plugin_name'] ) ? $log['plugin_name'] : '';
                $file_path     = isset( $log['file_path'] ) ? $log['file_path'] : '';
                $class_name    = isset( $log['class_name'] ) ? $log['class_name'] : '';
                $function_name = isset( $log['function_name'] ) ? $log['function_name'] : '';
                $message       = isset( $log['message'] ) ? $log['message'] : '';
                $data          = isset( $log['data'] ) ? $log['data'] : null;
                $tags          = isset( $log['tags'] ) ? $log['tags'] : array();
                
                // Skip empty messages
                if ( empty( $message ) ) {
                    continue;
                }
                
                // Call the unified log method with all parameters
                SL::log( $level, $plugin_name, $file_path, $class_name, $function_name, $message, $data, $tags );
                
                $processed++;
            } catch ( Exception $e ) {
                $errors[] = $e->getMessage();
            }
        }
        
        return array(
            'success'   => true,
            'processed' => $processed,
            'total'     => count( $logs ),
            'errors'    => $errors,
        );
    }
}