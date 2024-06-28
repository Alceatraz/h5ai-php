const {map, debounce, parsePattern, dom} = require('../util');
const server = require('../server');
const event = require('../core/event');
const location = require('../core/location');
const resource = require('../core/resource');
const allsettings = require('../core/settings');
const Item = require('../model/item');
const view = require('../view/view');

const settings = Object.assign({
  enable: false,
  enableRegex: false,
  debounceDelay: 1000
}, allsettings['feature']['searching']);

const tpl =
  `<div id="search" class="tool">
            <img src="${resource.image('search')}" alt="search"/>
            <input class="l10n_ph-search" type="text" value=""/>
        </div>`;
let inputIsVisible = false;
let prevPattern = '';
let $search;
let $input;

const search = (pattern = '') => {
  if (pattern === prevPattern) {
    return;
  }
  prevPattern = pattern;

  if (!pattern) {
    view.setLocation();
    return;
  }

  $search.addCls('pending');

  server.jsonRequest({
    action: 'get',
    search: {
      href: location.getAbsHref(),
      pattern
    }
  }).then(response => {
    $search.rmCls('pending');
    view.setHint('noMatch');
    view.setItems(map(response.search, item => Item.get(item)));
  });
};

const update = () => {
  if (inputIsVisible) {
    $search.addCls('active');
    $input[0].focus();
    search(parsePattern($input.val(), settings['enableRegex']));
  } else {
    search();
    $search.rmCls('active');
  }
};

const toggle = () => {
  inputIsVisible = !inputIsVisible;
  update();
};

const reset = () => {
  inputIsVisible = false;
  $input.val('');
  update();
};

const init = () => {
  if (!settings.enable) {
    return;
  }

  $search = dom(tpl).appTo('#toolbar');
  $input = $search.find('input');

  $search.find('img').on('click', toggle);
  $input.on('keyup', debounce(update, settings['debounceDelay']));
  event.sub('location.changed', reset);
};

init();
