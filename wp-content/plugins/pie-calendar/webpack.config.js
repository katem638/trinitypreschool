const defaultConfig = require("@wordpress/scripts/config/webpack.config");

var config = {
  ...defaultConfig,
  entry: {
    ...defaultConfig.entry(),
    index: "./src/index.js",
  },
};

// Return Configuration
module.exports = config;
