<?php

namespace PluboLogs;

class AdminMenu
{
    private static $PLUBO_TOOLS_PAGE    = 'tools_page_plubo-logs';
    private static $PLUBO_HANDLE_NAME   = 'plubo-logs';
    private static $PLUBO_ADMIN_VERSION = '0.0.1';

    /**
     * Adds the logs submenu on the admin panel
     */
    public static function add_logs_submenu()
    {
        add_submenu_page(
            'tools.php',
            'Plugin Logs',
            'Plugin Logs',
            'manage_options',
            'plubo-logs',
            [ self::class, 'render_logs_menu' ]
        );
    }

    /**
     * Enqueues the logs scripts
     */
    public static function enqueue_log_scripts( $hook )
    {
        if ( $hook === self::$PLUBO_TOOLS_PAGE && !wp_script_is( self::$PLUBO_HANDLE_NAME ) ) {
            $assets_url = plugin_dir_url( __FILE__ ) . '../dist/';
            if ( defined( 'PLUBO_LOGS_ASSETS_PATH' ) ) {
                $assets_url = PLUBO_LOGS_ASSETS_PATH;
            }

            wp_enqueue_script( self::$PLUBO_HANDLE_NAME, $assets_url . 'app.js', [], self::$PLUBO_ADMIN_VERSION );
            wp_enqueue_style( self::$PLUBO_HANDLE_NAME, $assets_url . 'app.css', [], self::$PLUBO_ADMIN_VERSION );

            $rest_url   = get_rest_url( null, Endpoints::$NAMESPACE );
            $rest_nonce = wp_create_nonce( 'wp_rest' );
            $logs_url   = admin_url( 'tools.php?page=plubo-logs' );
            wp_add_inline_script( self::$PLUBO_HANDLE_NAME, "const PLUBO_LOGS_PARAMS = {restUrl: '$rest_url', restNonce: '$rest_nonce', logsUrl: '$logs_url'}", 'before' );
        }
    }

    /**
     * Renders the corresponding logs page in the admin panel
     */
    public static function render_logs_menu()
    {
        $log_id = sanitize_text_field( $_GET['log_id'] ?? '' );

        if ( !$log_id ) self::render_logs_page();
        
        else self::render_single_log( $log_id );
    }

