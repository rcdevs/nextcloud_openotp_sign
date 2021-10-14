const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config');

webpackConfig.entry = {
    adminSettings: path.join(__dirname, 'src', 'adminSettings.js'),
    main: path.join(__dirname, 'src', 'main.js'),
}

module.exports = webpackConfig
