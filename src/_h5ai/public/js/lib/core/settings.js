const config = require('../config');

const setup = {
  rootHref: '/',
  publicHref: '/_h5ai/public/'
};

module.exports = Object.assign(setup, config['options']);