    /**
     * Renders the logs page
     */
    public static function render_logs_page()
    {
        ?>
            <div x-data="adminLogsData" class="pb-p-8 pb-flex pb-flex-col lg:pb-flex-row pb-gap-8" id="poststuff">
                <div class="pb-w-full pb-max-w-[280px]">
                    <button type="button" class="button button-primary pb-w-full !pb-mb-8" @click="get_logs(true)"><?php esc_html_e( 'Update', 'plubo-logs' ); ?></button>

                    <!-- Service Filters -->
                    <div class="postbox-container">
                        <div class="stuffbox">
                            <h2><?php esc_html_e( 'Service', 'plubo-logs' ); ?></h2>
                            <div class="inside">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <template x-for="service in services">
                                            <p>
                                                <input type="checkbox" x-bind:checked="service_filter.includes(service)" @change="toggle_service(service)"/>
                                                <span x-text="service"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filters -->
                    <div class="postbox-container">
                        <div class="stuffbox">
                            <h2><?php esc_html_e( 'Status', 'plubo-logs' ); ?></h2>
                            <div class="inside">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <p>
                                            <input type="checkbox" x-bind:checked="status_filter.includes('error')" @change="toggle_status('error')"/>
                                            <span class="pb-h-5 pb-pl-1 pb-mx-2 pb-bg-error pb-rounded-full"></span>
                                            <span><?php esc_html_e( 'Error', 'plubo-logs' ); ?></span>
                                        </p>
                                        <p>
                                            <input type="checkbox" x-bind:checked="status_filter.includes('warning')" @change="toggle_status('warning')"/>
                                            <span class="pb-h-5 pb-pl-1 pb-mx-2 pb-bg-warning pb-rounded-full"></span>
                                            <span><?php esc_html_e( 'Warning', 'plubo-logs' ); ?></span>
                                        </p>
                                        <p>
                                            <input type="checkbox" x-bind:checked="status_filter.includes('log')" @change="toggle_status('log')"/>
                                            <span class="pb-h-5 pb-pl-1 pb-mx-2 pb-bg-info pb-rounded-full"></span>
                                            <span><?php esc_html_e( 'Info', 'plubo-logs' ); ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date Filters -->
                    <div class="postbox-container">
                        <div class="stuffbox">
                            <h2><?php esc_html_e( 'Date', 'plubo-logs' ); ?></h2>
                            <div class="inside">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <select x-model="date_filter">
                                            <option value=""><?php esc_html_e( 'All', 'plubo-logs' ); ?></option>
                                            <option value="last_hour"><?php esc_html_e( 'Last Hour', 'plubo-logs' ); ?></option>
                                            <option value="today"><?php esc_html_e( 'Today', 'plubo-logs' ); ?></option>
                                            <option value="this_week"><?php esc_html_e( 'This Week', 'plubo-logs' ); ?></option>
                                            <option value="custom"><?php esc_html_e( 'Custom', 'plubo-logs' ); ?></option>
                                        </select>

                                        <input x-show="date_filter === 'custom'" x-model="date" type="date" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <template x-if="!loading">
                    <div class="postbox-container">
                        <div class="stuffbox">
                            <h2><?php esc_html_e( 'Logs', 'plubo-logs' ); ?></h2>
                            <div class="inside">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <?php self::render_logs_table(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="postbox-container">
                        <div class="stuffbox">
                            <h2><?php esc_html_e( 'Logs', 'plubo-logs' ); ?></h2>
                            <div class="inside">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader pb-animate-spin pb-h-4 pb-w-4"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                                        <?php esc_html_e( 'Fetching logs...', 'plubo-logs' ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        <?php
    }

    /**
     * Renders the logs table
     */
    public static function render_logs_table()
    {
        ?>
            <table class="wp-list-table widefat fixed striped table-view-list plugins">
                <thead>
                    <tr class="pb-uppercase pb-text-left pb-outline pb-outline-slate-200 pb-outline-1">
                        <th><?php esc_html_e( 'Date', 'plubo-logs' ); ?></th>
                        <th><?php esc_html_e( 'Service', 'plubo-logs' ); ?></th>
                        <th><?php esc_html_e( 'Content', 'plubo-logs' ); ?></th>
                        <th><?php esc_html_e( 'File', 'plubo-logs' ); ?></th>
                        <th><?php esc_html_e( 'Line', 'plubo-logs' ); ?></th>
                        <th><?php esc_html_e( 'Function', 'plubo-logs' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <template x-for="log in logs">
                        <tr class="pb-cursor-pointer" :class="{
                            'active': log.status === 'log',
                            'warning': log.status === 'warning',
                            'error': log.status === 'error'
                        }" @click="access_log(log.id)">
                            <th scope="row" class="check-column"><p x-text="log.date"></p></th>
                            <td x-text="log.service"></td>
                            <td><p class="pb-line-clamp-3" x-text="log.content"></p></td>
                            <td><p class="pb-line-clamp-3" x-text="log.backtrace[0].file"></p></td>
                            <td x-text="log.backtrace[0].line"></td>
                            <td x-text="log.backtrace[1].function"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        <?php
    }

    /**
     * Renders the page for a single log
     */
    public static function render_single_log( $log_id )
    {
        $log            = new Log( [ 'id' => $log_id ] );
        $log->sync();
        $log_backtrace  = json_decode( $log->backtrace, ARRAY_A );

        ?>
            <div class="pb-p-8">
                <a class="pb-text-lg" href="<?php echo admin_url( 'tools.php?page=plubo-logs' ); ?>"><?php esc_html_e( 'Return to logs', 'plubo-logs' ) ?></a>

                <div class="pb-bg-white pb-p-8 pb-rounded-xl pb-w-auto pb-shadow pb-mt-4">
                    <p class="pb-text-lg pb-m-0 pb-mb-8">
                        <span class="pb-uppercase pb-font-bold pb-p-1 pb-rounded-md <?php
                            echo ( $log->status === Log::$STATUS_ERROR ) ? 'pb-bg-error-clear' : ( $log->status === Log::$STATUS_WARNING ? 'pb-bg-warning-clear' : 'pb-bg-info-clear' );
                        ?>"><?php
                            echo ( $log->status === Log::$STATUS_ERROR ) ? 'error' : ( $log->status === Log::$STATUS_WARNING ? 'warning' : 'info' );
                        ?></span>
                        <span class="pb-ml-2 pb-font-bold"><?php echo date( 'M d, Y \a\t h:i:s a', strtotime( $log->date ) ); ?></span>
                    </p>

                    <span class="pb-font-bold pb-text-xl"><?php esc_html_e( 'Log Details', 'plubo-logs' ); ?></span>
                    <hr class="pb-border-b pb-border-b-solid pb-border-b-slate-200 pb-m-0 pb-mb-4">
                    <table class="pb-min-w-[768px] pb-mb-8">
                        <tr class="pb-outline pb-outline-2 pb-outline-slate-200 [&>*]:pb-p-2">
                            <td class="pb-border-0 pb-border-r-2 pb-border-solid pb-border-r-slate-200"><span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'File', 'plubo-logs' ); ?></span></td>
                            <td><?php echo $log_backtrace[0]['file'] ?? 'None'; ?></td>
                        </tr>
                        <tr class="pb-outline pb-outline-2 pb-outline-slate-200 [&>*]:pb-p-2">
                            <td class="pb-border-0 pb-border-r-2 pb-border-solid pb-border-r-slate-200"><span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Line', 'plubo-logs' ); ?></span></td>
                            <td><?php echo $log_backtrace[0]['line'] ?? 'None'; ?></td>
                        </tr>
                        <tr class="pb-outline pb-outline-2 pb-outline-slate-200 [&>*]:pb-p-2">
                            <td class="pb-border-0 pb-border-r-2 pb-border-solid pb-border-r-slate-200"><span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Function', 'plubo-logs' ); ?></span></td>
                            <td><?php echo $log_backtrace[1]['function'] ?? 'None'; ?></td>
                        </tr>
                        <tr class="pb-outline pb-outline-2 pb-outline-slate-200 [&>*]:pb-p-2">
                            <td class="pb-border-0 pb-border-r-2 pb-border-solid pb-border-r-slate-200"><span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Class', 'plubo-logs' ); ?></span></td>
                            <td><?php echo $log_backtrace[1]['class'] ?? 'None'; ?></td>
                        </tr>
                        <tr class="pb-outline pb-outline-2 pb-outline-slate-200 [&>*]:pb-p-2">
                            <td class="pb-border-0 pb-border-r-2 pb-border-solid pb-border-r-slate-200"><span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Type', 'plubo-logs' ); ?></span></td>
                            <td><?php echo $log_backtrace[1]['type'] ?? 'None'; ?></td>
                        </tr>
                    </table>

                    <span class="pb-font-bold pb-text-xl"><?php esc_html_e( 'Log Content', 'plubo-logs' ); ?></span>
                    <hr class="pb-border-b pb-border-b-solid pb-border-b-slate-200 pb-m-0 pb-mb-4">
                    <div class="pb-bg-code pb-p-4 pb-rounded-lg pb-flex pb-mb-8">
                        <div class="pb-font-mono pb-w-full">
                            <?php echo $log->content; ?>
                        </div>
                        <button type="button" class="pb-bg-transparent pb-outline-none pb-border-0 pb-cursor-pointer hover:pb-bg-slate-200 pb-rounded-full pb-h-fit pb-py-1" onclick="navigator.clipboard.writeText('<?php echo $log->content; ?>')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy pb-h-5 pb-w-5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                    </div>

                    <span class="pb-font-bold pb-text-xl"><?php esc_html_e( 'Full Log Backtrace', 'plubo-logs' ); ?></span>
                    <hr class="pb-border-b pb-border-b-solid pb-border-b-slate-200 pb-m-0 pb-mb-4">
                    <div class="pb-w-full pb-grid pb-grid-cols-2 pb-gap-4">
                        <div class="pb-border-2 pb-border-solid pb-border-slate-200 pb-rounded-xl pb-p-4">
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'File', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[0]['file'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Line', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[0]['line'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Function', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[0]['function'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Class', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[0]['class'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Type', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[0]['type'] ?? 'None'; ?>
                            </p>
                        </div>
                        <div class="pb-border-2 pb-border-solid pb-border-slate-200 pb-rounded-xl pb-p-4">
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'File', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[1]['file'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Line', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[1]['line'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Function', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[1]['function'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Class', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[1]['class'] ?? 'None'; ?>
                            </p>
                            <p>
                                <span class="pb-uppercase pb-font-bold"><?php esc_html_e( 'Type', 'plubo-logs' ); ?>:</span>
                                <?php echo $log_backtrace[1]['type'] ?? 'None'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
}