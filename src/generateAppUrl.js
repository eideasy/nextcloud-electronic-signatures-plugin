import { generateUrl } from '@nextcloud/router';
import config from './config';
const generateAppUrl = function generateAppUrl(endpoint) {
  return generateUrl(`/apps/${config.appId}${endpoint}`);
};

export default generateAppUrl;
