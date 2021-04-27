const config = {
  appId: 'electronicsignatures',
  features: {},
};

if (process.env.NODE_ENV === 'development') {
  config.features.signingLinkByEmail = true;
}

export default config;
