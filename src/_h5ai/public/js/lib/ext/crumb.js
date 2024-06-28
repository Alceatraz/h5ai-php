const {each, dom} = require('../util');
const event = require('../core/event');
const location = require('../core/location');
const resource = require('../core/resource');
const allsettings = require('../core/settings');
const base = require('../view/base');

const settings = Object.assign({
  enable: false,
  override: 'H5ai-x'
}, allsettings['browsing']['crumb']);

const crumbTpl =
  `<a class="crumb">
    <img class="sep" src="${resource.image('crumb')}" alt="IMG"/>
    <span class="label"></span>
    </a>`;

const crumbBarTpl = '<div id="crumbbar"></div>';

const pageHintTpl = `<img class="hint" src="${resource.icon('folder-page')}" alt="has index page"/>`;

const createHtml = item => {

  const $html = dom(crumbTpl);
  location.setLink($html, item);

  $html.find('.label').text(item.label);

  if (item.isCurrentFolder()) {
    $html.addCls('active');
  }

  if (!item.isManaged) {
    $html.app(dom(pageHintTpl));
  }

  item._$crumb = $html;
  $html[0]._item = item;

  return $html;

};

const onLocationChanged = item => {

  const $crumb = item._$crumb;
  const $crumbbar = dom('#crumbbar');

  if ($crumb && $crumb.parent()[0] === $crumbbar[0]) {
    $crumbbar.children().rmCls('active');
    $crumb.addCls('active');
  } else {

    $crumbbar.clr();

    const crumb = item.getCrumb();

    if (settings.override && settings.override.length > 0) {
      crumb[0]['label'] = settings.override;
    }

    each(crumb, crumbItem => {
      $crumbbar.app(createHtml(crumbItem));
    });
  }
};

const init = () => {
  if (!settings.enable) {
    return;
  }
  dom(crumbBarTpl).appTo(base.$flowbar);
  event.sub('location.changed', onLocationChanged);
};

init();
