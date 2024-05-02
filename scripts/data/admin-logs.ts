import apiFetch from '@wordpress/api-fetch'
import { addQueryArgs } from '@wordpress/url'

interface Backtrace {
    file: string,
    line: number,
    function?: string,
    class?: string,
    type?: string
}

interface Log {
    id: number,
    date: string,
    service: string,
    content: string,
    status: string,
    backtrace: Backtrace[]
}

interface LogRequestArgs {
    page: number,
    service?: string|string[]
    status?: string[],
    date?: string
}

interface LogRequestResponse {
    id: number,
    date: string,
    service: string,
    content: string,
    status: string,
    backtrace: string
}

interface AdminLogsData {
    services: string[],
    logs: Log[],
    loading: boolean,

    service_filter: string[],
    status_filter: string[],
    date_filter: string,
    date: string,

    page: number,

    init: () => void,

    toggle_service: (value: string) => void,
    toggle_status: (value: string) => void,

    get_logs: (reset_results: boolean) => void,
    access_log: (log_id: number) => void
}

const getLogsApiConfig = {
    // @ts-ignore
    path: PLUBO_LOGS_PARAMS.restUrl + '/get_logs',
    method: 'GET',
    headers: {
        // @ts-ignore
        'X-WP-Nonce': PLUBO_LOGS_PARAMS.restNonce
    }
}

export const adminLogsData = () => {
    // @ts-ignore
    Alpine.data('adminLogsData', (): AdminLogsData => ({
        services: [],
        logs: [],
        loading: false,
        
        service_filter: [],
        status_filter: ['error','warning','log'],
        date_filter: '',
        date: '',
        
        page: 0,

        init: function()
        {
            this.get_logs(false);
        },

        toggle_service: function(value: string)
        {
            if (this.service_filter.includes(value)) {
                this.service_filter.splice(this.service_filter.indexOf(value), 1)
                return;
            }
            this.service_filter.push(value);
        },
        toggle_status: function(value: string)
        {
            if (this.status_filter.includes(value)) {
                this.status_filter.splice(this.status_filter.indexOf(value), 1)
                return;
            }
            this.status_filter.push(value);
        },

        get_logs: async function(reset_results: boolean)
        {          
            this.loading = true;

            if (reset_results) {
                this.logs = [];
            }

            const args: LogRequestArgs = {
                page: this.page,
                service: (this.service_filter.length === this.services.length) ? 'all' : this.service_filter,
                status: this.status_filter
            };

            if (this.date_filter) args.date = (this.date_filter === 'custom') ? this.date : this.date_filter;

            const logs_response: LogRequestResponse[] = await apiFetch({
                ...getLogsApiConfig,
                path: addQueryArgs( getLogsApiConfig.path, args )
            });

            this.logs = logs_response.map((log) => ({...log, backtrace: JSON.parse(log.backtrace)}));
            this.logs.forEach((log) => {
                if (!this.services.includes(log.service)) this.services.push(log.service)
            });
            if (args.service === 'all') this.service_filter = [...this.services];

            this.loading = false;
        },

        access_log: function(log_id: number)
        {
            // @ts-ignore
            window.location.href = PLUBO_LOGS_PARAMS.logsUrl + '&log_id=' + log_id
        }
    }))
}