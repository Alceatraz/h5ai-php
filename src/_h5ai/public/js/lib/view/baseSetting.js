const allsettings = require('../core/settings');

const generalSettings = Object.assign({
  use1024: false,
  showParentFolder: false,
  showParentFolderName: false,
  maxIconSize: 80
}, allsettings['browsing']['general']);

const paginationSettings = Object.assign({
  enable: true,
  hidden: false,
  default: 50,
  pagination: [10, 25, 50, 100, 250, 500, 0]
}, allsettings['browsing']['pagination']);

module.exports = {
  generalSettings,
  paginationSettings
};
