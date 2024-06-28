const {jsonRequest} = require('./server');

const config = module.exports = {
  _update: query => jsonRequest(query).then(resp => Object.assign(config, resp))
};


