module.exports = {
    content: ['./PluboLogs/**/*.{php,vue,js}'],
    theme: {
        extend: {
            colors: {
                error: '#E3554C',
                warning: '#EDB359',
                info: '#9BCCE4',
                code: '#F6F7F8'
            },
        },
    },
    plugins: [],
    prefix: 'pb-',
    corePlugins: {
        preflight: false,
    }
};
