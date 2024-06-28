require('../view/viewmode');

require('../ext/contextmenu');
require('../ext/crumb');
require('../ext/banner');
require('../ext/search');
require('../ext/download');
require('../ext/filter');
require('../ext/info');
require('../ext/l10n');
require('../ext/preview');
require('../ext/select');
require('../ext/sort');
require('../ext/thumbnails');
require('../ext/title');
require('../ext/tree');

const href = global.window.document.location.href;
require('../core/location').setLocation(href, true);
