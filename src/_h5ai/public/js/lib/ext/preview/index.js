const allsettings = require('../../core/settings');

const previewSettings = Object.assign({
  enable: false,
  module: {img: {}, aud: {}, vid: {}, txt: {}}
}, allsettings['feature']['preview']);

module.exports = previewSettings;

require('./preview');
require('./preview-aud');
require('./preview-img');
require('./preview-txt');
require('./preview-vid');
