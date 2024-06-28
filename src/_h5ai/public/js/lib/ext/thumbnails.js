const {each, map, includes} = require('../util');
const server = require('../server');
const event = require('../core/event');
const allsettings = require('../core/settings');

const settings = Object.assign({
  enable: false,
  extension: [],
  chunkSize: 5,
  delayTime: 100
}, allsettings['feature']['thumbnails']);

const queueItem = (queue, item) => {

  let type = null;

  if (item.type === 'folder') {
    return;
    // } else if (includes(settings.blocklist, item.type)) {
    //   type = 'blocked';
  } else if (includes(settings['extension'], item.type)) {
    type = item.type;
  } else {
    // type = 'file';
    return;
  }

  if (item.thumbRational) {
    item.$view.find('.icon img').addCls('thumb').attr('src', item.thumbRational);
  } else {
    queue.push({
      type,
      href: item.absHref,
      callback_thumb: src => {
        if (src && item.$view) {
          item.thumbRational = src;
          item.$view.find('.icon img').addCls('thumb').attr('src', src);
        }
      },
      callback_type: filetype => {
        if (filetype && item.$view) {

          // sconsole.log(`Updated type for ${item.label}: ${item.type}->${filetype}`);

          item.type = filetype;
          event.pub('item.changed', item);
        }
      }
    });
  }
};

const requestQueue = queue => {
  const thumbs = map(queue, req => {
    return {
      type: req.type,
      href: req.href
    };
  });

  return server.jsonRequest({
    action: 'get',
    thumbs
  }).then(json => {
    each(queue, (req, idx) => {
      if (json) {
        if (json['thumbs']['href']) {
          req.callback_thumb(json['thumbs']['href'][idx]);
        }
        if (json['thumbs']['type']) {
          req.callback_type(json['thumbs']['type'][idx]);
        }
      }
    });
  });
};

const breakAndRequestQueue = queue => {
  const len = queue.length;
  const chunkSize = settings['chunkSize'];
  let p = Promise.resolve();
  for (let i = 0; i < len; i += chunkSize) {
    p = p.then(() => requestQueue(queue.slice(i, i + chunkSize)));
  }
};

const handleItems = items => {
  const queue = [];
  each(items, item => queueItem(queue, item));
  breakAndRequestQueue(queue);
};

const onViewChanged = added => {
  // console.info('thumbnails - onViewChanged');
  // console.info(added);
  setTimeout(() => handleItems(added), settings['delayTime']);
};

const init = () => {

  if (!settings['enable']) {
    return;
  }

  event.sub('view.changed', onViewChanged);
};

init();

// console.info(settings);
