const {dom} = require('../../util');
const format = require('../../core/format');
const preview = require('./preview');

const previewSettings = require('./index');

const settings = Object.assign({
  enable: false,
  autoplay: false,
  fileType: []
}, previewSettings['module']['aud']);

const tpl = '<audio id="pv-content-aud"/>';

const updateGui = () => {
  const el = dom('#pv-content-aud')[0];
  if (!el) {
    return;
  }

  preview.setLabels([
    preview.item.label,
    format.formatDate(el.duration * 1000, 'm:ss')
  ]);
};

const addUnloadFn = el => {
  el.unload = () => {
    el.pause();
    el.src = '';
    el.load();
  };
};

const load = item => {
  return new Promise(resolve => {
    const $el = dom(tpl)
      .on('loadedmetadata', () => resolve($el))
      .attr('controls', 'controls');
    if (settings.autoplay) {
      $el.attr('autoplay', 'autoplay');
    }
    addUnloadFn($el[0]);
    $el.attr('src', item.absHref);
  });
};

const init = () => {
  if (settings.enable) {
    preview.register(settings.fileType, load, updateGui);
  }
};

init();
