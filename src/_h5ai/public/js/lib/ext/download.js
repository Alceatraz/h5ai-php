const {each, dom} = require('../util');
const server = require('../server');
const event = require('../core/event');
const location = require('../core/location');
const resource = require('../core/resource');
const allsettings = require('../core/settings');

const settings = Object.assign({
  enable: false,
  enableFolder: false
}, allsettings['feature']['download']);
const tpl = `<div id="download" class="tool"><img src="${resource.image('download')}" alt="download"/></div>`;
let selectedItems = [];
let $download;

const onSelection = items => {
  selectedItems = items.slice(0);
  if (selectedItems.length) {
    $download.show();
  } else if (!settings['enableFolder']) {
    $download.hide();
  }
};

const onClick = () => {

  // const type = settings.type;
  let name = 'h5ai-download';

  // const extension = type === 'shell-zip' ? 'zip' : 'tar';

  if (!name) {
    if (selectedItems.length === 1) {
      name = selectedItems[0].label;
    } else {
      name = location.getItem().label;
    }
  }

  const query = {
    action: 'download',
    as: name + '.tar',
    baseHref: location.getAbsHref(),
    hrefs: ''
  };

  each(selectedItems, (item, idx) => {
    query[`hrefs[${idx}]`] = item.absHref;
  });

  server.formRequest(query);
};

const init = () => {
  if (!settings.enable) {
    return;
  }

  $download = dom(tpl)
    .hide()
    .appTo('#toolbar')
    .on('click', onClick);

  if (settings['enableFolder']) {
    $download.show();
  }

  event.sub('selection', onSelection);
};

init();
