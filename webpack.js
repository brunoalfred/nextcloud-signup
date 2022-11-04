// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry = {
    settings: path.join(__dirname, 'src', 'settings'),
    form: path.join(__dirname, 'src', 'form'),
}

module.exports = webpackConfig
