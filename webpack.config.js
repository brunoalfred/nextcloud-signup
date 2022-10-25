// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const { VueLoaderPlugin } = require('vue-loader')
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');

webpackConfig.entry = {
    settings: path.join(__dirname, 'src', 'settings'),
    form: path.join(__dirname, 'src', 'form'),
}

webpackConfig.plugins.push(
    new VueLoaderPlugin(),
    new NodePolyfillPlugin()
)

module.exports = webpackConfig
