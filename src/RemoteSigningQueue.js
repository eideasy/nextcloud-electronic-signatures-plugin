import axios from 'axios';
import { generateUrl } from '@nextcloud/router';
import OC from './OC';

class RemoteSigningQueue {

  create(filePath) {
    return axios({
      method: 'post',
      url: generateUrl('/apps/electronicsignatures/create_signing_queue_remote'),
      responseType: 'json',
      headers: {
        requesttoken: OC.requestToken,
      },
      data: {
        path: filePath,
      },
    });
  }

}

export default RemoteSigningQueue;
