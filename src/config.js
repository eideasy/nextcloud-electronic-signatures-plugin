const config = {
  appId: 'electronicsignatures',
  features: {},
};

if (process.env.NODE_ENV === 'development') {
  config.features.showEmailSignatureSetting = true;
}

export default config;
