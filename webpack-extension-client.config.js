const webpack = require('webpack');
const path = require('path');
const isProduction = process.env.SYMFONY_ENV  === "prod"

const config = {
    entry: './node_modules/@awardwallet/extension-client/dist/DesktopExtensionInterface.js',
    devtool: 'inline-source-map',
    output: {
        path: path.resolve('./web/assets/extension-client'),
        filename: 'bundle.js',
        library: {
            type: 'amd'
        },
    },
    plugins: [
        new webpack.DefinePlugin({
            __CHROME_EXTENSION_ID__: isProduction ? "'elbkchakmaiinadjpnmdgpflpjogpgmb'" : "'nlfhklfcdielnbndncmdnibglgkfdfde'",
        })
    ]
};

module.exports = config;
