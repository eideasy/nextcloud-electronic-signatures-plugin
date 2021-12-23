import axios from 'axios';
import { generateUrl } from '@nextcloud/router';
import OC from './OC';

class SigningQueue {

  getQueue(filePath) {
    return axios({
      method: 'get',
      url: generateUrl('/apps/electronicsignatures/signing_queue'),
      responseType: 'json',
      headers: {
        requesttoken: OC.requestToken,
      },
      params: {
        path: filePath,
      },
    });
  }

  setQueue(filePath, emails) {
    return axios({
      method: 'post',
      url: generateUrl('/apps/electronicsignatures/signing_queue'),
      responseType: 'json',
      headers: {
        requesttoken: OC.requestToken,
      },
      data: {
        path: filePath,
        emails,
      },
    });
  }

}

export default SigningQueue;
