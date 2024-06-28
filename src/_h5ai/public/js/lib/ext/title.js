const event = require('../core/event');
const allsettings = require('../core/settings');

const doc = global.window.document;

const settings = Object.assign({
  dynamic: false,
  staticTitle: 'H5ai-x'
}, allsettings['browsing']['title']);

const onLocationChanged = item => {
  const labels = item.getCrumb().map(i => i.label);
  let title = labels.join(' > ');
  if (labels.length > 1) {
    title = labels[labels.length - 1] + ' - ' + title;
  }
  doc.title = title;
};

const init = () => {
  if (settings.dynamic) {
    event.sub('location.changed', onLocationChanged);
  } else {
    doc.title = settings['staticTitle'];
  }
};

init();
