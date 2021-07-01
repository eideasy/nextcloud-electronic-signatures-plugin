const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config');
webpackConfig.module.rules = [
  {
    test: /\.css$/,
    use: ['style-loader', 'css-loader'],
  },
  {
    test: /\.scss$/,
    use: ['style-loader', 'css-loader', 'sass-loader'],
  },
  {
    test: /\.(js|vue)$/,
    use: 'eslint-loader',
    exclude: /node_modules/,
    enforce: 'pre',
  },
  {
    test: /\.vue$/,
    loader: 'vue-loader',
  },
  {
    test: /\.js$/,
    loader: 'babel-loader',
    exclude: /node_modules/,
  },
  {
    test: /\.(png|jpg|gif|svg)$/,
    loader: 'url-loader',
    options: {
      esModule: false,
    }
  },
];

webpackConfig.entry = {
    adminSettings: path.join(__dirname, 'src', 'adminSettings.js'),
    signFile: path.join(__dirname, 'src', 'signFile.js'),
    fileActions: path.join(__dirname, 'src', 'fileActions.js'),
    main: path.join(__dirname, 'src', 'main.js'),
}

module.exports = webpackConfig
