import {render} from '@testing-library/vue';
import '@testing-library/jest-dom';
import SignatureLinkModal from './SignatureLinkModal';
import EventBus from './EventBus';
jest.mock("@nextcloud/router", () => (
  {
    generateUrl: () => 'someMockingUrl',
  }
));
jest.mock("./OC", () => ({ requestToken: 'thisIsMockRequestToken' }));

test('SignatureLinkModal component', async () => {
  const {getByText} = render(SignatureLinkModal);
  EventBus.$emit('GET_SIGNING_LINK_CLICK', { filename: 'test-name' });
  expect(true);
})
