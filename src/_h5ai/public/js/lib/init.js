const {awaitReady} = require('./util');
const config = require('./config');

const query = {
  action: 'get',
  types: true,
  theme: true,
  langs: true,
  options: true
};

config._update(query)
  .then(() => awaitReady())
  .then(() => require(`./main/index`));
