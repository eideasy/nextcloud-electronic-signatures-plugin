import axios from 'axios';
import OC from './OC';
import { generateUrl } from '@nextcloud/router';

const fetchAdminSettings = function fetchAdminSettings() {
  return axios({
    method: 'get',
    url: generateUrl('/apps/electronicsignatures/settings'),
    responseType: 'json',
    headers: {
      requesttoken: OC.requestToken,
    },
  });
};

export default fetchAdminSettings;
